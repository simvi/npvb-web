<?

//A utiliser!!!
//htmspecialchars();


if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}
if ($Joueur->DieuToutPuissant=="n"){ require("accueil.inc.php"); return;}

		
//******************************************************************
//************ Effectue les modifications demandées
//******************************************************************
//$Joueurs = ChargeJoueurs("V", "Nom, Prenom");
$Equipes = ChargeEquipes();


if ($ModeModif){
	
	$Evenements = ChargeEvenements(null, null, $Jour);
	
	//reconstitue la dateheure et la teste
	$NouvelleDateHeure=substr($Date, 6, 4).substr($Date, 3, 2).substr($Date, 0, 2).substr($Heure, 0, 2).substr($Heure, 3, 2)."00";
	if (!preg_match("/^[0-9]{14}$/", $NouvelleDateHeure)) $ErreurDonnees["DateHeure"] .= "La format de la date est invalide<br/>";
	if (!$ErreurDonnees["DateHeure"]){
		if (!checkDate(substr($NouvelleDateHeure, 4, 2), substr($NouvelleDateHeure, 6, 2), substr($NouvelleDateHeure, 0, 4))) $ErreurDonnees["DateHeure"] .= "La date est invalide<br/>";
		if (((int)substr($NouvelleDateHeure, 8, 2) > 23)||((int)substr($NouvelleDateHeure, 8, 2) < 0)||((int)substr($NouvelleDateHeure, 10, 2) > 59)||((int)substr($NouvelleDateHeure, 10, 2) < 0)) $ErreurDonnees["DateHeure"] .= "L'heure est invalide<br/>";
	}
	
	switch ($ModeModif){
		case "Nouveau":
			if ($ErreurDonnees["DateHeure"]) $ErreurDonnees["Nouveau"] = $ErreurDonnees["DateHeure"];
			if (!$Equipes[$Equipe]) $ErreurDonnees["Nouveau"] .= "Le Type/Equipe est nécéssaire à la création d'un événement<br/>";
			if ($ErreurDonnees["Nouveau"]) break;
			$Modification=true;
			
			//Ajoute l'event
			if (!mySql_query("INSERT INTO NPVB_Evenements (DateHeure, Libelle, Etat, Titre, InscritsMax) VALUES ('".$NouvelleDateHeure."', '".$Equipe."', 'I', '".$Equipe."', '".$InscritsMax."')", $sdblink)) $ErreurDonnees["Enregistrement"] .= "Erreur lors de l'enregistrement: ".mySql_errno($sdblink).":<br/>".mySql_error($sdblink)."<br/>";
			break;
		case "Modif":
			if ($ErreurDonnees["DateHeure"]) $ErreurDonnees["Modif"] = $ErreurDonnees["DateHeure"];
			if (!$Equipes[$Equipe]) $ErreurDonnees["Modif"] .= "Le Type/Equipe obligatoire<br/>";
			if (!$Titre){$ErreurDonnees["Modif"] .= "Le Titre est obligatoire<br/>";
			}else if (preg_match($EregTexteSeulement, $Titre)){$ErreurDonnees["Modif"] .= "Le format du Titre est incorrect<br/>";
			}
			if (!$Intitule){$ErreurDonnees["Modif"] .= "L'intitule est obligatoire<br/>";
			}else if (preg_match($EregTexteSeulement, $Intitule)){$ErreurDonnees["Modif"] .= "Le format de l'intitule est incorrect<br/>";
			}
			if (preg_match($EregTexteComplet, $MotifAnnulation)) $ErreurDonnees["Modif"] .= "Le format du motif d'annulation est incorrect<br/>";
			if (preg_match($EregTexteComplet, $Lieu)) $ErreurDonnees["Modif"] .= "Le format du Lieu est incorrect<br/>";
			if (preg_match($EregTexteComplet, $Adresse)) $ErreurDonnees["Modif"] .= "Le format de l'adresse est incorrect<br/>";
			if (preg_match($EregTexteSeulement, $Adversaire)) $ErreurDonnees["Modif"] .= "Le format de l'adversaire est incorrect<br/>";
			if (($Domicile)&&($Domicile<>"n")&&($Domicile<>"o")) $ErreurDonnees["Modif"] .= "Le format du Domicile est incorrect<br/>";
			if (!preg_match("/^([0-9]{1,2}\/[0-9]{1,2}\|?)*$/", $Resultat)) $ErreurDonnees["Modif"] .= "Le format du Résultat est incorrect<br/>";
			$Analyse = str_replace(array("<", ">"), array("&lt;", "&gt;"), $Analyse);
			if (preg_match($EregTexteComplet, $Analyse)) $ErreurDonnees["Modif"] .= "Le format de l'analyse est incorrect<br/>";
			if ($ErreurDonnees["Modif"]) break;
			$Modification=true;
			
			//Modif de l'event
			$Requete = "UPDATE NPVB_Evenements SET";
			$Requete .= " DateHeure='".$NouvelleDateHeure."'";
			if ($Equipe<>$Libelle) $Requete .= ", Libelle='".$Equipe."'";
			if ($Etat=="A") $Requete .= ", Etat='".$Etat.$MotifAnnulation."'";
			else if ($Evenements[substr($DateHeure, 0, 8)][substr($DateHeure, 8, 4)][$Libelle]->Etat<>$Etat) $Requete .= ", Etat='".$Etat."'";
			if ($Evenements[substr($DateHeure, 0, 8)][substr($DateHeure, 8, 4)][$Libelle]->Titre<>$Titre) $Requete .= ", Titre='".$Titre."'";
			if ($Evenements[substr($DateHeure, 0, 8)][substr($DateHeure, 8, 4)][$Libelle]->Intitule<>$Intitule) $Requete .= ", Intitule='".$Intitule."'";
			if ($Evenements[substr($DateHeure, 0, 8)][substr($DateHeure, 8, 4)][$Libelle]->Lieu<>$Lieu) $Requete .= ", Lieu='".$Lieu."'";
			if ($Evenements[substr($DateHeure, 0, 8)][substr($DateHeure, 8, 4)][$Libelle]->Adresse<>$Adresse) $Requete .= ", Adresse='".$Adresse."'";
			if ($Evenements[substr($DateHeure, 0, 8)][substr($DateHeure, 8, 4)][$Libelle]->Adversaire<>$Adversaire) $Requete .= ", Adversaire='".$Adversaire."'";
			if ($Evenements[substr($DateHeure, 0, 8)][substr($DateHeure, 8, 4)][$Libelle]->Domicile<>$Domicile) $Requete .= ", Domicile='".$Domicile."'";
			if ($Evenements[substr($DateHeure, 0, 8)][substr($DateHeure, 8, 4)][$Libelle]->Analyse<>$Analyse) $Requete .= ", Analyse='".$Analyse."'";
			$Requete .= ", InscritsMax='".$InscritsMax."'";			
			
			$Requete .= " WHERE (DateHeure='".$DateHeure."' AND Libelle='".$Libelle."')";
			
			if (!mySql_query($Requete, $sdblink)) $ErreurDonnees["Enregistrement"] .= "Erreur d'enregistrement: ".mySql_errno($sdblink).":<br/>".mySql_error($sdblink)."<br/>";
			if ((is_uploaded_file($ReleveFNP))&&(!$ErreurDonnees))
			{
				if (substr($ReleveFNP_name, strRpos($ReleveFNP_name, ".")+1)<>"xls")
				{
					$ErreurDonnees["Enregistrement"] = "Le fichier n'est pas un fichier .xls <br/>(extension en minuscule obligatoire)<br/>";
				}else{
					if(!move_uploaded_file($ReleveFNP, $RepertoireRelevesFNP."FNP_".$NouvelleDateHeure."_".$Equipe.".xls")) $ErreurDonnees["Enregistrement"] = "Impossible de copier le relevé FNP<br/>";
				}
			}else if ($SupprimeReleveFNP=="o")			{
				if (!unlink($RepertoireRelevesFNP."FNP_".$NouvelleDateHeure."_".$Equipe.".xls")) $ErreurDonnees["Enregistrement"] = "Impossible de supprimer le relevé FNP<br/>";
			}
			break;
		case "Supprime":
			if ($ErreurDonnees["DateHeure"]) $ErreurDonnees["Supprime"] = $ErreurDonnees["DateHeure"];
			if ($ErreurDonnees["Supprime"]) break;
			$Modification=true;
			if (!mySql_query("DELETE FROM NPVB_Evenements WHERE DateHeure='".$DateHeure."' AND Libelle='".$Libelle."'", $sdblink)) $ErreurDonnees["Enregistrement"] .= "Erreur d'enregistrement: ".mySql_errno($sdblink).":<br/>".mySql_error($sdblink)."<br/>";
			if (!mySql_query("DELETE FROM NPVB_Presence WHERE DateHeure='".$DateHeure."' AND Libelle='".$Libelle."'", $sdblink)) $ErreurDonnees["Enregistrement"] .= "Erreur d'enregistrement: ".mySql_errno($sdblink).":<br/>".mySql_error($sdblink)."<br/>";
			break;
		default:
		$Modification=true;
		$ErreurDonnees["Enregistrement"] .= "Pas compris";
	}
}


//******************************************************************
//************ Charge les données et affiche la page
//******************************************************************
$Evenements = ChargeEvenements(null, null, $Jour);

?>
<script type="text/javascript">

function ConfirmSupprime(NomFormulaire, Evenement){
	if (confirm("Voulez vous vraiment supprimer\n"+Evenement)){
		eval ("document.forms[\"form"+NomFormulaire+"\"].ModeModif.value=\"Supprime\";");
		eval ("document.forms[\"form"+NomFormulaire+"\"].submit();");
	}
}
</script>

<h2>Le <?=substr($Jour, 6, 2)?> <?=$montharray[(int)substr($Jour, 4, 2)]?> <?=substr($Jour, 0, 4)?></h2>
<form action="<?=$PHP_SELF?>" method="get">
	<input type="hidden" name="Page" value="<?=$Page?>" />
	<input type="hidden" name="Jour" value="<?=$Jour?>" />
	<input type="hidden" name="Mois" value="<?=$Mois?>" />
	<input type="hidden" name="Annee" value="<?=$Annee?>" />
	<input type="hidden" name="ModeModif" value="Nouveau" />
	<b>Ajout d'un événement</b><select name="Equipe"><option value=""></option><?foreach ($Equipes as $Equipe){?><option value="<?=$Equipe->Nom?>"><?=$Equipe->Nom?></option><?}?></select>
	Date <input type="text" value="<?=substr($Jour, 6, 2)."/".substr($Jour, 4, 2)."/".substr($Jour, 0, 4)?>" size="10"  maxlength="10" disabled="disabled" /><input type="hidden" name="Date" value="<?=substr($Jour, 6, 2)."/".substr($Jour, 4, 2)."/".substr($Jour, 0, 4)?>"/>
	Heure <input type="text" name="Heure" value="20:00" size="5"  maxlength="5" />
	<input type="submit" value="Ajouter" class="Action" />
</form>

<table id="Jour">
<?
if ($Modification){
	if ($ErreurDonnees["Enregistrement"]){
		print("\t<tr>\n\t\t<td class=\"ModifError\">".$ErreurDonnees["Enregistrement"]."</td>\n\t</tr>\n");
	}else{
		print("\t<tr>\n\t\t<td class=\"ModifOk\">Modifications effectuées avec succès</td>\n\t</tr>\n");
	}
}	
if ($ErreurDonnees["Nouveau"]){
	print("\t<tr>\n\t\t<td class=\"ModifError\">".$ErreurDonnees["Nouveau"]."</td>\n\t</tr>\n");
}
if ($ErreurDonnees["Modif"]){
	print("\t<tr>\n\t\t<td class=\"ModifError\">".$ErreurDonnees["Modif"]."</td>\n\t</tr>\n");
}
?>
<?
if ($Evenements[$Jour]){

	/********************** Il y a bien au moins un evènement ce jour **********************/

	$EtatsEvent = array("I"=>"Initialise", "O"=>"Ouvert", "F"=>"Fermé", "T"=>"Terminé", "A"=>"Annule");

	foreach ($Evenements[$Jour] as $HeureKey=>$HeureEvent){
		foreach ($Evenements[$Jour][$HeureKey] as $Key=>$Event){
			$LaDate = ConvertisDate($Event->DateHeure, "PHP");
			$Date = date("d/m/Y", $LaDate);
			$Heure = date("H:i", $LaDate);
			if (($Event->Libelle=="ASSO")||($Event->Libelle=="SEANCE")){
				$Style=ucFirst(strToLower($Event->Libelle));
			}else{
				$Style="UneRencontre";
			}
			if (!(($ModeModif=="Modif")&&($DateHeure==$Event->DateHeure)&&($Libelle==$Event->Libelle)&&($ErreurDonnees))){
				$Titre = $Event->Titre;
				$Intitule = $Event->Intitule;
				$Lieu = $Event->Lieu;
				$Adresse = $Event->Adresse;
				$Adversaire = $Event->Adversaire;
				$Domicile = $Event->Domicile;
				$Analyse = $Event->Analyse;
				$InscritsMax = $Event->InscritsMax;
			}
			$Titre = str_replace("\'","'",$Titre);
			$Intitule = str_replace("\'","'",$Intitule);
			$Lieu = str_replace("\'","'",$Lieu);
			$Adresse = str_replace("\'","'",$Adresse);
			$Adversaire = str_replace("\'","'",$Adversaire);
			$Domicile = str_replace("\'","'",$Domicile);
			$Analyse = str_replace("\'","'",$Analyse);
?>
	<tr>
		<td class="FicheEvent <?=$Style?>">
		<form id="form<?=$Event->DateHeure?><?=$Event->Libelle?>"enctype="multipart/form-data" action="<?=$PHP_SELF?>" method="post">
		<div>
		<input type="hidden" name="Page" value="<?=$Page?>" />
		<input type="hidden" name="Jour" value="<?=$Jour?>" />
		<input type="hidden" name="Mois" value="<?=$Mois?>" />
		<input type="hidden" name="Annee" value="<?=$Annee?>" />
		<input type="hidden" name="MAX_FILE_SIZE" value="30000" />
		<input type="hidden" name="ModeModif" value="Modif" />
		<input type="hidden" name="Libelle" value="<?=$Event->Libelle?>" />
		<input type="hidden" name="DateHeure" value="<?=$Event->DateHeure?>" />
		<p>
		Type / Equipe <select<?=(($Event->Etat<>"I")?" disabled=\"disabled\"":" name=\"Equipe\"")?>><?foreach ($Equipes as $Equipe){?><option value="<?=$Equipe->Nom?>"<?=(($Event->Libelle==$Equipe->Nom)?" selected=\"selected\"":"")?>><?=$Equipe->Nom?></option><?}?></select>
		Date <input type="text" value="<?=$Date?>" size="10"  maxlength="10"<?=(($Event->Etat<>"I")?" disabled=\"disabled\"":" name=\"Date\"")?> />
		Heure <input type="text" value="<?=$Heure?>" size="5"  maxlength="5"<?=(($Event->Etat<>"I")?" disabled=\"disabled\"":"  name=\"Heure\"")?> />
		Etat <select name="Etat"><?foreach ($EtatsEvent as $ValEtatEvent=>$EtatEvent){{?><option value="<?=$ValEtatEvent?>"<?=((substr($Event->Etat, 0, 1)==$ValEtatEvent)?" selected=\"selected\"":"")?><?=((($ValEtatEvent=="I")&&($Event->Etat<>"I"))?" disabled=\"disabled\"":"")?>><?=$EtatEvent?></option><?}}?></select>
		<input type="button" value="Supprimer" onclick="ConfirmSupprime('<?=$Event->DateHeure?><?=$Event->Libelle?>', 'de type <?=$Titre?> le <?=$Date?> à <?=$Heure?>')" class="Bouton Annule" />
		<input type="submit" value="Modifier" class="Bouton Action" />
		<?if($Event->Etat<>"I"){?>		<input type="hidden" name="Equipe" value="<?=$Event->Libelle?>" /><input type="hidden" name="Date" value="<?=$Date?>" /><input type="hidden" name="Heure" value="<?=$Heure?>" /><?}?>
		</p>
		<table>
<?
			if (substr($Event->Etat, 0, 1)=="A"){
?>
			<tr>
				<td colspan="8">Motif d'annulation <input type="text" name="MotifAnnulation" value="<?=(substr($Event->Etat, 1))?>" size="30" maxlength="21" /></td>
			</tr>
<?
			}
?>
			<tr><td>Titre</td><td><input type="text" name="Titre" value="<?=$Titre?>" size="30" maxlength="9" /></td></tr>
			<tr><td>Intitule</td><td><input type="text" name="Intitule" value="<?=$Intitule?>" size="30" maxlength="30" /></td></tr>
			<tr><td>Lieu</td><td><input type="text" name="Lieu" value="<?=$Lieu?>" size="30" maxlength="30" /></td></tr>
			<tr><td>Adresse</td><td><input type="text" name="Adresse" value="<?=$Adresse?>" size="30" maxlength="50" /></td></tr>
<?
			if (($Event->Libelle<>"ASSO")&&($Event->Libelle<>"SEANCE")){
?>
			<tr><td>Adversaire</td><td><input type="text" name="Adversaire" value="<?=$Adversaire?>" size="30" maxlength="30" /></td></tr>
			<tr><td>Rencontre à</td><td><input type="radio" name="Domicile" value="o"<?=(($Domicile=="o")?" checked=\"checked\"":"")?> /> Domicile<br/><input type="radio" name="Domicile" value="n"<?=(($Domicile=="n")?" checked=\"checked\"":"")?> /> L'extérieur</td></tr>
<?
			}
?>
		
			<tr><td>Commentaire</td><td><textarea name="Analyse" cols="35" rows="5"><?=$Analyse?></textarea></td></tr>

<?
			if (($Event->Libelle == "SEANCE")){
?>
			<tr><td>Places disponibles</td><td><textarea name="InscritsMax" cols="35" rows="2"><?=$InscritsMax?></textarea></td></tr>
<?
			}
?>
	
			<tr>
				
<?
			if (($Event->Libelle<>"ASSO")&&($Event->Libelle<>"SEANCE"))
			{
				if (!is_file($RepertoireRelevesFNP."FNP_".$Event->DateHeure."_".$Event->Libelle.".xls"))
				{				
					print("<td>Relevé FNP</td><td><input type=\"file\" name=\"ReleveFNP\" size=\"15\" /></td>");
			 	}else{
				 	print("<td>Supprimer le relevé FNP</td><td><input type=\"checkbox\" name=\"SupprimeReleveFNP\" value=\"o\" /></td>");
			 	}
			}
?>
				</td>
			</tr>
		</table>
		</div>
		</form>
		
		</td>
	</tr>
<?
		}
	}
}else{
	/********************** Aucun événement ce jour **********************/
?>
	<tr>
		<td class="AucunEvenement">Pas d'événement ce jour</td>
	</tr>
<?
}
?>
</table>

<div class="Explications">
	<a href="#HautDePage">Haut de page</a><br/>
</div>
