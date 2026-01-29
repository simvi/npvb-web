<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}
if ($Joueur->DieuToutPuissant=="n"){ require("accueil.inc.php"); return;}

//******************
//** Génération de lien de réinitialisation
//******************
$LienGenere = "";
$ErreurGeneration = "";

if (isset($_POST['GenererLienReset']) && $_POST['GenererLienReset'] == 'o') {
	// Bypass des validations de fiche membre pour la génération de lien
	$Modification = false;
	$PseudoMembre = isset($_POST['MembrePourReset']) ? trim($_POST['MembrePourReset']) : '';

	if ($PseudoMembre) {
		// Récupérer l'email du membre
		$queryMembre = "SELECT Email FROM NPVB_Joueurs WHERE Pseudonyme='" . mysql_real_escape_string($PseudoMembre) . "' AND Etat='V'";
		$resultMembre = mysql_query($queryMembre, $sdblink);

		if ($resultMembre && mysql_num_rows($resultMembre) > 0) {
			$rowMembre = mysql_fetch_assoc($resultMembre);
			$emailMembre = $rowMembre['Email'];

			if ($emailMembre) {
				// Générer le token (même fonction que motdepasseoublie)
				$Token = sha1(uniqid(rand(), true) . $PseudoMembre . time());

				// Dates
				$DateCreation = date('Y-m-d H:i:s');
				$DateExpiration = date('Y-m-d H:i:s', time() + (24 * 3600)); // 24h

				// IP admin
				$IpDemande = isset($_SERVER['REMOTE_ADDR']) ? mysql_real_escape_string($_SERVER['REMOTE_ADDR']) : '';
				$PseudoEscaped = mysql_real_escape_string($PseudoMembre);

				// Insérer le token
				$query = "INSERT INTO NPVB_PasswordReset (Token, Pseudonyme, DateCreation, DateExpiration, Utilise, IpDemande)
				          VALUES ('$Token', '$PseudoEscaped', '$DateCreation', '$DateExpiration', 'n', '$IpDemande')";

				if (mysql_query($query, $sdblink)) {
					// Construire le lien complet
					$LienGenere = "http://" . $_SERVER['HTTP_HOST'] . "/index.php?Page=resetmotdepasse&Token=" . $Token;
				} else {
					$ErreurGeneration = "Erreur lors de la g&eacute;n&eacute;ration du lien.";
				}
			} else {
				$ErreurGeneration = "Ce membre n'a pas d'adresse email.";
			}
		} else {
			$ErreurGeneration = "Membre introuvable.";
		}
	}
}

//******************
//** Modif de la fiche
//******************

if ((($Mode=="Modif")||($Mode=="Nouveau")) && !isset($_POST['GenererLienReset'])) {

	$Modification=true;
	
	//**************************************
	//***Tests du formulaire
	//**************************************
	
	//Test du pseudonyme
	if (!$Membre){$ErreurDonnees["Pseudonyme"] .= "Le pseudonyme est obligatoire";
	}else if (ereg("[^a-zA-Z0-9_]", $Membre)){$ErreurDonnees["Pseudonyme"] .= "Le format du pseudonyme est incorrect";
	}
	// Validation du mot de passe uniquement pour la création de compte
	if ($Mode=="Nouveau") {
		if (!$MotDePasse){
			$ErreurDonnees["Pseudonyme"] .= "Le mot de passe est obligatoire<br/>pour la cr�ation d'un compte";
		}else if (ereg("[^a-zA-Z0-9_\*\+���������\(\)\[\]=-]", $MotDePasse)){
			$ErreurDonnees["Pseudonyme"] .= "Le format du mot de passe est incorrect";
		}
	}
	
	//Test de la Civilit�
	if (!$Nom){$ErreurDonnees["Civillite"] .= "Le Nom est obligatoire<br/>";
	}else if (ereg("[^a-zA-Z0-9_\ ���������-]", $Nom)){$ErreurDonnees["Civillite"] .= "Le format du Nom est incorrect<br/>";
	}
	if (!$Prenom){$ErreurDonnees["Civillite"] .= "Le Pr�nom est obligatoire<br/>";
	}else if (ereg("[^a-zA-Z0-9_\ ���������-]", $Prenom)){$ErreurDonnees["Civillite"] .= "Le format du Pr�nom est incorrect<br/>";
	}
	if (($DateNaissance)&&($DateNaissance<>"JJ/MM/AAAA")&&(!ereg("[0-9]{2}/[0-9]{2}/[0-9]{4}", $DateNaissance))){
		$ErreurDonnees["Autres"] .= "La date de naissance est incorrecte<br/>";
		$DateNaissance = "JJ/MM/AAAA";
	}else{
		$DateNaissanceMySQL = substr($DateNaissance, 6, 4)."-".substr($DateNaissance, 3, 2)."-".substr($DateNaissance, 0, 2);
	}
	if (!$Sexe){$ErreurDonnees["Civillite"] .= "Le Sexe est obligatoire<br/>";}
	
	//Test du contact
	$Telephones="";
	if (ereg("[0-9]{10}", $Telephone1)) $Telephones.="D".$Telephone1;
	if (ereg("[0-9]{10}", $Telephone2)) $Telephones.="D".$Telephone2;
	if (ereg("[0-9]{10}", $Mobile1)) $Telephones.="M".$Mobile1;
	if (ereg("[0-9]{10}", $Mobile2)) $Telephones.="M".$Mobile2;
	if (($Telephone1)&&(!ereg("[0-9]{10}", $Telephone1))){$ErreurDonnees["Contact"] .= "Le premier num�ro de t�l�phone est incorrect<br/>";}
	if (($Telephone2)&&(!ereg("[0-9]{10}", $Telephone2))){$ErreurDonnees["Contact"] .= "Le second num�ro de t�l�phone est incorrect<br/>";}
	if (($Mobile1)&&(!ereg("[0-9]{10}", $Mobile1))){$ErreurDonnees["Contact"] .= "Le premier num�ro de mobile est incorrect<br/>";}
	if (($Mobile2)&&(!ereg("[0-9]{10}", $Mobile2))){$ErreurDonnees["Contact"] .= "Le second num�ro de mobile est incorrect<br/>";}
	if (($Email)&&(!ereg("[a-zA-Z0-9\.-]+@{1}[a-zA-Z0-9\-]+\.{1}[a-zA-Z0-9]{2,3}", $Email)))
		{$ErreurDonnees["Contact"] .= "Le format de l'email est incorrect<br/>";}
	
	//Test des Coordonn�es
	if (($Adresse)&&(ereg("[^a-zA-Z0-9_',\ ���������-]", $Adresse))){$ErreurDonnees["Coordonnees"] .= "Le format de l'adresse est incorrect<br/>";}
	if (($CodePostal)&&(!ereg("[0-9]{5}", $CodePostal))){$ErreurDonnees["Coordonnees"] .= "Le code postal est incorrect<br/>";}
	if (($Ville)&&(ereg("[^a-zA-Z0-9_\'\ ���������-]", $Ville))){$ErreurDonnees["Coordonnees"] .= "Le format de la ville est incorrect<br/>";}
	$CPVille=$CodePostal." ".$Ville;
	
	//Test des Autres
	if (($Profession)&&(ereg("[^a-zA-Z0-9_\'\ ,���������\(\)-]", $Profession))){$ErreurDonnees["Autres"] .= "Le format de la profession est incorrect<br/>";}
	
	if (($PremiereAdhesion)&&($PremiereAdhesion<>"JJ/MM/AAAA")&&(!ereg("[0-9]{2}/[0-9]{2}/[0-9]{4}", $PremiereAdhesion))){
		$ErreurDonnees["Autres"] .= "La date de premi�re adh�sion est incorrecte<br/>";
		$PremiereAdhesion = "JJ/MM/AAAA";
	}else{
		$PremiereAdhesionMySQL = substr($PremiereAdhesion, 6, 4)."-".substr($PremiereAdhesion, 3, 2)."-".substr($PremiereAdhesion, 0, 2);
	}
	
	if (($Adhesion)&&($Adhesion<>"JJ/MM/AAAA")&&(!ereg("[0-9]{2}/[0-9]{2}/[0-9]{4}", $Adhesion))){
		$ErreurDonnees["Autres"] .= "La date de l'adh�sion est incorrecte<br/>";
		$Adhesion = "JJ/MM/AAAA";
	}else{
		$AdhesionMySQL = substr($Adhesion, 6, 4)."-".substr($Adhesion, 3, 2)."-".substr($Adhesion, 0, 2);
	}
	
	if (($License)&&($License<>"JJ/MM/AAAA")&&(!ereg("[0-9]{2}/[0-9]{2}/[0-9]{4}", $License))){
		$ErreurDonnees["Autres"] .= "La date de la license est incorrecte<br/>";
		$License = "JJ/MM/AAAA";
	}else{
		$LicenseMySQL = substr($License, 6, 4)."-".substr($License, 3, 2)."-".substr($License, 0, 2);
	}
	
	/*
	if (($NumLicence)&&(!ereg("[a-z][A-Z][0-9]", $NumLicence))){
		$ErreurDonnees["Autres"] .= "Le numero de licence est incorrect<br/>";
		$NumLicence = "";
	}*/
	
	if ((!$ErreurDonnees["Pseudonyme"])&&($Mode=="Nouveau"))
		if ($RechercheJoueur = mySql_fetch_object(mySql_query("SELECT Pseudonyme FROM NPVB_Joueurs WHERE (Pseudonyme='".$Membre."')", $sdblink))) $ErreurDonnees["Pseudonyme"] = "Le Pseudonyme ".$RechercheJoueur->Pseudonyme." est d�j� utilis�";
	
	//**************************************
	//***Fin des tests du formulaire
	//**************************************
	
	if (!$ErreurDonnees){
		
		if ($Etat=="I") {
			
			/*$Telephones="";
			$Adresse="";
			$Email="";
			$CodePostal="";
			$Ville="";
			$Profession="";
			$PremiereAdhesionMySQL="";
			$PremiereAdhesion="";
			$DateNaissanceMySQL="";
			$DateNaissance="";*/
			
			$AdesionMySQL="";
			$Adesion="";
			$LicenseMySQL="";
			$License="";
			
			mySql_query("DELETE FROM NPVB_Presence WHERE (Joueur='".$Membre."' AND DateHeure>'".ConvertisDate(time(), "MySQL")."')", $sdblink);
		
		}
		
		switch ($Mode){
			
			case "Modif":
			
				if (!mySql_query("UPDATE NPVB_Joueurs SET Etat='".$Etat."', Adhesion='".$AdhesionMySQL."', Nom='".$Nom."', Prenom='".$Prenom."', Sexe='".$Sexe."', DateNaissance='".$DateNaissanceMySQL."', Profession='".$Profession."', Adresse='".$Adresse."', CPVille='".$CPVille."', Telephones='".$Telephones."', Email='".$Email."', Accord='".$Accord."', PremiereAdhesion='".$PremiereAdhesionMySQL."', License='".$LicenseMySQL."', NumeroLicence='".$NumLicence."' WHERE (Pseudonyme='".$Membre."')", $sdblink)) {
				
				 $ErreurDonnees["Enregistrement"]="Erreur d'enregistrement: ".mySql_errno($sdblink).", ".mySql_error($sdblink);
				 
				 }
				
				// Supprimé: envoi d'email de changement de mot de passe (remplacé par génération de lien) 
				
				// Supprimé: envoi d'email de création de compte avec mot de passe (remplacé par génération de lien) 
				
				break;
				
			case "Nouveau":
				//$Etat="V";
				if (mySql_query("INSERT INTO NPVB_Joueurs (Pseudonyme, Password, DieuToutPuissant, Etat, Adhesion, Nom, Prenom, Sexe, DateNaissance, Profession, Adresse, CPVille, Telephones, Email, Accord, PremiereAdhesion, License) VALUES ('".$Membre."', OLD_PASSWORD('".$MotDePasse."'), 'n','".$Etat."', '".$AdhesionMySQL."', '".$Nom."', '".$Prenom."', '".$Sexe."', '".$DateNaissanceMySQL."', '".$Profession."', '".$Adresse."', '".$CPVille."', '".$Telephones."', '".$Email."', '".$Accord."', '".$PremiereAdhesionMySQL."', '".$LicenseMySQL."')", $sdblink)){
					if (($MotDePasse)&&($Email)&&($EnvoiMail)&&(!$ErreurDonnees)) if (!mail ($Email, $SujetMailCreationCompte, $CorpsMailCreationCompte)) $ErreurDonnees["Enregistrement"]="Erreur d'envoi du mail.<br/>"; 
					$Mode="Modif";
				}else{
					 $ErreurDonnees["Enregistrement"]="Erreur d'enregistrement: ".mySql_errno($sdblink).", ".mySql_error($sdblink);
				 }
				break;
				
			default:	
		}
		// Supprimé: vérification d'envoi d'email (plus nécessaire)
		// Supprimé: vérification du mot de passe pour envoi email (plus nécessaire)

	}else{
		
		$ErreurDonnees["Enregistrement"]="Erreur dans les donn�es";
		
	}
} 
//******************
//** FinModif de la fiche
//******************

//******************
//** Modif de la Photo
//******************
if ($Mode=="EnlevePhoto"){
	$Modification=true;
	if (!is_file($RepertoirePhotos."Photo".$Membre.".jpg")) {
		$ErreurDonnees["Photo"] .= "Aucune photo � supprimer<br/>";
	}else{
		if (!unlink($RepertoirePhotos."Photo".$Membre.".jpg")) $ErreurDonnees["Photo"] .= "N'a pas r�ussi � supprimer la photo<br/>";
	}
	if($ErreurDonnees["Photo"]) $ErreurDonnees["Enregistrement"]="Erreur de suppression de la photo:</BR>";
	$Mode="Modif";
}
if ($Mode=="ModifPhoto"){
	$Modification=true;
	if (is_uploaded_file($PhotoMembre)){
		//print("<br/>".$Fichier);
		$imageValide=true;
		$paramPhoto=getImageSize($PhotoMembre);
		if ($paramPhoto[0]<>100) $ErreurDonnees["Photo"] .=	"La largeur de la photo fait ".$paramPhoto[0]." pixels<br/>";//Largeur 
		if ($paramPhoto[1]<>100) $ErreurDonnees["Photo"] .=	"La hauteur de la photo fait ".$paramPhoto[1]." pixels<br/>";	//Hauteur
		if ($paramPhoto[2]<>2) $ErreurDonnees["Photo"] .= "La photo n'est pas au format jpg<br/>";	//Type -> jpg=2
		if (!$ErreurDonnees["Photo"]){
			if(!move_uploaded_file($PhotoMembre, $RepertoirePhotos."Photo".$Membre.".jpg"))$ErreurDonnees["Photo"] = "Impossible d'enregistrer la photo<br/>";
		}
	}else{$ErreurDonnees["Photo"] = "Photo non uploadee<br/>";}
	if($ErreurDonnees["Photo"]) $ErreurDonnees["Enregistrement"]="Erreur d'envoi de la photo:</BR>";
	$Mode="Modif";
}


//******************
//** FinModif de la Photo
//******************


$Joueurs = ChargeJoueurs("", "Nom, Prenom");

if (($Mode<>"Modif")&&($Mode<>"Nouveau")) $Mode = ($Joueurs[$Membre])?"Modif":"Nouveau";

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

$Nom = str_replace("\'","'",$Nom);
$Prenom = str_replace("\'","'",$Prenom);
$Adresse = str_replace("\'","'",$Adresse);
$CPVille = str_replace("\'","'",$CPVille);
$Profession = str_replace("\'","'",$Profession);
?>

<h2><?=($Mode=="Modif")?"Modification de ".$Joueurs[$Membre]->Pseudonyme:"Cr�ation d'un nouvel utilisateur";?></h2>

<table id="Membres">
<?
if ($Modification){
	if ($ErreurDonnees["Enregistrement"]){
		print("\t<tr>\n\t\t<td><p class=\"ModifError\">".$ErreurDonnees["Enregistrement"]."</p></td>\n\t</tr>\n");
	}else{
		print("\t<tr>\n\t\t<td><p class=\"ModifOk\">Modifications effectu�es avec succ�s</p></td>\n\t</tr>\n");
	}
}

if ($ErreurDonnees["Photo"]){
	print("\t<tr>\n\t\t<td><p class=\"ModifError\">".$ErreurDonnees["Photo"]."</p></td>\n\t</tr>\n");
}
if ($EnvoiMail){
	if ($ErreurDonnees["Mail"]){
		print("\t<tr>\n\t\t<td><p class=\"ModifError\">".$ErreurDonnees["Mail"]."</p></td>\n\t</tr>\n");
	}else{
		print("\t<tr>\n\t\t<td><p class=\"ModifOk\">Email envoy� (si adresse correcte)</p></td>\n\t</tr>\n");
	}
}

?>

	<tr>
		<td>
<?
	if($Mode=="Modif"){
?>

		<table>
			<tr>
				<td valign="top">
					<div class="UnMembre"><img src="<?=$RepertoirePhotos?>CadrePhoto.gif" class="CadrePhoto" alt="" /><img src="<?=PhotoJoueur($Joueurs[$Membre]->Pseudonyme)?>" class="PhotoMembre" alt="" /></div>
				</td>
				<td>
					<fieldset>
						<legend>Changer la photo</legend>
						<form id="formulairePhoto" enctype="multipart/form-data" action="<?=$PHP_SELF?>" method="post">
						<div>
						<input type="hidden" name="Page" value="adminfichemembre" />
						<input type="hidden" name="Mode" value="ModifPhoto" />
						<input type="hidden" name="Membre" value="<?=$Membre?>" />
						<input type="hidden" name="MAX_FILE_SIZE" value="30000" />
						<table>
							<tr><td colspan="2"><input type="file" name="PhotoMembre" size="15" /></td></tr>
							<tr><td><ul><li>Format .jpg</li><li>Taille 100*100px</li></ul></td><td><input type="submit" value="Changer" class="PetitBouton Action" /><br/><input type="button" value="Enlever" onclick="javascript:document.forms['formulairePhoto'].Mode.value='EnlevePhoto';document.forms['formulairePhoto'].submit()" class="PetitBouton Annule" /></td></tr>
						</table>
						</div>
						</form>
					</fieldset>
				</td>
			</tr>
		</table>

<?
	}
?>
		<form id="formulaire" action="<?=$PHP_SELF?>" method="post">
		<div>
		<input type="hidden" name="Page" value="adminfichemembre" />
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

							<tr><td colspan="2">(Alphanum�riqe et '_' autoris�s seulement)</td></tr>

<?
	}
	$Equipes=ChargeEquipes();
	$estDansUneEquipe=false;
	foreach($Equipes as $Equipe){
		if (($Equipe->faisPartie($Membre))&&($Equipe->TousJoueurs=="n")) $estDansUneEquipe=true;
	}
	
		
?>

							<tr><td class="Colonne1">Etat du compte</td><td class="Colonne2"><select name="Etat"><option value="V"<?=(($Etat=="V")?" selected=\"selected\"":"")?>>Actif</option><?if($Mode=="Modif"){?><option value="I"<?=(($Etat=="I")?" selected=\"selected\"":"").(($estDansUneEquipe)?" disabled=\"disabled\"":"")?>>Inactif</option><?}?><option value="E"<?=(($Etat=="E")?" selected=\"selected\"":"").(($estDansUneEquipe)?" disabled=\"disabled\"":"")?>>Essai</option></select><?=(($estDansUneEquipe)?"<a href=\"javascript:alert('Vous devez supprimer le joueur de toute �quipe\\navant de le passer inactif');\">?</a>":"")?></td></tr>
<?php if ($Mode=="Modif") { ?>
							<tr>
								<td class="Colonne1">R&eacute;initialisation<br/>mot de passe</td>
								<td class="Colonne2">
									<form method="post" action="<?=$PHP_SELF?>?Page=adminfichemembre&Membre=<?=$Membre?>" style="margin:0;">
										<input type="hidden" name="MembrePourReset" value="<?=$Membre?>" />
										<input type="hidden" name="GenererLienReset" value="o" />

										<?php if ($LienGenere) { ?>
											<div style="padding:12px;background:#d4edda;border:1px solid #c3e6cb;border-radius:4px;margin-bottom:10px;">
												<strong style="color:#155724;">OK - Lien g&eacute;n&eacute;r&eacute; avec succ&egrave;s !</strong><br/>
												<small style="color:#155724;">Valable 24h - Copiez et envoyez au membre :</small><br/>
												<input type="text" value="<?=$LienGenere?>"
												       style="width:100%;margin-top:8px;padding:6px;border:1px solid #c3e6cb;font-size:11px;font-family:monospace;"
												       readonly onclick="this.select();" />
											</div>
										<?php } ?>

										<?php if ($ErreurGeneration) { ?>
											<div style="padding:10px;background:#f8d7da;border:1px solid #f5c6cb;border-radius:4px;margin-bottom:10px;color:#721c24;">
												<strong>ERREUR :</strong> <?=$ErreurGeneration?>
											</div>
										<?php } ?>

										<input type="submit" value="G&eacute;n&eacute;rer un lien de r&eacute;initialisation" class="PetitBouton Action"
										       style="background:#ff9800;border-color:#f57c00;" />
										<br/><small style="color:#666;">Le membre recevra le lien par email (à copier/coller depuis ici)</small>
									</form>
								</td>
							</tr>
<?php } else { ?>
							<tr><td class="Colonne1">Mot de passe<br/>initial</td><td class="Colonne2"><input type="password" name="MotDePasse" size="30" /><br/><small style="color:#666;">Obligatoire pour créer le compte</small></td></tr>
<?php } ?>
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
					<legend>Civilit�</legend>
					<table>
						<tr><td class="Colonne1">Nom</td><td class="Colonne2"><input type="text" name="Nom" size="30" value="<?=$Nom?>" /></td></tr>
						<tr><td class="Colonne1">Pr�nom</td><td class="Colonne2"><input type="text" name="Prenom" size="30" value="<?=$Prenom?>" /></td></tr>
						<tr><td class="Colonne1">N�<?=($Sexe=="f")?"e":""?> le</td><td class="Colonne2"><input type="text" name="DateNaissance" size="30" value="<?=$DateNaissance?>" /></td></tr>
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
						<tr><td class="Colonne1">T�l�phone</td><td class="Colonne2"><input type="text" name="Telephone1" size="11" value="<?=$Telephone1?>" /> ou <input type="text" name="Telephone2" size="11" value="<?=$Telephone2?>" /></td></tr>
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
					<legend>Coordon�es</legend>
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
						<tr><td class="Colonne1">Premi�re adh�sion le</td><td class="Colonne2"><input type="text" name="PremiereAdhesion" size="30" value="<?=$PremiereAdhesion?>" /></td></tr>
						<tr><td class="Colonne1">Adh�sion jusqu'au</td><td class="Colonne2"><input type="text" name="Adhesion" size="30" value="<?=$Adhesion?>" /></td></tr>
						<tr>
							<td class="Colonne1">Licence jusqu'au<br/>(laisser vide si pas de licence)</td>
							<td class="Colonne2"><input type="text" name="License" size="30" value="<?=$License?>" />
							</td>
						</tr>
						<tr>
							<td class="Colonne1">N� licence</td>
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

