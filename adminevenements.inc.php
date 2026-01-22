<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}
if ($Joueur->DieuToutPuissant=="n"){ require("accueil.inc.php"); return;}

$Evenements = ChargeEvenements($Annee, $Mois, null);
$Equipes = ChargeEquipes();
$Joueurs = ChargeJoueurs("V", "Nom, Prenom");


//******************
//** Recupère et teste les infos de tout le fichier
//******************
if ($Import=="oui"){
	$EvenementsImportes=array();
	if (is_uploaded_file($ImportEvenements)){
		//print("<br/>".$Fichier);
		if ($FichierImportEvenements = fopen($ImportEvenements, "r")) {
			$Ligne=-1;
			$TitresAtributs=array();
			$EvenementsImportesTMP = fGetCsv($FichierImportEvenements, 1024, ";");
			while (!feof($FichierImportEvenements)){
				for ($index=0; $index<count($EvenementsImportesTMP); $index++){
					if ($Ligne==-1){
						$TitresAtributs[$index] = trim(ereg_replace("\([^\(\)]*\)", "", $EvenementsImportesTMP[$index]));
					}else{
						$EvenementsImportes[$Ligne][$TitresAtributs[$index]] = trim(ereg_replace("–", "-", $EvenementsImportesTMP[$index]));
					}
				}
				if ($Ligne>=0){
					$Date = $EvenementsImportes[$Ligne]["Date"];
					$Heure = $EvenementsImportes[$Ligne]["Heure"];
					$DateHeure=substr($Date, 6, 4).substr($Date, 3, 2).substr($Date, 0, 2).substr($Heure, 0, 2).substr($Heure, 3, 2)."00";
					if (!ereg("[0-9]{14}", $DateHeure)) $ErreurDonnees["Donnees"] .= "La format de la date ou de l'heure à la ligne ".($Ligne+1)." est invalide<br/>".$DateHeure;
					if (!$ErreurDonnees["Donnees"]){
						if (!checkDate(substr($DateHeure, 4, 2), substr($DateHeure, 6, 2), substr($DateHeure, 0, 4))) $ErreurDonnees["Donnees"] .= "La date à la ligne ".($Ligne+1)." est invalide<br/>";
						if (((int)substr($DateHeure, 8, 2) > 23)||((int)substr($DateHeure, 8, 2) < 0)||((int)substr($DateHeure, 10, 2) > 59)||((int)substr($DateHeure, 10, 2) < 0)) $ErreurDonnees["Donnees"] .= "L'heure à la ligne ".($Ligne+1)." est invalide<br/>";
					}
					
					
					if (!$EvenementsImportes[$Ligne]["Type/Equipe"]) {$ErreurDonnees["Donnees"] .= "Le Type/Equipe obligatoire (ligne ".($Ligne+1).")<br/>";
					}else if (!$Equipes[$EvenementsImportes[$Ligne]["Type/Equipe"]]){ $ErreurDonnees["Donnees"] .= "Le Type/Equipe n'est pas reconnu (ligne ".($Ligne+1).")<br/>";}
					if (!$EvenementsImportes[$Ligne]["Titre"]){$ErreurDonnees["Donnees"] .= "Le Titre est obligatoire (ligne ".($Ligne+1).")<br/>";
					}else if (ereg($EregTexteSeulement, $EvenementsImportes[$Ligne]["Titre"])){$ErreurDonnees["Donnees"] .= "Le format du Titre est incorrect (ligne ".($Ligne+1).")<br/>";
					}
					if (!$EvenementsImportes[$Ligne]["Intitule"]){$ErreurDonnees["Donnees"] .= "L'intitule est obligatoire (ligne ".($Ligne+1).")<br/>";
					}else if (ereg($EregTexteSeulement, $EvenementsImportes[$Ligne]["Intitule"])){$ErreurDonnees["Donnees"] .= "Le format de l'intitule est incorrect (ligne ".($Ligne+1).")<br/>";
					}
					if (ereg($EregTexteComplet, $EvenementsImportes[$Ligne]["Lieu"])) $ErreurDonnees["Donnees"] .= "Le format du Lieu est incorrect (ligne ".($Ligne+1).")<br/>";
					if (ereg($EregTexteComplet, $EvenementsImportes[$Ligne]["Adresse"])) $ErreurDonnees["Donnees"] .= "Le format de l'adresse est incorrect (ligne ".($Ligne+1).")<br/>";
					if (ereg($EregTexteSeulement, $EvenementsImportes[$Ligne]["Adversaire"])) $ErreurDonnees["Donnees"] .= "Le format de l'adversaire est incorrect (ligne ".($Ligne+1).")<br/>";
					$EvenementsImportes[$Ligne]["Domicile"] = strToLower($EvenementsImportes[$Ligne]["Domicile"]);
					if (($EvenementsImportes[$Ligne]["Domicile"])&&($EvenementsImportes[$Ligne]["Domicile"]<>"n")&&($EvenementsImportes[$Ligne]["Domicile"]<>"o")) $ErreurDonnees["Donnees"] .= "Le format du Domicile est incorrect (ligne ".($Ligne+1).")<br/>";
										
					
					if ($Evenements[substr($DateHeure, 0, 8)][$EvenementsImportes[$Ligne]["Type/Equipe"]]) $ErreurDonnees["Donnees"] .= "L'événement de la ligne ".($Ligne+1)." existe déjà<br/>";
					if (!$ErreurDonnees["Donnees"]) $EvenementsImportes[$Ligne]["DateHeure"] = $DateHeure;
				}
				$Ligne++;
				$EvenementsImportesTMP = fGetCsv($FichierImportEvenements, 1024, ";");
			}
			if (!fclose($FichierImportEvenements)) $ErreurDonnees["UPLOAD"] .= "Impossible de fermer le frichier<br/>";
			if (!unlink($ImportEvenements)) $ErreurDonnees["UPLOAD"] .= "Impossible de supprimer le frichier<br/>";
		}else{$ErreurDonnees["UPLOAD"] .= "Impossible d'ouvrir le frichier<br/>";}
	}else{$ErreurDonnees["UPLOAD"] .= "Aucun fichier uploade<br/>";}
}
//******************
//** FinRecupère et teste les infos de tout le fichier
//******************


//******************
//** S'il n'y a pas d'erreur dans le fichier, enregistrer les evènements
//******************

if (($Import=="oui")&&(!$ErreurDonnees)){
	$NBChamps=9;
	foreach ($EvenementsImportes as $EvenementImporte){
		if (!mySql_query("INSERT INTO NPVB_Evenements (DateHeure, Libelle, Etat, Titre, Intitule, Lieu, Adresse, Adversaire, Domicile) VALUES ('".$EvenementImporte["DateHeure"]."', '".$EvenementImporte["Type/Equipe"]."', 'I', '".$EvenementImporte["Titre"]."', '".$EvenementImporte["Intitule"]."', '".$EvenementImporte["Lieu"]."', '".$EvenementImporte["Adresse"]."', '".$EvenementImporte["Adversaire"]."', '".$EvenementImporte["Domicile"]."')", $sdblink)) $ErreurDonnees["Enregistrement"] .= "Erreur lors de l'enregistrement: ".mySql_errno($sdblink).":<br/>".mySql_error($sdblink)."<br/>";
	}
}
$Mode="Admin";
?>
<div class="Explications">
	Cliquez sur un jour pour éditer ses événements
</div>

	

<?if ($ErreurDonnees) print("<table id=\"AdminEvenements\">");?>
<?if ($ErreurDonnees["UPLOAD"]) print("\n\t<tr><td><p class=\"ModifError\">".$ErreurDonnees["UPLOAD"]."</p></td></tr>");?>
<?if ($ErreurDonnees["Donnees"]) print("\n\t<tr><td><p class=\"ModifError\">".$ErreurDonnees["Donnees"]."</p></td></tr>");?>
<?if ($ErreurDonnees["Enregistrement"]) print("\n\t<tr><td><p class=\"ModifError\">".$ErreurDonnees["Enregistrement"]."</p></td></tr>");?>
<?
if ($Import=="oui"){
	if ($ErreurDonnees){
		print("\t<tr>\n\t\t<td class=\"ModifError\">Aucun événement n'a été importé</td>\n\t</tr>\n");
	}else{
		$NombreEvent=count($EvenementsImportes);
		print("\t<tr>\n\t\t<td class=\"ModifOk\">".$NombreEvent." événement".(($NombreEvent>1)?"s ont été importés":" a été importé")." </td>\n\t</tr>\n");
	}
}	
if ($ErreurDonnees) print("</table>\n");
require("calendrier.inc.php");
?>		

<div class="Explications">
	<a href="#HautDePage">Haut de page</a><br/>
	<form id="formulaireImport" enctype="multipart/form-data" action="<?=$PHP_SELF?>" method="post">
		<input type="hidden" name="Page" value="adminevenements" />
		<input type="hidden" name="Import" value="oui" />
		<p>Importer plusieurs événements par un fichier externe</p>
		<a href="javascript:alert('Téléchargez le document fourni et remplissez le\navec les événements à ajouter (un par ligne).\n\nVous pouver ouvrir le document avec un simple tableur(excel, OpenOffice)\nen précisant que le caractère d\'échapement est le point-virgule.\n\nEnregistrez vos modifications et importez ensuite le fichier renseigné.')">Comment faire?</a>
		<a href="Documents/Evenements.Import.csv">Le Modèle</a>
		<input type="hidden" name="MAX_FILE_SIZE" value="30000" />
		<input type="file" name="ImportEvenements" />
		<input type="submit" value="Importer" class="Action"/>
	</form>
</div>

