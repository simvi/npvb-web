<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}
if ($Joueur->DieuToutPuissant=="n"){ require("accueil.inc.php"); return;}



switch ($Etat){
	case "T": $Joueurs = ChargeJoueurs("", "Nom, Prenom"); break;
	case "E": $Joueurs = ChargeJoueurs("E", "Nom, Prenom"); break;
	case "I": $Joueurs = ChargeJoueurs("I", "Nom, Prenom"); break;
	default: $Joueurs = ChargeJoueurs("V", "Nom, Prenom"); $Etat="V";
}

$LettresNoms = array("A"=>false, "B"=>false, "C"=>false, "D"=>false, "E"=>false, "F"=>false, "G"=>false, "H"=>false, "I"=>false, "J"=>false, "K"=>false, "L"=>false, "M"=>false, "N"=>false, "O"=>false, "P"=>false, "Q"=>false, "R"=>false, "S"=>false, "T"=>false, "U"=>false, "V"=>false, "W"=>false, "X"=>false, "Y"=>false, "Z"=>false);
foreach($Joueurs as $UnJoueur){
	$LettresNoms[strToUpper(substr($UnJoueur->Nom, 0, 1))]=true;
	}

$LiensLettres="";
foreach($LettresNoms as $Lettre=>$Presente){
	$LiensLettres .= (($LiensLettres)?(($Lettre=="N")?"<br/>":" - "):"").(($Presente)?"<a href=\"#Lettre".$Lettre."\">".$Lettre."</a>":$Lettre);
}
?>

<form action="<?=$PHP_SELF?>" method="get">
	<b>Ajout d'un nouveau membre</b>
	<input type="hidden" name="Page" value="adminfichemembre" />
	<input type="submit" value="Ajouter" class="Bouton Action" />
</form>

<table id="Membres">
	<tr>
		<td class="Filtre">
			<form action="<?=$PHP_SELF?>" method="get">
				Filtre: 
				<input type="hidden" name="Page" value="adminmembres" />
				Etat
				<select name="Etat">
					<option value="T"<?=(($Etat=="T")?" selected=\"selected\"":"")?>>Tous</option>
					<option value="V"<?=(($Etat=="V")?" selected=\"selected\"":"")?>>Actif</option>
					<option value="E"<?=(($Etat=="E")?" selected=\"selected\"":"")?>>Essai</option>
					<option value="I"<?=(($Etat=="I")?" selected=\"selected\"":"")?>>Inactif</option>
				</select>
				<input type="submit" value="Filtrer" class="PetitBouton Action" />
			</form>

		</td>
	</tr>
	<tr>
		<td class="LiensLettres">
			<?=$LiensLettres?>
		</td>
	</tr>
<?
foreach($Joueurs as $UnJoueur){
	$Membre = $UnJoueur->Pseudonyme;
	if ($LettresNoms[strToUpper(substr($UnJoueur->Nom, 0, 1))]){
		$LienLettre = "<a name=\"Lettre".strToUpper(substr($UnJoueur->Nom, 0, 1))."\"></a>";
		$LettresNoms[strToUpper(substr($UnJoueur->Nom, 0, 1))] = false;
	}else{$LienLettre = "";}
?>
	<tr>
		<td>
		<?=$LienLettre?>
		
		<fieldset<?=(($UnJoueur->Etat=="I")?" class=\"Inactif\"":"")?>>
				<legend><?=$UnJoueur->Pseudonyme?></legend>
			<table>
				<tr>
					<td>
						<div class="UnMembre"><img src="<?=$RepertoirePhotos?>CadrePhoto.gif" class="CadrePhoto" alt="" /><img src="<?=PhotoJoueur($Joueurs[$Membre]->Pseudonyme)?>" class="PhotoMembre" alt="" /></div>
					</td>
					<td>
						<ul>
							<li class="TitreListe <?=($Joueurs[$Membre]->Sexe=="m")?"Garcon":"Fille"?>"><?=$Joueurs[$Membre]->Prenom?> <?=$Joueurs[$Membre]->Nom?></li>
						
<?
	$numLicence = $Joueurs[$Membre]->NumeroLicence;
	$Telephone1="";
	$Telephone2="";
	$Mobile1="";
	$Mobile2="";
	$Index=0;
	while (($TypeTel = substr($Joueurs[$Membre]->Telephones, $Index, 1))<>""){
		
		switch($TypeTel){
			case "D": if ($Telephone1) $Telephone2 .=  preg_replace("/[0-9]{2}/i", "\$0 ", substr($Joueurs[$Membre]->Telephones, $Index+1, 10)); else $Telephone1 .=  preg_replace("/[0-9]{2}/i", "\$0 ", substr($Joueurs[$Membre]->Telephones, $Index+1, 10));break;
			case "M": if ($Mobile1) $Mobile2 .=  preg_replace("/[0-9]{2}/i", "\$0 ", substr($Joueurs[$Membre]->Telephones, $Index+1, 10)); else $Mobile1 .=  preg_replace("/[0-9]{2}/i", "\$0 ", substr($Joueurs[$Membre]->Telephones, $Index+1, 10));break;
			default:
		}
		$Index+=11;
		
	}
?>		
							<li><img src="<?=$RepertoireImages?>phone.svg" class="Icone" alt="telephone" /> <?=$Telephone1?><?=(($Telephone2<>"")?" ou ".$Telephone2:"")?></li>
							<li><img src="<?=$RepertoireImages?>mobile.svg" class="Icone" alt="mobile" /> <?=$Mobile1?><?=(($Mobile2<>"")?" ou ".$Mobile2:"")?></li>
							<li><img src="<?=$RepertoireImages?>email.svg" class="Icone" alt="email" /><a href="mailto:<?=$Joueurs[$Membre]->Email?>"><?=$Joueurs[$Membre]->Email?></a></li>
						</ul>
					</td>
				</tr>
				<tr>	
					<td colspan="2" class="Colonne">
					<table>
<?	if ($Joueurs[$Membre]->DateNaissance<>"0000-00-00") {?>						<tr><td colspan="2">Né<?=($Joueurs[$Membre]->Sexe=="f")?"e":""?> le <?=substr($Joueurs[$Membre]->DateNaissance, 8, 2)?> <?=$montharray[(int)substr($Joueurs[$Membre]->DateNaissance, 5, 2)]?> <?=substr($Joueurs[$Membre]->DateNaissance, 0, 4)?></td></tr><?}?>
<?	if ($Joueurs[$Membre]->Adresse<>"") {?>						<tr><td>Adresse: </td><td><?=$Joueurs[$Membre]->Adresse."<br/>".$Joueurs[$Membre]->CPVille?></td></tr><?}?>

<?	if ($Joueurs[$Membre]->Profession<>"") {?>						

	<tr>
		<td>Profession: </td>
		<td><?=$Joueurs[$Membre]->Profession?></td>
	</tr>

<?
		}
?>

<? if (strlen($numLicence)) { ?> 

	<td>No licence: </td>
	<td><?=$numLicence?></td></tr>

<?
	
	}
?>

					</table>
					</td>
				</tr>
				<tr>	
					<td colspan="2" align="right">
						<form action="<?=$PHP_SELF?>" method="get">
							<div>
							<input type="hidden" name="Page" value="adminfichemembre" />
							<input type="hidden" name="Membre" value="<?=$Joueurs[$Membre]->Pseudonyme?>" />
							<input type="submit" value="Modifier" class="PetitBouton Action" />
							</div>
						</form>
					</td>
				</tr>
			</table>	
		</fieldset>
		</td>
	</tr>
<?
}
?>
</table>

<div  class="Explications">
	<a href="#HautDePage">Haut de page</a><br/>
</div>