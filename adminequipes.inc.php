<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}
if ($Joueur->DieuToutPuissant=="n"){ require("accueil.inc.php"); return;}

$Equipes = ChargeEquipes();
$Joueurs = ChargeJoueurs("V", "Nom, Prenom");

if (($Modif=="oui")&&($Equipes[$Equipe])){
	$Modification=true;
	foreach ($Joueurs as $UnJoueur){
		if ($Equipes[$Equipe]->faisPartie($UnJoueur->Pseudonyme)){
			eval("$"."Enleve = $"."EnleveMembre".$UnJoueur->Pseudonyme.";");
			if ($Enleve=="on") {
				if (!mySql_query("DELETE FROM NPVB_Appartenance WHERE Joueur='".$UnJoueur->Pseudonyme."' AND Equipe='".$Equipe."'", $sdblink)) $ErreurDonnees["Enregistrement"] .= "Erreur d'enregistrement: ".mySql_errno($sdblink).", ".mySql_error($sdblink)."<br/>";
			}
		}
	}
	if ($Joueurs[$AjouteMembre]){
		if (!mySql_query("INSERT INTO NPVB_Appartenance (Joueur, Equipe) VALUES ('".$AjouteMembre."', '".$Equipe."')", $sdblink)) $ErreurDonnees["Enregistrement"] .= "Erreur d'enregistrement: ".mySql_errno($sdblink).", ".mySql_error($sdblink)."<br/>";
	}
	$TousJoueurs = (substr($TousJoueurs, 0, 1)=="o")?"o":"n";
	$PresenceDefaut = (substr($PresenceDefaut, 0, 1)=="o")?"o":"n";
	if (($Responsable<>$Equipes[$Equipe]->Responsable)||($Supleant<>$Equipes[$Equipe]->Supleant)||($TousJoueurs<>$Equipes[$Equipe]->TousJoueurs)||($PresenceDefaut<>$Equipes[$Equipe]->PresenceDefaut)){
		if (!mySql_query("UPDATE NPVB_Equipes SET Responsable='".$Responsable."', Supleant='".$Supleant."', TousJoueurs='".$TousJoueurs."', PresenceDefaut='".$PresenceDefaut."' WHERE (Nom='".$Equipe."')", $sdblink)) $ErreurDonnees["Enregistrement"] .= "Erreur d'enregistrement: ".mySql_errno($sdblink).", ".mySql_error($sdblink)."<br/>";
	}
	$Equipes = ChargeEquipes();
}




?>
	
<table id="Equipes">
<?
if ($Modification){
	if ($ErreurDonnees["Enregistrement"]){
		print("			<tr><td><p class=\"ModifError\">".$ErreurDonnees["Enregistrement"]."</p></td></tr>");
	}else{
		print("			<tr><td><p class=\"ModifOk\">Modifications effectuées avec succès</p></td></tr>");
	}
}	
?>


<?
foreach($Equipes as $UneEquipe){
	if (($UneEquipe->Nom=="SEANCE")||($UneEquipe->Nom=="ASSO")){
		$Style=ucFirst(strToLower($UneEquipe->Nom));
	}else{
		$Style="UneEquipe";
	}
	
?>

	<tr>
		<td class="FicheEquipe <?=$Style?>">
			<form action="<?=$PHP_SELF?>">
				<div>
				<input type="hidden" name="Page" value="adminequipes" />
				<input type="hidden" name="Modif" value="oui" />
				<input type="hidden" name="Equipe" value="<?=$UneEquipe->Nom?>" />
				<table width="100%" class="TitreFicheEquipe">
					<tr>
						<td><?=$UneEquipe->Nom?></td>
						<td class="TousJoueurs">Tous joueurs <input type="checkbox" name="TousJoueurs"<?=(($UneEquipe->TousJoueurs=="o")?" checked=\"checked\"":"")?> /></td>
					</tr>
				</table>
				<table class="ContenuFicheEquipe <?=$Style?>">
<?
	if ($UneEquipe->TousJoueurs=="n"){
?>				

					<tr>
						<td colspan="3" class="SousTitreEquipe">Liste des membres composant l'équipe (<?=count($UneEquipe->Joueurs)?>)</td>
					</tr>
<?
		foreach ($Joueurs as $UnJoueur){
			if ($UneEquipe->faisPartie($UnJoueur->Pseudonyme)){
?>				



					<tr>
						<?if (($UnJoueur->NumeroLicence).length > 0) {?>
							<td><?=$UnJoueur->Prenom." ".$UnJoueur->Nom." (".$UnJoueur->NumeroLicence.")"?></td>
						<?}
						else {?>
							<td><?=$UnJoueur->Prenom." ".$UnJoueur->Nom?></td>
						<?}?>
						<td> L'enlever
							<input type="checkbox" name="EnleveMembre<?=$UnJoueur->Pseudonyme?>"<?=((($UneEquipe->Responsable==$UnJoueur->Pseudonyme)||($UneEquipe->Supleant==$UnJoueur->Pseudonyme))?" disabled=\"disabled\"":"")?> />
						</td>
						
					</tr>
<?			
			}				 
		}
?>

					<tr>
						<td>Ajouter</td>
						<td>
							<select name="AjouteMembre">
<?
		print("\t\t\t\t\t\t\t\t<option value=\"\"></option>");
		foreach ($Joueurs as $UnJoueur){	
			if (!$UneEquipe->faisPartie($UnJoueur->Pseudonyme)) print("<option value=\"".$UnJoueur->Pseudonyme."\">".$UnJoueur->Prenom." ".$UnJoueur->Nom."</option>");		
		}
?>

							</select>
						</td>
					</tr>
					<tr>
						<td colspan="3"><hr/></td>
					</tr>
<?
	}
?>				
					<tr>
						<td colspan="3" class="SousTitreEquipe">Présence et responsables</td>
					</tr>
					<tr>
						<td>Responsable</td>
						<td>
							<select name="Responsable">
<?
		print("\t\t\t\t\t\t\t\t<option value=\"\"></option>");
		foreach ($Joueurs as $UnJoueur){
			if ($UneEquipe->faisPartie($UnJoueur->Pseudonyme)) print("<option value=\"".$UnJoueur->Pseudonyme."\"".(($UneEquipe->Responsable==$UnJoueur->Pseudonyme)?" selected=\"selected\"":"").">".$UnJoueur->Prenom." ".$UnJoueur->Nom."</option>");
		}
?>

							</select>
						</td>
						<td class="PresenceParDefaut">Présence par défaut <input type="checkbox" name="PresenceDefaut"<?=(($UneEquipe->PresenceDefaut=="o")?" checked=\"checked\"":"")?> /></td>
					</tr>
					<tr>
						<td>Suppléant</td>
						<td>
							<select name="Supleant">
<?
		print("\t\t\t\t\t\t\t\t<option value=\"\"></option>");
		foreach ($Joueurs as $UnJoueur){
			if ($UneEquipe->faisPartie($UnJoueur->Pseudonyme)) print("<option value=\"".$UnJoueur->Pseudonyme."\"".(($UneEquipe->Supleant==$UnJoueur->Pseudonyme)?" selected=\"selected\"":"").">".$UnJoueur->Prenom." ".$UnJoueur->Nom."</option>");
		}
?>
				
							</select>
						</td>
						<td class="BoutonAction"><input type="submit" value="Changer" class="PetitBouton Action" /></td>
					</tr>
				</table>
				</div>				
			</form>
		</td>
	</tr>
<?
}
?>
	</table>

<div class="Explications">
	<a href="#HautDePage">Haut de page</a><br/>
</div>


