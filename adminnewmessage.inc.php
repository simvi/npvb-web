<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}
if ($Joueur->DieuToutPuissant=="n"){ require("accueil.inc.php"); return;}

//******************
//** Modif du message
//******************

if (($Mode=="Modif")||($Mode=="Nouveau")) {
	
	$Modification=true;
	
	//**************************************
	//***Tests du formulaire
	//**************************************
	
	// if (($Profession)&&(ereg("[^a-zA-Z0-9_\'\ ,éêèùàâûîô\(\)-]", $Profession))){$ErreurDonnees["Autres"] .= "Le format de la profession est incorrect<br/>";}

	//**************************************
	//***Fin des tests du formulaire
	//**************************************
	
	if (!$ErreurDonnees){
		
		if ($Etat=="I") {
			
			$AdesionMySQL="";
			$Adesion="";
			$LicenseMySQL="";
			$License="";
			
			//mySql_query("DELETE FROM NPVB_News WHERE (id='".$id."' AND DateHeure>'".ConvertisDate(time(), "MySQL")."')", $sdblink);
		
		}
		
		switch ($Mode){
			
			case "Modif":
			
				if (!mySql_query("UPDATE NPVB_Joueurs SET".(($MotDePasse)?" Password=OLD_PASSWORD('".$MotDePasse."'),":"")." Etat='".$Etat."', Adhesion='".$AdhesionMySQL."', Nom='".$Nom."', Prenom='".$Prenom."', Sexe='".$Sexe."', DateNaissance='".$DateNaissanceMySQL."', Profession='".$Profession."', Adresse='".$Adresse."', CPVille='".$CPVille."', Telephones='".$Telephones."', Email='".$Email."', Accord='".$Accord."', PremiereAdhesion='".$PremiereAdhesionMySQL."', License='".$LicenseMySQL."', NumeroLicence='".$NumLicence."' WHERE (Pseudonyme='".$Membre."')", $sdblink)) {
				
				 $ErreurDonnees["Enregistrement"]="Erreur d'enregistrement: ".mySql_errno($sdblink).", ".mySql_error($sdblink);
				 
				 }
				
				if (($MotDePasse)&&($Email)&&($EnvoiMail)&&(!$ErreurDonnees)&&(!$MailCreationCompte)) if (!mail ($Email, $SujetMailModifMotDePasse, $CorpsMailModifMotDePasse)) $ErreurDonnees["Enregistrement"]="Erreur d'envoi du mail.<br/>"; 
				
				if (($MotDePasse)&&($Email)&&($EnvoiMail)&&(!$ErreurDonnees)&&($MailCreationCompte=="o")) if (!mail ($Email, $SujetMailCreationCompte, $CorpsMailCreationCompte)) $ErreurDonnees["Enregistrement"]="Erreur d'envoi du mail.<br/>"; 
				
				break;
				
			case "Nouveau":
				//$Etat="V";
				if (mySql_query("INSERT INTO NPVB_News (title, message) VALUES ('".$title."', '".$message."')", $sdblink)){
					$Mode="Modif";
				}else{
					 $ErreurDonnees["Enregistrement"]="Erreur d'enregistrement: ".mySql_errno($sdblink).", ".mySql_error($sdblink);
				 }
				break;
				
			default:	
		}
		
	}else{
		
		$ErreurDonnees["Enregistrement"]="Erreur dans les données";
		
	}
} 
//******************
//** FinModif de la fiche
//******************


$News = ChargeNews();

if (($Mode<>"Modif")&&($Mode<>"Nouveau")) $Mode = ($News[$Id])?"Modif":"Nouveau";

/*
if ($Mode=="Modif"){
	$Etat = $Joueurs[$Membre]->Etat;
	$Nom = $Joueurs[$Membre]->Nom;
	$Prenom = $Joueurs[$Membre]->Prenom;
	$DateNaissance = $Joueurs[$Membre]->DateNaissance;
	$DateNaissance = ($DateNaissance<>"0000-00-00")?substr($DateNaissance, 8, 2)."/".substr($DateNaissance, 5, 2)."/".substr($DateNaissance, 0, 4) : "JJ/MM/AAAA";;
	$Sexe = $Joueurs[$Membre]->Sexe;
	$Profession = $Joueurs[$Membre]->Profession;
	$Adresse = $Joueurs[$Membre]->Adresse;
	$CPVille = $Joueurs[$Membre]->CPVille;
	$CodePostal = substr($CPVille, 0, 5);
	$Ville = substr($CPVille, 6);
	$Telephones = $Joueurs[$Membre]->Telephones;
	$Telephone1="";
	$Telephone2="";
	$Mobile1="";
	$Mobile2="";
	$Index=0;
	while (($TypeTel = substr($Joueurs[$Membre]->Telephones, $Index, 1))<>""){
		switch($TypeTel){
			case "D": if ($Telephone1) $Telephone2 .=  substr($Joueurs[$Membre]->Telephones, $Index+1, 10); else $Telephone1 .=  substr($Joueurs[$Membre]->Telephones, $Index+1, 10);break;
			case "M": if ($Mobile1) $Mobile2 .=  substr($Joueurs[$Membre]->Telephones, $Index+1, 10); else $Mobile1 .=  substr($Joueurs[$Membre]->Telephones, $Index+1, 10);break;
			default:
		}
		$Index+=11;
	}
	$Email = $Joueurs[$Membre]->Email;
	$Accord = $Joueurs[$Membre]->Accord;
	$PremiereAdhesion = $Joueurs[$Membre]->PremiereAdhesion;
	$PremiereAdhesion = ($PremiereAdhesion<>"0000-00-00")?substr($PremiereAdhesion, 8, 2)."/".substr($PremiereAdhesion, 5, 2)."/".substr($PremiereAdhesion, 0, 4) : "JJ/MM/AAAA";
	$Adhesion = $Joueurs[$Membre]->Adhesion;
	$Adhesion = ($Adhesion<>"0000-00-00")?substr($Adhesion, 8, 2)."/".substr($Adhesion, 5, 2)."/".substr($Adhesion, 0, 4) : "JJ/MM/AAAA";
	$License = $Joueurs[$Membre]->License;
	$License = ($License<>"0000-00-00")?substr($License, 8, 2)."/".substr($License, 5, 2)."/".substr($License, 0, 4) : "JJ/MM/AAAA";
	$NumLicence = $Joueurs[$Membre]->NumeroLicence;
	
}
*/

$title = str_replace("\'","'",$title);
$message = str_replace("\'","'",$message);

?>

<h2><?=($Mode=="Modif")?"Modification de ".$News[$Id]->Pseudonyme:"Création d'un nouveau message";?></h2>

<table id="News">
<?
if ($Modification){
	if ($ErreurDonnees["Enregistrement"]){
		print("\t<tr>\n\t\t<td><p class=\"ModifError\">".$ErreurDonnees["Enregistrement"]."</p></td>\n\t</tr>\n");
	}else{
		print("\t<tr>\n\t\t<td><p class=\"ModifOk\">Modifications effectuées avec succès</p></td>\n\t</tr>\n");
	}
}

?>

	<tr>
		<td>

		<form id="formulaire" action="<?=$PHP_SELF?>" method="post">
		<div>
		<input type="hidden" name="Page" value="adminnewmessage" />
		<input type="hidden" name="Mode" value="<?=$Mode?>" />
		<table>
			<tr>
				<td class="Colonne">
<?
if ($ErreurDonnees["Pseudonyme"]){
	print("			<p class=\"ModifError\">".$ErreurDonnees["Pseudonyme"]."</p>");
}
?>

					<fieldset>
						<legend>Compte</legend>
						<table>
							<tr><td class="Colonne1">Pseudonyme</td><td class="Colonne2"><input type="text"<?=($Mode=="Modif")?"":" name=\"Membre\""?> size="30" value="<?=$Membre?>" <?=($Mode=="Modif")?" disabled=\"disabled\" /><input type=\"hidden\" name=\"Membre\" value=\"".$Membre."\" ":""?> /></td></tr>
<?
	if($Mode=="Nouveau"){
?>

							<tr><td colspan="2">(Alphanumériqe et '_' autorisés seulement)</td></tr>

<?
	}
	$Equipes=ChargeEquipes();
	$estDansUneEquipe=false;
	foreach($Equipes as $Equipe){
		if (($Equipe->faisPartie($Membre))&&($Equipe->TousJoueurs=="n")) $estDansUneEquipe=true;
	}
	
		
?>

							<tr><td class="Colonne1">Etat du compte</td><td class="Colonne2"><select name="Etat"><option value="V"<?=(($Etat=="V")?" selected=\"selected\"":"")?>>Actif</option><?if($Mode=="Modif"){?><option value="I"<?=(($Etat=="I")?" selected=\"selected\"":"").(($estDansUneEquipe)?" disabled=\"disabled\"":"")?>>Inactif</option><?}?><option value="E"<?=(($Etat=="E")?" selected=\"selected\"":"").(($estDansUneEquipe)?" disabled=\"disabled\"":"")?>>Essai</option></select><?=(($estDansUneEquipe)?"<a href=\"javascript:alert('Vous devez supprimer le joueur de toute équipe\\navant de le passer inactif');\">?</a>":"")?></td></tr>
							<tr><td class="Colonne1"><?=(($Mode=="Modif")?"Changer le<br/>":"")?>mot de passe</td><td class="Colonne2"><input type="password" name="MotDePasse" size="30" />
							<br/><input type="checkbox" name="EnvoiMail" value="o" />Envoyer email<?=(($Mode=="Modif")?"<br/><input type=\"checkbox\" name=\"MailCreationCompte\" value=\"o\" />(de création)":"")?></td></tr>
							<tr><td class="Colonne1">Accord pour<br/>diffusion</td><td class="Colonne2"><input type="radio" name="Accord" value="o"<?=($Accord=="o")?" checked=\"checked\"":""?>/>oui / <input type="radio" name="Accord" value="n"<?=($Accord=="n")?" checked=\"checked\"":""?>/>non</td></tr>
					</table>
					</fieldset>
				</td>
			</tr>
			<tr>	
				<td>
<?
if ($ErreurDonnees["Civillite"]){
	print("			<p class=\"ModifError\">".$ErreurDonnees["Civillite"]."</p>");
}
?>
		
				<fieldset>
					<legend>Civilité</legend>
					<table>
						<tr><td class="Colonne1">Nom</td><td class="Colonne2"><input type="text" name="Nom" size="30" value="<?=$Nom?>" /></td></tr>
						<tr><td class="Colonne1">Prénom</td><td class="Colonne2"><input type="text" name="Prenom" size="30" value="<?=$Prenom?>" /></td></tr>
						<tr><td class="Colonne1">Né<?=($Sexe=="f")?"e":""?> le</td><td class="Colonne2"><input type="text" name="DateNaissance" size="30" value="<?=$DateNaissance?>" /></td></tr>
						<tr><td class="Colonne1">Sexe</td><td class="Colonne2"> <input type="radio" name="Sexe" value="m"<?=($Sexe=="m")?" checked=\"checked\"":""?> /> Homme / <input type="radio" name="Sexe" value="f"<?=($Sexe=="f")?" checked=\"checked\"":""?> /> Femme </td></tr>
					</table>
				</fieldset>
				</td>
			</tr>
			<tr>	
				<td>
<?
if ($ErreurDonnees["Contact"]){
	print("			<p class=\"ModifError\">".$ErreurDonnees["Contact"]."</p>");
}
?>

				<fieldset>
					<legend>Contact</legend>
					<table>
						<tr><td class="Colonne1">Téléphone</td><td class="Colonne2"><input type="text" name="Telephone1" size="11" value="<?=$Telephone1?>" /> ou <input type="text" name="Telephone2" size="11" value="<?=$Telephone2?>" /></td></tr>
						<tr><td class="Colonne1">Portable</td><td class="Colonne2"><input type="text" name="Mobile1" size="11" value="<?=$Mobile1?>" /> ou <input type="text" name="Mobile2" size="11" value="<?=$Mobile2?>" /></td></tr>
						<tr><td class="Colonne1">Email</td><td class="Colonne2"><input type="text" name="Email" size="30" value="<?=$Email?>" /></td></tr>
					</table>
				</fieldset>
<?
if ($ErreurDonnees["Coordonnees"]){
	print("			<p class=\"ModifError\">".$ErreurDonnees["Coordonnees"]."</p>");
}
?>

				<fieldset>
					<legend>Coordonées</legend>
					<table>
						<tr><td class="Colonne1">Adresse</td><td class="Colonne2"><input type="text" name="Adresse" size="30" value="<?=$Adresse?>" /></td></tr>
						<tr><td class="Colonne1">CodePostal</td><td class="Colonne2"><input type="text" name="CodePostal" size="6" value="<?=$CodePostal?>" /></td></tr>
						<tr><td class="Colonne1">Ville</td><td class="Colonne2"><input type="text" name="Ville" size="30" value="<?=$Ville?>" /></td></tr>
					</table>
				</fieldset>
<?
if ($ErreurDonnees["Autres"]){
	print("			<p class=\"ModifError\">".$ErreurDonnees["Autres"]."</p>");
}
?>

				<fieldset>
					<legend>Autres</legend>
					<table>
						<tr><td class="Colonne1">Profession</td><td class="Colonne2"><input type="text" name="Profession" size="30" value="<?=$Profession?>" /></td></tr>
						<tr><td class="Colonne1">Première adhésion le</td><td class="Colonne2"><input type="text" name="PremiereAdhesion" size="30" value="<?=$PremiereAdhesion?>" /></td></tr>
						<tr><td class="Colonne1">Adhésion jusqu'au</td><td class="Colonne2"><input type="text" name="Adhesion" size="30" value="<?=$Adhesion?>" /></td></tr>
						<tr>
							<td class="Colonne1">Licence jusqu'au<br/>(laisser vide si pas de licence)</td>
							<td class="Colonne2"><input type="text" name="License" size="30" value="<?=$License?>" />
							</td>
						</tr>
						<tr>
							<td class="Colonne1">N° licence</td>
							<td class="Colonne2"><input type="text" name="NumLicence" size="30" value="<?=$NumLicence?>" />
							</td>
						</tr>
					</table>
				</fieldset>
				</td>
			</tr>
			<tr>	
				<td align="center">
					<input type="submit" class="Bouton Action" value="Valider" />
				</td>
			</tr>
		</table>
		</div>
		</form>
		</td>
	</tr>
</table>

<div  class="Explications">
	<a href="#HautDePage">Haut de page</a><br/>
</div>

