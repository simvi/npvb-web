<?
if (!$je_suis_deja_connecte_connard){
	include("PASSWD/_passwrds.inc.php");
	$ConnectionBD=true;
	if (!($sdblink = mySql_connect($basesql, $utilisateursql, $motdepassesql))){
	 	$ConnectionBD=false;
		}
	if (!(mySql_select_db($labasededonnees, $sdblink))){
		$ConnectionBD=false;
		}
	// Configure l'encodage de la connexion MySQL
	if ($ConnectionBD) {
		mysql_query("SET CHARACTER SET utf8", $sdblink);
		mysql_query("SET NAMES utf8", $sdblink);
	}
	$je_suis_deja_connecte_connard=true;
	}
?>