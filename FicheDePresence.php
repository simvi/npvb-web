<?
$NbLignesParPages=36;

$PasseParIndex=true;
include("classes.inc.php");
include("variables.inc.php");
include("fonctions.inc.php");
include("_entete.inc.php");

if(isset($_POST)) {
	foreach($_POST as $key=>$val) {
 		eval("$".$key." = ".$val.";");
 	} 
}else if(isset($_GET)) {
	foreach($_GET as $key=>$val) {
		eval("$".$key." = ".$val.";");
	} 
} 

$Evenements = ChargeEvenements(null, null, substr($Jour, 0, 8));
$Joueurs = ChargeJoueurs("", "Nom, Prenom");
$Equipes = ChargeEquipes();

print ("<"."?xml version=\"1.0\" encoding=\"ISO-8859-1\"?".">");
?>

<!DOCTYPE html 
                    PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
                    "http://www.w3.org/TR/xhtml1/dtD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" 
           xml:lang="FR" lang="French">
<head>
	<title>NPVB - Fiche de présence</title>
		<link rel="StyleSheet" href="Feuilles de style/FicheDePresence.css" type="text/css" />
</head>
<body>
<?
if ($Event = $Evenements[substr($Jour, 0, 8)][substr($Jour, 8, 4)][$Evenement]){
	reset($Joueurs);
	$Equipe = $Equipes[$Event->Libelle];
	$Page=0;
	$NBJoueurs=0;
	$NBJoueursPresents=0;
	$DernierePage=false;
	while(!$DernierePage){
		$Page++;
?>
<table class="FicheDePresence">
	<tr class="Entete">
		<td rowspan="2" class="EnteteLogo"><div class="BoutonsAction"><input type="button" value="RETOUR" class="BoutonAction" onclick="window.history.go(-1)" /><input type="button" value="IMPRIMER" class="BoutonAction" onclick="window.print()" /></div><div class="Logo"><img src="<?=$RepertoireImages?>Logo-print.jpg" class="LogoNPVB" alt="" /></div></td>
		<td class="EnteteEvent"><?=$Event->Intitule?></td>
		<td class="EnteteDate"><?=substr($Jour, 6, 2)?> <?=$montharray[(int)substr($Jour, 4, 2)]?> <?=substr($Jour, 0, 4)?></td>
	</tr>
	<tr class="Entete">
		<td colspan="2" class="EnteteTitre">Feuille de présence</td>
	</tr>
	<tr>
		<td colspan="3">
			<table class="MembresPresents">
				<tr class="LigneTitres">
					<td class="Colonne1">Prénom</td>
					<td class="Colonne2">Nom</td>
					<td class="Colonne3">Prévu</td>
					<td class="Colonne4">Présent</td>
				</tr>				
<?	
		$Ligne=1;
		while (($Ligne <= $NbLignesParPages)&&(list($Key, $UnJoueur) = each($Joueurs))){
			if (($Equipe->faisPartie($UnJoueur->Pseudonyme))||$Event->seraPresent($UnJoueur->Pseudonyme)){
				print("\n\t\t\t\t<tr class=\"".(($Ligne==1)?"Ligne1":"Ligne")."\">\n\t\t\t\t\t<td class=\"Colonne1\">".$UnJoueur->Prenom."</td><td class=\"Colonne2\">".$UnJoueur->Nom."</td><td class=\"Colonne3\">".(($Event->seraPresent($UnJoueur->Pseudonyme))?"&nbsp;":"ABS")."</td><td class=\"Colonne4\">&nbsp;</td>\n\t\t\t\t</tr>");
				$NBJoueurs++;
				if ($Event->seraPresent($UnJoueur->Pseudonyme)) $NBJoueursPresents++;
				$Ligne++;				
			}
		}
		if (!$UnJoueur){
			$DernierePage=true;
			print("\n\t\t\t\t<tr class=\"LigneTotal\">\n\t\t\t\t\t<td class=\"Colonne1\">TOTAL</td><td class=\"Colonne2\">(sur ".$NBJoueurs." personnes)</td><td class=\"Colonne3\">".$NBJoueursPresents."</td><td class=\"Colonne4\"> &nbsp; </td>\n\t\t\t\t</tr>");
		}
?>

			</table>
		</td>
	</tr>
</table>
<p class="LiensW3C"><a href="http://validator.w3.org/check?uri=referer"><img src="http://www.w3.org/Icons/valid-xhtml10" alt="Valid XHTML 1.0!" height="31" width="88" /></a><a href="http://jigsaw.w3.org/css-validator/"><img style="width:88px;height:31px" src="http://jigsaw.w3.org/css-validator/images/vcss.png" alt="Valid CSS!" /></a></p>
<?
	}
}else{/*
?>


<p class="ErreurEvent">L'evènement <?=$Evenement?> le <?=$Jour> est invalide</p>
<?
*/}
?>
</body>
</html>

