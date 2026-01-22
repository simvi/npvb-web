<?
//Force la mise en cache
header("Expires: ".gmdate("D, d M Y H:i:s")." GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
// HTTP/1.1
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
// HTTP/1.0
header("Pragma: no-cache");

//$Page="maintenance";


$PasseParIndex=true;

include("classes.inc.php");
include("variables.inc.php");
include("fonctions.inc.php");
include("_entete.inc.php");

if (!$ConnectDB) $Page="maintenance";

if(isset($_POST)) {
	foreach($_POST as $key=>$val) {
 		eval("$".$key." = \"".$val."\";");
 	} 
}else if(isset($_GET)) {
	foreach($_GET as $key=>$val) {
		eval("$".$key." = \"".$val."\";");
	} 
} 

switch ($Page){
	case "adminstats":
	case "adminfichejour":
	case "adminevenements":
	case "adminequipes":
	case "adminmembres":
	case "adminaccueil":
	case "adminnewmessage":
	case "adminfichemembre": if ($Joueur->DieuToutPuissant<>"o") $Page="accueil";
	case "jour": 
	//case "quinzeans": 
	case "membres": if (!$Joueur) $Page="accueil";
	case "calendrier":
	case "Erreur404": break;
	default: $Page="accueil";
	}
$Contenu = $Page.".inc.php";
print ("<"."?xml version=\"1.0\" encoding=\"ISO-8859-1\"?".">");
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
	<meta name="Generator" content="Crimson Editor" />
	<meta name="author" content="Nantes PVB" />
	<meta name="Distribution" content="Global" />
	<meta name="Robots" content="All" />
	<meta name="reply-to" content="nantespvb@gmail.com" />
	<meta name="owner" content="Nantes Plaisir du Volley Ball" />
	<meta name="Rating" content="General" />
    	<meta name="verify-v1" content="FSYeF8Wwa0ABLnMB8SFSvXigw4CQ/JX3wf7yEIGOfsw=" />
	<meta name="apple-itunes-app" content="app-id=793137223, app-argument=http%3A%2F%2Fyousite.com%2Fsomepath%3Fquery%3Da%2Cb" />
	<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0' >

	<?
		switch ($Page){
			case "adminequipes": print("\n\t<link rel=\"StyleSheet\" href=\"Feuilles de style/AdminEquipes.css\" type=\"text/css\" />"); break;
			case "adminfichemembre": 
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
		<?if ($Joueur){?>		<span id="NomJoueur">Bienvenue<br/><?=$Joueur->Prenom?> <?=$Joueur->Nom?></span><?}?>
	</div>
	<div id="Menu">
	<ul>
			<?
		/* MENU NON CONNECT
		<li<?=(($Page=="accueil")?" class=\"MenuActif\"":"")?>><a href="<?=$PHP_SELF?>?Page=accueil">Accueil</a></li>
		<li<?=((($Page=="calendrier")||($Page=="jour"))?" class=\"MenuActif\"":"")?>><a href="<?=$PHP_SELF?>?Page=calendrier">Le calendrier</a></li>
		*/
		?>
<?
if ((!$Joueur)&&($Page=="accueil")){
?>		
		<li class="LiFormulaireLogin">
		<form id="FormulaireLogin" action="<?=$PHP_SELF?>" method="post">
		<div>
			<input type="hidden" name="Page" value="accueil" />
			<input type="text" name="Pseudonyme" value="Votre login" class="LoginInput" onfocus="videChamp(this)"/>
			<input type="password" name="Password" value="MotDePasse" class="LoginInput" onfocus="videChamp(this)"/>
			<input type="submit" value="S'identifier"  class="PetitBouton Action"/>
		</div>
		</form>
		</li>
<?
}
?>
<?
if ($Joueur) {
?>
<li<?=(($Page=="accueil")?" class=\"MenuActif\"":"")?>><a href="<?=$PHP_SELF?>?Page=accueil">Accueil</a></li>
		<li<?=((($Page=="calendrier")||($Page=="jour"))?" class=\"MenuActif\"":"")?>><a href="<?=$PHP_SELF?>?Page=calendrier">Le calendrier</a></li>
		<li<?=(($Page=="membres")?" class=\"MenuActif\"":"")?>><a href="<?=$PHP_SELF?>?Page=membres">Les membres</a></li>
		<li><a href="<?=$PHP_SELF?>?Page=Accueil&amp;Action=deloguer">Fermer session</a></li>
<?	
}
?>
	</ul>
	
	



<?
if ($Joueur->DieuToutPuissant=="o"){
?>

	<!-- Menu 15 ans du club 
	<div id="special">
		<?=(($Page=="quinzesans")?" class=\"MenuActif\"":"")?><a href="<?=$PHP_SELF?>?Page=quinzeans"> - S'inscrire pour l'evenement "Les 15 ans du club NPVB" - </a>
	</div>

	<br/>-->

	<ul>
		<li<?=(($Page=="adminequipes")?" class=\"MenuActif\"":"")?>><a href="<?=$PHP_SELF?>?Page=adminequipes">Admin.Equipes</a></li>
		<li<?=((($Page=="adminevenements")||($Page=="adminfichejour"))?" class=\"MenuActif\"":"")?>><a href="<?=$PHP_SELF?>?Page=adminevenements">Admin.Evenements</a></li>
		<li<?=((($Page=="adminmembres")||($Page=="adminfichemembre"))?" class=\"MenuActif\"":"")?>><a href="<?=$PHP_SELF?>?Page=adminmembres">Admin.Membres</a></li>
		<li<?=((($Page=="adminaccueil")||($Page=="adminaccueil"))?" class=\"MenuActif\"":"")?>><a href="<?=$PHP_SELF?>?Page=adminaccueil">Admin.Accueil</a></li>
	</ul>
<?
}		
?>


	
	
	</div>
	
	<div id="Corps">
		<?require($Contenu);?>
	</div>

</body>
</html>

