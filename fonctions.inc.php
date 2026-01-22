<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}
//********************************** FONCTIONS ************************************************//


function ConvertisDate($LaDate, $Vers){
	$LaDate = "".$LaDate;
	switch($Vers){
		case "PHP": $LadateChangee = mkTime(substr($LaDate, 8, 2), substr($LaDate, 10, 2), substr($LaDate, 12, 2), substr($LaDate, 4, 2), substr($LaDate, 6, 2), substr($LaDate, 0, 4));break;
		case "PHPDate": $LadateChangee = mkTime(substr($LaDate, 0, 4), substr($LaDate, 5, 2), substr($LaDate, 8, 2), 0, 0, 0);break;
		case "MySQL": $LadateChangee =  date("YmdHis", $LaDate); break;
		//case "MySQLDate": $LadateChangee =  date("YmdHis", $LaDate); break;
		default:
	}
	return $LadateChangee;
}

function PhotoJoueur($PseudonymeJoueur){
	GLOBAL $RepertoirePhotos;
	if (is_file($RepertoirePhotos."/Photo".$PseudonymeJoueur.".jpg")) return $RepertoirePhotos."/Photo".$PseudonymeJoueur.".jpg";
	else if (is_file($RepertoirePhotos."/Photo".$PseudonymeJoueur.".gif")) return $RepertoirePhotos."/Photo".$PseudonymeJoueur.".gif";
	return $RepertoirePhotos."/PhotoInconnu.gif";
	}
	
//++++Fonctions des objets
function ChargeEquipes(){
	GLOBAL $sdblink;
	$ListeJoueurs = ChargeJoueurs("V", null);
	$Equipes = array();
	$Joueurs = array();
	$DBEquipes = mySql_query("SELECT * FROM NPVB_Equipes", $sdblink);
	while ($Equipe = mySql_fetch_object($DBEquipes)){
		$Joueurs = array();
		if ($Equipe->TousJoueurs=="o"){
			//$DBJoueurs = mySql_query("SELECT Pseudonyme FROM NPVB_Joueurs WHERE (Etat='V')", $sdblink);
			//while ($Joueur = mySql_fetch_object($DBJoueurs))
			foreach ($ListeJoueurs as $Joueur){
				$Adhesion=$Joueur->Adhesion;
				if (!$Adhesion) $Adhesion="0000-00-00";
				$Joueurs[$Joueur->Pseudonyme] = ConvertisDate($Adhesion, "PHPDate")+(24*3600)-1;
			}
		}else{
			$DBJoueurs = mySql_query("SELECT Joueur FROM NPVB_Appartenance WHERE (Equipe='".$Equipe->Nom."')", $sdblink);
			while ($Joueur = mySql_fetch_object($DBJoueurs)){
				$Adhesion=$ListeJoueurs[$Joueur->Joueur]->License;
				if (!$License) $License="0000-00-00";
				$Joueurs[$Joueur->Joueur] = ConvertisDate($License, "PHPDate")+(24*3600)-1;
			}
		}
		$Equipes[$Equipe->Nom] = new Equipe($Equipe->Nom, $Equipe->Responsable, $Equipe->Supleant, $Equipe->TousJoueurs, $Equipe->PresenceDefaut, $Joueurs);
		}
	return $Equipes;
}

function ChargeJoueurs($Etat, $Tri){
	GLOBAL $sdblink;
	$Joueurs = array();
	switch ($Etat){
		case "V":
		case "I":
		case "E":
			$TextEtat = " WHERE Etat='".$Etat."'";
			break;
		default: $TextEtat="";
	}
	
	switch ($Tri){
		case "Titre":
		case "Nom":
		case "Prenom":
		case "Nom, Prenom":
		case "Prenom, Nom":
			$TextTri = " ORDER BY ".$Tri;
			break;
		default: $TextTri="";
	}
	$DBJoueurs = mySql_query("SELECT * FROM NPVB_Joueurs".$TextEtat.$TextTri, $sdblink);
	while ($Joueur = mySql_fetch_object($DBJoueurs)){
		$Joueurs[$Joueur->Pseudonyme] = $Joueur;
		}
	return $Joueurs;
}

function ChargeNews(){
	GLOBAL $sdblink;
	$News = array();
	
	/*switch ($Etat){
		case "V":
		case "I":
		case "E":
			$TextEtat = " WHERE Etat='".$Etat."'";
			break;
		default: $TextEtat="";
	}*/
	
	
	$DBNews = mySql_query("SELECT * FROM NPVB_News ORDER BY date", $sdblink);
	while ($News = mySql_fetch_object($DBNews)){
		$News[$News->id] = $News;
		}
	return $News;
}


function ChargeEvenements($Annee, $Mois, $Jour){
	//***
	//   ATTENTION, soit on spécifie l'année et le mois, soit le jour entier sous la forme AAAAMMJJ
	//***
	GLOBAL $sdblink;
	$LocalEquipes = ChargeEquipes();
	$LocalJoueurs = ChargeJoueurs("", null);
	$Evenements = array();
	$Joueurs = array();
	$MoisAvant=($Mois==1)?12:$Mois-1;
	$AnneeAvant=($Mois==1)?$Annee-1:$Annee;
	$MoisApres=($Mois==12)?1:$Mois+1;
	$AnneeApres=($Mois==12)?$Annee+1:$Annee;
	if ($MoisApres<10) $MoisApres = "0".$MoisApres;
	if ($MoisAvant<10) $MoisAvant = "0".$MoisAvant;
	if ($Mois<10) $Mois = "0".$Mois;
	if (!$Jour){
		$DBEvenements = mySql_query("SELECT * FROM NPVB_Evenements WHERE (DateHeure >= ".$AnneeAvant.$MoisAvant."23000000 AND DateHeure <= ".$AnneeApres.$MoisApres."06235959) ORDER BY DateHeure", $sdblink);
	}else{
		$DBEvenements = mySql_query("SELECT * FROM NPVB_Evenements WHERE (DateHeure >= ".$Jour."000000 AND DateHeure <= ".$Jour."235959) ORDER BY DateHeure", $sdblink);
	}
	while ($Event = mySql_fetch_object($DBEvenements)){
		$serontPresents = array();
		$etaientPresents = array();
		$TMPJoueurs = array();
		$DBJoueurs = mySql_query("SELECT * FROM NPVB_Presence WHERE (DateHeure = '".$Event->DateHeure."' AND Libelle = '".$Event->Libelle."')", $sdblink);
		//On recupere les joueurs qui ont saisi une présence pour l'event
		while ($Joueur = mySql_fetch_object($DBJoueurs))
			$TMPJoueurs[$Joueur->Joueur] = $Joueur;
		if ($LocalEquipes[$Event->Libelle]->PresenceDefaut=="o"){
			//Si l'event a PresenceDefaut=o
			//Pour chaque joueur, on regarde si son absence n'est pas renseignée et on l'ajoute
			foreach($LocalJoueurs as $Joueur){
				if (($TMPJoueurs[$Joueur->Pseudonyme]->Prevue=="o")||(($TMPJoueurs[$Joueur->Pseudonyme]->Prevue<>"n")&&($LocalEquipes[$Event->Libelle]->faisPartie($Joueur->Pseudonyme)))) $serontPresents[$Joueur->Pseudonyme]="o";
				if ($TMPJoueurs[$Joueur->Pseudonyme]->Effective<>"") $etaientPresents[$Joueur->Pseudonyme]=$TMPJoueurs[$Joueur->Pseudonyme]->Effective;
			}
		}else{
			//Si l'event a PresenceDefaut=n
			//on ajoute le joueur si sa présence est saisie
			foreach($TMPJoueurs as $PresenceJoueur){
				if ($PresenceJoueur->Prevue=="o") $serontPresents[$PresenceJoueur->Joueur]="o";
				if ($PresenceJoueur->Effective<>"") $etaientPresents[$PresenceJoueur->Joueur]=$PresenceJoueur->Effective;
			}
		}
		$Evenements[substr($Event->DateHeure, 0, 8)][substr($Event->DateHeure, 8, 4)][$Event->Libelle] = new Evenement($Event->DateHeure, $Event->Libelle, $Event->Etat, $Event->Titre, $Event->Intitule, $Event->Lieu, $Event->Adresse, $Event->Adversaire, $Event->Domicile, $Event->Resultat, $Event->Analyse, $Event->InscritsMax,$serontPresents, $etaientPresents);
		}
	return $Evenements;
	}

?>