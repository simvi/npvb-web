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
	$je_suis_deja_connecte_connard=true;
	}
?>