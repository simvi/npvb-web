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

include("classes.inc.php");
include("variables.inc.php");
include("fonctions.inc.php");
include("_entete.inc.php");

if (!$ConnectDB) $Page="maintenance";

// CORRECTIF SÉCURITÉ #1: Suppression eval() - Whitelist des variables autorisées
// Compatible PHP 4 - Pas d'utilisation de filter_input()
$allowed_vars = array('Page', 'Pseudonyme', 'Password', 'Action', 'Equipe', 'Jour', 'Mois', 'Annee', 'DateHeure', 'Libelle');

if(isset($_POST) && is_array($_POST)) {
	foreach($_POST as $key => $val) {
		if (in_array($key, $allowed_vars)) {
			// Sanitization basique compatible PHP 4
			$val = stripslashes($val);
			$val = str_replace(array('<', '>', '"', "'", "\0"), '', $val);
			$$key = $val;
		}
	}
}

if(isset($_GET) && is_array($_GET)) {
	foreach($_GET as $key => $val) {
		if (in_array($key, $allowed_vars)) {
			// Sanitization basique compatible PHP 4
			$val = stripslashes($val);
			$val = str_replace(array('<', '>', '"', "'", "\0"), '', $val);
			$$key = $val;
		}
	}
}

// Initialisation par défaut si Page non définie
if (!isset($Page) || empty($Page)) {
	$Page = "accueil";
}

// CORRECTIF SÉCURITÉ #2: Whitelist stricte des pages autorisées
$pages_autorisees = array(
	'accueil', 'calendrier', 'jour', 'membres', 'Erreur404', 'maintenance',
	'adminstats', 'adminfichejour', 'adminevenements', 'adminequipes',
	'adminmembres', 'adminaccueil', 'adminnewmessage', 'adminfichemembre'
);

// Vérifier que la page demandée est autorisée
if (!in_array($Page, $pages_autorisees)) {
	$Page = "accueil";
}

// CORRECTIF SÉCURITÉ #3: Contrôle d'accès corrigé avec break appropriés
$pages_admin = array('adminstats', 'adminfichejour', 'adminevenements',
                     'adminequipes', 'adminmembres', 'adminaccueil',
                     'adminnewmessage', 'adminfichemembre');

if (in_array($Page, $pages_admin)) {
	// Vérification admin stricte
	if (!isset($Joueur) || !is_object($Joueur) || $Joueur->DieuToutPuissant != "o") {
		$Page = "accueil";
	}
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

// Fonction helper pour échapper les sorties (compatible PHP 4)
function escape_html($string) {
	return htmlspecialchars($string, ENT_QUOTES, 'ISO-8859-1');
}

// Variable sécurisée pour les liens
$script_name = escape_html($_SERVER['SCRIPT_NAME']);

print ("<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>");
?>

<!DOCTYPE html
                    PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
                    "http://www.w3.org/TR/xhtml1/dtD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"
           xml:lang="FR" lang="French">
<head>
	<title>Nantes Plaisir du Volley Ball - LE SITE OFFICIEL</title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<meta http-equiv="Content-Language" content="fr" />
	<meta name="Description" content="Calendrier de Nantes Plaisir du Volley Ball" />
	<meta name="Keywords" content="nantes, volley, ball, sport, plaisir, detente, loisir, club, association, 44, Noe Lambert, gymnase, site, web, calendrier" />
	<meta name="author" content="Nantes PVB" />
	<meta name="Robots" content="All" />
	<meta name="reply-to" content="nantespvb@gmail.com" />
	<meta name="owner" content="Nantes Plaisir du Volley Ball" />
	<meta name="Rating" content="General" />
    	<meta name="verify-v1" content="FSYeF8Wwa0ABLnMB8SFSvXigw4CQ/JX3wf7yEIGOfsw=" />
	<meta name="apple-itunes-app" content="app-id=793137223, app-argument=http%3A%2F%2Fyousite.com%2Fsomepath%3Fquery%3Da%2Cb" />
	<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0' >

	<?php
		switch ($Page){
			case "adminequipes": print("\n\t<link rel=\"StyleSheet\" href=\"Feuilles de style/AdminEquipes.css\" type=\"text/css\" />"); break;
			case "adminfichemembre":
			case "adminnewmessage":
			case "adminnewmessage":
			case "adminmembres":
			case "adminaccueil":
			case "membres": print("\n\t<link rel=\"StyleSheet\" href=\"Feuilles de style/Membres.css\" type=\"text/css\" />"); break;
			case "adminfichejour": print("\n\t<link rel=\"StyleSheet\" href=\"Feuilles de style/AdminFicheJour.css\" type=\"text/css\" />"); break;
			case "jour": print("\n\t<link rel=\"StyleSheet\" href=\"Feuilles de style/Jour.css\" type=\"text/css\" />"); break;
			case "adminevenements":
			case "calendrier": 	print("\n\t<link rel=\"StyleSheet\" href=\"Feuilles de style/Bulle.css\" type=\"text/css\" />");
						print("\n\t<link rel=\"StyleSheet\" href=\"Feuilles de style/Calendrier.css\" type=\"text/css\" />"); break;
			default: break;
		}
	?>

	<link rel="StyleSheet" href="Feuilles de style/style.css" type="text/css" />

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
		<a href="http://nantespvb.free.fr">
			<img src="Images/logo.svg" alt="Nantes Plaisir du Volley-Ball" />
		</a>
		<?php if (isset($Joueur) && is_object($Joueur)){ ?>
			<span id="NomJoueur">Bienvenue<br/><?php echo escape_html($Joueur->Prenom); ?> <?php echo escape_html($Joueur->Nom); ?></span>
		<?php } ?>
	</div>
	<div id="Menu">
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
		<li<?php echo ((($Page=="adminaccueil")||($Page=="adminaccueil"))?" class=\"MenuActif\"":""); ?>><a href="<?php echo $script_name; ?>?Page=adminaccueil">Admin.Accueil</a></li>
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
