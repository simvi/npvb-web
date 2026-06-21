<?php
// Désactive la mise en cache pour pages dynamiques
header("Expires: ".gmdate("D, d M Y H:i:s")." GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
// HTTP/1.1
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
// HTTP/1.0
header("Pragma: no-cache");

// Headers de sécurité (compatible PHP 4)
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");

//$Page="maintenance";

$PasseParIndex=true;

// Configuration club (credentials DB, identité, couleurs) — propre à chaque déploiement
require_once("config.php");

// CORRECTIF SÉCURITÉ #1 (PHP 8) : register_globals étant supprimé, on recrée les
// variables depuis $_GET/$_POST (comportement attendu par le code legacy, y compris
// les champs au nom dynamique type seraPresent<Equipe><Date>).
// IMPORTANT : cette extraction doit avoir lieu AVANT les includes, car
// variables.inc.php calcule des valeurs dérivées ($MoisAvant...) à partir des
// variables de requête et _entete.inc.php utilise $Action (déconnexion).
// - Liste NOIRE des variables internes critiques : interdites à l'écrasement
//   (sinon contournement d'authentification via $Joueur, $sdblink, etc.).
// - Les valeurs tableau sont ignorées (anti variable-injection).
// - Sanitization : suppression des caractères dangereux (anti-injection SQL/XSS),
//   les requêtes interpolant directement ces valeurs.
$protected_vars = array(
	'Joueur', 'sdblink', 'ConnectDB', 'ConnectionBD', 'PasseParIndex',
	'Equipes', 'Joueurs', 'Evenements', 'ListeJoueurs', 'LocalEquipes', 'LocalJoueurs',
	'protected_vars', 'pages_autorisees', 'je_suis_deja_connecte_connard',
	'GLOBALS', '_GET', '_POST', '_SESSION', '_SERVER', '_COOKIE', '_FILES', '_ENV', '_REQUEST'
);

foreach (array($_GET, $_POST) as $__source) {
	if (isset($__source) && is_array($__source)) {
		foreach ($__source as $key => $val) {
			if (in_array($key, $protected_vars)) continue;
			if (is_array($val)) continue;
			// Sanitization basique compatible legacy
			$val = stripslashes($val);
			$val = str_replace(array('<', '>', '"', "'", "\0"), '', $val);
			$$key = $val;
		}
	}
}

// Fichiers uploadés : en register_globals (PHP 4), $champ contenait le chemin
// temporaire du fichier. On reproduit ce comportement à partir de $_FILES,
// attendu par is_uploaded_file()/move_uploaded_file()/getImageSize().
if (isset($_FILES) && is_array($_FILES)) {
	foreach ($_FILES as $key => $infos) {
		if (in_array($key, $protected_vars)) continue;
		$$key = $infos['tmp_name'];
	}
}

include("classes.inc.php");
include("variables.inc.php");
include("fonctions.inc.php");
include("permissions.inc.php");

// Déterminer la page AVANT d'inclure _entete
if (!isset($Page) || empty($Page)) {
	$Page = "accueil";
}

// Ne pas générer le HTML pour les endpoints AJAX/API
$pages_api = array('adminaccueilimage');
$is_api_endpoint = in_array($Page, $pages_api);

if ($is_api_endpoint) {
	ob_start();
	include("_entete.inc.php"); // charge session + $Joueur sans envoyer le HTML
	ob_end_clean();
} else {
	include("_entete.inc.php");
	if (!$ConnectDB) $Page="maintenance";
}

// CORRECTIF SÉCURITÉ #2: Whitelist stricte des pages autorisées
$pages_autorisees = array(
	'accueil', 'calendrier', 'jour', 'membres', 'Erreur404', 'maintenance',
	'adminstats', 'adminfichejour', 'adminevenements', 'adminequipes',
	'adminmembres', 'adminnewmessage', 'adminfichemembre', 'adminmessages',
	'adminaccueilimage', 'resetmotdepasse'
);

// Vérifier que la page demandée est autorisée
if (!in_array($Page, $pages_autorisees)) {
	$Page = "accueil";
}

// CORRECTIF SÉCURITÉ #3: Contrôle d'accès par capacité (voir permissions.inc.php)
if (!peutAccederPage($Joueur, $Page)) {
	$Page = "accueil";
}

$pages_membres = array('jour', 'membres');
if (in_array($Page, $pages_membres)) {
	// Vérification connexion
	if (!isset($Joueur) || !is_object($Joueur)) {
		$Page = "accueil";
	}
}

// CORRECTIF SÉCURITÉ #4: Vérification existence du fichier avant inclusion
$Contenu = $Page . ".inc.php";
if (!file_exists($Contenu)) {
	$Contenu = "Erreur404.inc.php";
	// Fallback si Erreur404 n'existe pas non plus
	if (!file_exists($Contenu)) {
		die("Erreur: Page introuvable");
	}
}

// Endpoints API : inclure et exit avant de générer le HTML
if ($is_api_endpoint) {
	require($Contenu);
	exit;
}

// Fonction helper pour échapper les sorties (compatible PHP 4)
function escape_html($string) {
	return htmlspecialchars($string, ENT_QUOTES, 'ISO-8859-1');
}

// Variable sécurisée pour les liens
$script_name = escape_html($_SERVER['SCRIPT_NAME']);

print ("<?xml version=\"1.0\" encoding=\"UTF-8\"?>");
?>

<!DOCTYPE html
                    PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
                    "http://www.w3.org/TR/xhtml1/dtD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"
           xml:lang="FR" lang="French">
<head>
	<title><?php echo htmlspecialchars($config['club_nom']); ?> - LE SITE OFFICIEL</title>
	<meta charset="UTF-8" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="Content-Language" content="fr" />
	<meta name="Description" content="Calendrier de <?php echo htmlspecialchars($config['club_nom']); ?>" />
	<meta name="Keywords" content="nantes, volley, ball, sport, plaisir, detente, loisir, club, association, 44, Noe Lambert, gymnase, site, web, calendrier" />
	<meta name="author" content="<?php echo htmlspecialchars($config['club_sigle']); ?>" />
	<meta name="Robots" content="All" />
	<meta name="reply-to" content="<?php echo htmlspecialchars($config['club_email']); ?>" />
	<meta name="owner" content="<?php echo htmlspecialchars($config['club_nom']); ?>" />
	<meta name="Rating" content="General" />
    	<meta name="verify-v1" content="FSYeF8Wwa0ABLnMB8SFSvXigw4CQ/JX3wf7yEIGOfsw=" />
	<meta name="apple-itunes-app" content="app-id=793137223, app-argument=http%3A%2F%2Fyousite.com%2Fsomepath%3Fquery%3Da%2Cb" />
	<meta name='viewport' content='width=device-width, initial-scale=1.0' >

	<?php
		// Lien CSS avec version automatique (date de modif) → contourne le cache navigateur
		function lienCSS($fichier) {
			$v = @filemtime("Feuilles de style/".$fichier);
			return "\n\t<link rel=\"StyleSheet\" href=\"Feuilles de style/".rawurlencode($fichier)."?v=".$v."\" type=\"text/css\" />";
		}
		switch ($Page){
			case "adminequipes": print(lienCSS("AdminEquipes.css")); break;
			case "adminfichemembre":
			case "adminnewmessage":
			case "adminnewmessage":
			case "adminmembres":
			case "membres": print(lienCSS("Membres.css")); break;
			case "adminfichejour": print(lienCSS("AdminFicheJour.css")); break;
			case "jour": print(lienCSS("Jour.css")); break;
			case "adminevenements":
			case "calendrier": 	print(lienCSS("Bulle.css"));
						print(lienCSS("Calendrier.css")); break;
			default: break;
		}
	?>

	<?php print(lienCSS("style.css")); ?>

	<style>
	:root {
		--couleur-primaire:   <?php echo $config['couleur_primaire']; ?>;
		--couleur-secondaire: <?php echo $config['couleur_secondaire']; ?>;
		--couleur-danger:     <?php echo $config['couleur_danger']; ?>;
		--couleur-succes:     <?php echo $config['couleur_succes']; ?>;
		--couleur-alerte:     <?php echo $config['couleur_alerte']; ?>;
		--couleur-accent:     <?php echo $config['couleur_accent']; ?>;
		--couleur-texte:      <?php echo $config['couleur_texte']; ?>;
	}
	</style>

	<script src="libGene.js" type="text/javascript"></script>

    <script type="text/javascript">
		var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
		document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
	</script>

	<script type="text/javascript">
		var pageTracker = _gat._getTracker("UA-5530032-3");
		pageTracker._trackPageview();
	</script>

</head>

<body>
	<div id="Entete">
		<a href="<?php echo htmlspecialchars($config['club_url']); ?>">
			<img src="<?php echo htmlspecialchars($config['club_logo']); ?>" alt="<?php echo htmlspecialchars($config['club_nom']); ?>" />
		</a>
		<?php if (isset($Joueur) && is_object($Joueur)){ ?>
			<span id="NomJoueur">Bienvenue<br/><?php echo escape_html($Joueur->Prenom); ?> <?php echo escape_html($Joueur->Nom); ?></span>
		<?php } ?>
	</div>
	<div id="Menu">
<?php if (isset($Joueur) && is_object($Joueur)) { ?>
	<input type="checkbox" id="menu-toggle" class="menu-toggle-checkbox" />
	<label for="menu-toggle" class="menu-burger" aria-label="Ouvrir le menu"><span class="burger-icon"></span>Menu</label>
<?php } ?>
	<ul>
<?php
if ((!isset($Joueur) || !is_object($Joueur)) && ($Page=="accueil")){
?>
		<li class="LiFormulaireLogin">
		<form id="FormulaireLogin" action="<?php echo $script_name; ?>" method="post">
		<div>
			<input type="hidden" name="Page" value="accueil" />
			<input type="text" name="Pseudonyme" value="Votre login" class="LoginInput" onfocus="videChamp(this)"/>
			<input type="password" name="Password" value="MotDePasse" class="LoginInput" onfocus="videChamp(this)"/>
			<input type="submit" value="S'identifier"  class="PetitBouton Action"/>
		</div>
		</form>
		<div style="margin-top: 5px; text-align: center; font-size: 0.85em;">
			<?php if (isset($ErreurDonnees["Login"]) && $ErreurDonnees["Login"]) { ?>
			<span style="color: #666;">Mot de passe oubli&eacute; ? Contactez <a href="mailto:<?php echo htmlspecialchars($config['club_email']); ?>" style="color: #666;"><?php echo htmlspecialchars($config['club_email']); ?></a></span>
		<?php } ?>
		</div>
		</li>
<?php
}
?>
<?php
if (isset($Joueur) && is_object($Joueur)) {
?>
<li<?php echo (($Page=="accueil")?" class=\"MenuActif\"":""); ?>><a href="<?php echo $script_name; ?>?Page=accueil">Accueil</a></li>
		<li<?php echo ((($Page=="calendrier")||($Page=="jour"))?" class=\"MenuActif\"":""); ?>><a href="<?php echo $script_name; ?>?Page=calendrier">Le calendrier</a></li>
		<li<?php echo (($Page=="membres")?" class=\"MenuActif\"":""); ?>><a href="<?php echo $script_name; ?>?Page=membres">Les membres</a></li>
		<li><a href="<?php echo $script_name; ?>?Page=accueil&amp;Action=deloguer">Fermer session</a></li>
<?php
}
?>
	</ul>


<?php
if (isset($Joueur) && is_object($Joueur) && $Joueur->DieuToutPuissant=="o"){
?>

	<ul>
		<li<?php echo (($Page=="adminequipes")?" class=\"MenuActif\"":""); ?>><a href="<?php echo $script_name; ?>?Page=adminequipes">Admin.Equipes</a></li>
		<li<?php echo ((($Page=="adminevenements")||($Page=="adminfichejour"))?" class=\"MenuActif\"":""); ?>><a href="<?php echo $script_name; ?>?Page=adminevenements">Admin.Evenements</a></li>
		<li<?php echo ((($Page=="adminmembres")||($Page=="adminfichemembre"))?" class=\"MenuActif\"":""); ?>><a href="<?php echo $script_name; ?>?Page=adminmembres">Admin.Membres</a></li>
		<li<?php echo (($Page=="adminmessages")?" class=\"MenuActif\"":""); ?>><a href="<?php echo $script_name; ?>?Page=adminmessages">Admin.Messages</a></li>
	</ul>
<?php
}
?>




	</div>

	<div id="Corps">
		<?php require($Contenu); ?>
	</div>

</body>
</html>
