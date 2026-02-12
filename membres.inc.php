<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}
if (!$Joueur){ require("accueil.inc.php"); return;}



//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! Le joueur doit pouvoir changer son mot de passe !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!


//******************
//** Modif de la fiche
//******************
$ResultatMAJ="";
if ($Modif=="oui"){
	$Joueurs = ChargeJoueurs("V", "Prenom, Nom");
	if ($Joueurs[$Membre]->Pseudonyme==$Joueur->Pseudonyme){
		$MAJMotDePasse="";
		if ($MembreModifCoordonnes){
			$TelephonesMAJ="";
			if (preg_match("/^[0-9]{10}$/", $Telephone1)) $TelephonesMAJ.="D".$Telephone1;
			if (preg_match("/^[0-9]{10}$/", $Telephone2)) $TelephonesMAJ.="D".$Telephone2;
			if (preg_match("/^[0-9]{10}$/", $Mobile1)) $TelephonesMAJ.="M".$Mobile1;
			if (preg_match("/^[0-9]{10}$/", $Mobile2)) $TelephonesMAJ.="M".$Mobile2;
			}
		if (($AncienMotDePasse)&&($MotDePasse1)&&($MotDePasse2)){
			if ($MotDePasse1==$MotDePasse2){
				if (mySql_fetch_object(mySql_query("SELECT * FROM NPVB_Joueurs WHERE (Pseudonyme='".$Joueur->Pseudonyme."' AND Password=OLD_PASSWORD('".$AncienMotDePasse."'))", $sdblink))){
					$MAJMotDePasse = ", Password=OLD_PASSWORD('".$MotDePasse1."')";
				}else $ResultatMAJ = "Votre mot de passe est invalide";
			}else $ResultatMAJ = "Le nouveau mot de passe est mal confirmé";
		}
		if ($MembreModifCoordonnes){
			if ($ResultatMAJ=="") $ResultatMAJ = (mySql_query("UPDATE NPVB_Joueurs SET Accord='".$Accord."', Telephones='".$TelephonesMAJ."', Email='".$Email."', Adresse='".$Adresse."', CPVille='".$CodePostal." ".$Ville."', Profession='".$Profession."'".$MAJMotDePasse." WHERE (Pseudonyme='".$Joueur->Pseudonyme."')", $sdblink))?"":"Erreur lors de la mise à jour";
		}else{
			if ($ResultatMAJ=="") $ResultatMAJ = (mySql_query("UPDATE NPVB_Joueurs SET Accord='".$Accord."', Pseudonyme=Pseudonyme".$MAJMotDePasse." WHERE (Pseudonyme='".$Joueur->Pseudonyme."')", $sdblink))?"":"Erreur lors de la mise à jour";
		}
		
	}
}
//******************
//** FInModif de la fiche
//******************

$Joueurs = ChargeJoueurs("V", "Prenom, Nom");
if ($MembreModifCoordonnes){
	$Joueur = $Joueurs[$Joueur->Pseudonyme];
}

$Mode = (($Joueurs[$Membre])&&($Joueur))?"Fiche":"Trombi";

?>

<div class="Explications">
	<?=($Mode=="Fiche")?"":(($Joueur)?"Cliquez sur la photo d'un membre pour voir ses renseignements":"Trombinoscope des membres de l'association")?>
</div>
	
<table id="Membres">
<?
if ($Mode=="Fiche")
{
	//******************************
	//si mode Affichage de la fiche d'un membre
	//*****************************
	if (($Joueurs[$Membre]->Accord=="o")||($Joueurs[$Membre]->Pseudonyme==$Joueur->Pseudonyme))
	{	//+++++Si le joueur a donné son accord
?>	

<h2><?=$Joueurs[$Membre]->Prenom?> <?=$Joueurs[$Membre]->Nom?></h2>

<tr>
	<td>
		<div class="UnMembre"><img src="<?=$RepertoirePhotos?>CadrePhoto.gif" class="CadrePhoto" alt=""/><img src="<?=PhotoJoueur($Joueurs[$Membre]->Pseudonyme)?>" class="PhotoMembre" alt="" /></div>
	</td>
	<td>
		<ul>
<?
		$Telephone1="";
		$Telephone2="";
		$Mobile1="";
		$Mobile2="";
		$Index=0;
		while (($TypeTel = substr($Joueurs[$Membre]->Telephones, $Index, 1))<>""){
			switch($TypeTel){
				case "D": if ($Telephone1) $Telephone2 .=  substr($Joueurs[$Membre]->Telephones, $Index+1, 10); else $Telephone1 .= substr($Joueurs[$Membre]->Telephones, $Index+1, 10);break;
				case "M": if ($Mobile1) $Mobile2 .=  substr($Joueurs[$Membre]->Telephones, $Index+1, 10); else $Mobile1 .=  substr($Joueurs[$Membre]->Telephones, $Index+1, 10);break;
				default:
			}
			$Index+=11;
		}
?>		
<?		if ($Telephone1<>"") {?>		<li><img src="<?=$RepertoireImages?>phone.svg" class="Icone" alt="telephone"/> <?=preg_replace("/[0-9]{2}/i", "\$0 ", $Telephone1)?><?=(($Telephone2<>"")?" ou ".preg_replace("/[0-9]{2}/i", "\$0 ", $Telephone2):"")?></li><?}?>
<?		if ($Mobile1<>"") {?>		<li><img src="<?=$RepertoireImages?>mobile.svg" class="Icone" alt="mobile"/> <?=preg_replace("/[0-9]{2}/i", "\$0 ", $Mobile1)?><?=(($Mobile2<>"")?" ou ".preg_replace("/[0-9]{2}/i", "\$0 ", $Mobile2):"")?></li><?}?>
<?		if ($Joueurs[$Membre]->Email) {?>		<li><img src="<?=$RepertoireImages?>email.svg" class="Icone" alt="email"/><a href="mailto:<?=$Joueurs[$Membre]->Email?>"><?=$Joueurs[$Membre]->Email?></a></li><?}?>
<?		if ($Joueurs[$Membre]->DateNaissance<>"0000-00-00") {?>			<li><b>Né<?=($Joueurs[$Membre]->Sexe=="f")?"e":""?> le</b> <?=substr($Joueurs[$Membre]->DateNaissance, 8, 2)?> <?=$montharray[(int)substr($Joueurs[$Membre]->DateNaissance, 5, 2)]?> <?=substr($Joueurs[$Membre]->DateNaissance, 0, 4)?></li><?}?>
<?		if ($Joueurs[$Membre]->Adresse<>"") {?>			<li><b>Adresse :</b> <?=$Joueurs[$Membre]->Adresse."<br/>".$Joueurs[$Membre]->CPVille?></li><?}?>
<?		if ($Joueurs[$Membre]->Profession<>"") {?>			<li><b>Profession :</b> <?=$Joueurs[$Membre]->Profession?></li><?}?>

	</ul>
	</td>
</tr>	
<?
		if (($Modif=="oui")&&($Joueurs[$Membre]->Pseudonyme==$Joueur->Pseudonyme)){
?>

<tr>	
	<td colspan="2"<?=(($ResultatMAJ=="")?" class=\"ModifOk\">Mise à jour réussie":" class=\"ModifError\">".$ResultatMAJ)?></td>
</tr>	
<?
		}
		if ($Joueurs[$Membre]->Pseudonyme==$Joueur->Pseudonyme){
?>
<tr>	
	<td colspan="2">
		<form id="formulaire" action="<?=$PHP_SELF?>" method="post">
		<input type="hidden" name="Page" value="membres">
		<input type="hidden" name="Membre" value="<?=$Joueur->Pseudonyme?>">
		<input type="hidden" name="Modif" value="oui">
		<input type="hidden" id="estDerouleFormulaireMembre" name="estDerouleFormulaireMembre" value="n"/>
		<div id="DerouleFormulaireMembre" class="DerouleRouleau"><a href="javascript:Deroule('FormulaireMembre');" class="<?=($Joueur->Sexe=="m")?"Garcon":"Fille"?>"><?=($MembreModifCoordonnes)?"Modifier ma fiche":"Modifier mon mot de passe"?></a></div>
		<div id="EnrouleFormulaireMembre" class="Rouleau"><a href="javascript:Enroule('FormulaireMembre');" class="<?=($Joueur->Sexe=="m")?"Garcon":"Fille"?>">Cacher les options</a></div>
		<div class="Rouleau" id="RouleauFormulaireMembre">
<?
		if ($MembreModifCoordonnes){
?>
			<br/>Accord pour diffusion : <input type="radio" name="Accord" value="o"<?=($Joueur->Accord=="o")?" checked=\"checked\"":""?>/>oui / <input type="radio" name="Accord" value="n"<?=($Joueur->Accord=="n")?" checked=\"checked\"":""?>/>non<br/><br/>
			<fieldset>
				<legend>Me contacter</legend>
				<table>
					<tr><td class="Colonne1">Telephone</td><td class="Colonne2"><input type="text" name="Telephone1" size="11" value="<?=$Telephone1?>" /> ou <input type="text" name="Telephone2" size="11" value="<?=$Telephone2?>" /></td></tr>
					<tr><td class="Colonne1">Portable</td><td class="Colonne2"><input type="text" name="Mobile1" size="11" value="<?=$Mobile1?>" /> ou <input type="text" name="Mobile2" size="11" value="<?=$Mobile2?>" /></td></tr>
					<tr><td class="Colonne1">Email</td><td class="Colonne2"><input type="text" name="Email" size="30" value="<?=$Joueurs[$Membre]->Email?>" /></td></tr>
				</table>
			</fieldset>
			<fieldset>
				<legend>Mes coordonées</legend>
				<table>
					<tr><td class="Colonne1">Adresse</td><td class="Colonne2"><input type="text" name="Adresse" size="30" value="<?=$Joueurs[$Membre]->Adresse?>" /></td></tr>
					<tr><td class="Colonne1">CodePostal</td><td class="Colonne2"><input type="text" name="CodePostal" size="6" value="<?=substr($Joueurs[$Membre]->CPVille, 0, 5)?>" /></td></tr>
					<tr><td class="Colonne1">Ville</td><td class="Colonne2"><input type="text" name="Ville" size="30" value="<?=substr($Joueurs[$Membre]->CPVille, 6)?>" /></td></tr>
				</table>
			</fieldset>
			<fieldset>
				<legend>Autres</legend>
				<table>
					<tr><td class="Colonne1">Profession</td><td class="Colonne2"><input type="text" name="Profession" size="30" value="<?=$Joueurs[$Membre]->Profession?>" /></td></tr>
				</table>
			</fieldset>
<?
		}
?>
			<fieldset>
				<legend>Changer mon mot de passe</legend>
				<table>
					<tr><td class="Colonne1">Ancien</td><td class="Colonne2"><input type="password" name="AncienMotDePasse" size="30" value="" /></td></tr>
					<tr><td class="Colonne1">Nouveau</td><td class="Colonne2"><input type="password" name="MotDePasse1" size="30" value="" /></td></tr>
					<tr><td class="Colonne1">Confirme</td><td class="Colonne2"><input type="password" name="MotDePasse2" size="30" value="" /></td></tr>
				</table>
			</fieldset>
			<input type="submit" class="Bouton <?=($Joueur->Sexe=="m")?"Garcon":"Fille"?>" value="Valider">
		</div>
		</form>
	</td>
</tr>	
<?
		}
	}else{
		//+++++Si le joueur n'a pas donné son accord
?>
<tr>
	<td>Vous n'avez pas la possibilité de voir la fiche de ce joueur</td>
</tr>
<?		
	}
}else{
	//******************************
	// sinon, on affiche le trombinoscope
	//*****************************
	$Equipes = ChargeEquipes();
	$EquipesUFOLEP = array();
	foreach($Equipes as $UneEquipe) {if ($UneEquipe->TousJoueurs=="n") $EquipesUFOLEP[]=$UneEquipe;}
?>
	<tr>
		<td colspan="<?=$NombreMembresParLigne?>" class="Filtre">
		<form action="<?=$PHP_SELF?>" method="get">
		<div>
			<input type="hidden" name="Page" value="membres" />
			Afficher les membres
			<select name="FiltreEquipe">
				<option value=""<?=(($FiltreEquipe=="")?" selected=\"selected\"":"")?>>Tous</option>
				<option value="BUREAU"<?=(($FiltreEquipe=="BUREAU")?" selected=\"selected\"":"")?>>Du Bureau</option>
<?	foreach ($EquipesUFOLEP as $UneEquipe) print("<option value=\"".$UneEquipe->Nom."\"".(($FiltreEquipe==$UneEquipe->Nom)?" selected=\"selected\"":"").">de ".$UneEquipe->Nom."</option>");?>
			</select>
			<input type="submit" value="Filtrer" class="PetitBouton Action" />
		</div>
		</form>
		</td>
	</tr>
<?
	$Compteur=1;
	$Total=0;
	$TotalMails=0;
	$Emails= array("", "", "");
	if ($FiltreEquipe=="BUREAU") $Joueurs = ChargeJoueurs("V", "Titre");
	foreach($Joueurs as $UnJoueur){
		if (strpos($UnJoueur->Nom, 'nvité') === false) {
		
		//if (($UnJoueur->Pseudonyme===="o")||($UnJoueur->Pseudonyme==$Joueur->Pseudonyme)){
			if ((($FiltreEquipe=="BUREAU")&&($UnJoueur->Titre==""))||(($FiltreEquipe<>"")&&($FiltreEquipe<>"BUREAU")&&(!$Equipes[$FiltreEquipe]->faisPartie($UnJoueur->Pseudonyme)))) continue;//
			if ($Compteur==1) print("\n\t<tr>");
			$CadrePhoto = ($UnJoueur->Pseudonyme==$Joueur->Pseudonyme)?
			"<img src=\"".$RepertoirePhotos."CadrePhotoMontre.gif\" class=\"CadrePhoto Photo\" onmouseover=\"src='".$RepertoirePhotos."CadrePhotoMontreActif.gif'\" onmouseout=\"src='".$RepertoirePhotos."CadrePhotoMontre.gif'\" alt=\"".(($Joueur)?"Cliquez pour voir votre fiche":"")."\" />":"<img src=\"".$RepertoirePhotos."CadrePhoto.".(($SupportePNG)?"png":"gif")."\" class=\"CadrePhoto Photo\" onmouseover=\"src='".$RepertoirePhotos."CadrePhotoActif.gif'\" onmouseout=\"src='".$RepertoirePhotos."CadrePhoto.".(($SupportePNG)?"png":"gif")."'\" alt=\"".(($Joueur)?"Cliquez pour accéder à sa fiche":"")."\" />";
			print("\n\t\t<td><div class=\"UnMembre ".(($UnJoueur->Sexe=="m")?"Garcon":"Fille")."\">".(($Joueur)?"<a href=\"".$PHP_SELF."?Page=membres&amp;Membre=".$UnJoueur->Pseudonyme."\">":"").$CadrePhoto."<img src=\"".PhotoJoueur($UnJoueur->Pseudonyme)."\" class=\"PhotoMembre Photo\" alt=\"\" />".(($Joueur)?"</a>":"")."<br/>".(($FiltreEquipe=="BUREAU")?substr($UnJoueur->Titre, 1):$UnJoueur->Prenom)."</div></td>");
			if ($Compteur==$NombreMembresParLigne){
				$Compteur=0;
				print("\n\t</tr>");
			}
			$Compteur++;
			$Total++;
			if ($UnJoueur->Email) 
			{
				;
				$Emails[($TotalMails % 3)] .= (($Emails[($TotalMails % 3)])?";":"").$UnJoueur->Email;
				$TotalMails++;
			}
		}
	}		
	if ($Compteur>1) print("\n\t</tr>");
?>
	<tr>
		<td colspan="<?=$NombreMembresParLigne?>" class="Filtre">
			Total: <?=$Total?>
			<br/><br />
			Mail collectif : <a href="mailto:<?=$Emails[0]?>">Liste 1</a> | <a href="mailto:<?=$Emails[1]?>">Liste 2</a> | <a href="mailto:<?=$Emails[2]?>">Liste 3</a>
			<br />
			<?=(($Total<>$TotalMails)?" (".($Total-$TotalMails)." n'".((($Total-$TotalMails)>1)?"ont":"a")." pas d'email)":"")?>
		</td>
	</tr>
<?
}
	//******************************
	// Fin sinon, on affiche le trombinoscope
	//*****************************
?>
	</table>


<div class="Explications">
	<a href="">Haut de page</a>
</div>