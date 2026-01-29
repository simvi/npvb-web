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
	//   ATTENTION, soit on sp�cifie l'ann�e et le mois, soit le jour entier sous la forme AAAAMMJJ
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
		//On recupere les joueurs qui ont saisi une pr�sence pour l'event
		while ($Joueur = mySql_fetch_object($DBJoueurs))
			$TMPJoueurs[$Joueur->Joueur] = $Joueur;
		if ($LocalEquipes[$Event->Libelle]->PresenceDefaut=="o"){
			//Si l'event a PresenceDefaut=o
			//Pour chaque joueur, on regarde si son absence n'est pas renseign�e et on l'ajoute
			foreach($LocalJoueurs as $Joueur){
				if (($TMPJoueurs[$Joueur->Pseudonyme]->Prevue=="o")||(($TMPJoueurs[$Joueur->Pseudonyme]->Prevue<>"n")&&($LocalEquipes[$Event->Libelle]->faisPartie($Joueur->Pseudonyme)))) $serontPresents[$Joueur->Pseudonyme]="o";
				if ($TMPJoueurs[$Joueur->Pseudonyme]->Effective<>"") $etaientPresents[$Joueur->Pseudonyme]=$TMPJoueurs[$Joueur->Pseudonyme]->Effective;
			}
		}else{
			//Si l'event a PresenceDefaut=n
			//on ajoute le joueur si sa pr�sence est saisie
			foreach($TMPJoueurs as $PresenceJoueur){
				if ($PresenceJoueur->Prevue=="o") $serontPresents[$PresenceJoueur->Joueur]="o";
				if ($PresenceJoueur->Effective<>"") $etaientPresents[$PresenceJoueur->Joueur]=$PresenceJoueur->Effective;
			}
		}
		$Evenements[substr($Event->DateHeure, 0, 8)][substr($Event->DateHeure, 8, 4)][$Event->Libelle] = new Evenement($Event->DateHeure, $Event->Libelle, $Event->Etat, $Event->Titre, $Event->Intitule, $Event->Lieu, $Event->Adresse, $Event->Adversaire, $Event->Domicile, $Event->Resultat, $Event->Analyse, $Event->InscritsMax,$serontPresents, $etaientPresents);
		}
	return $Evenements;
	}

//********************************** FONCTIONS RESET MOT DE PASSE ************************************************//

// G�n�re un token s�curis� pour la r�initialisation de mot de passe (PHP 4 compatible)
// Utilise SHA1 avec plusieurs sources d'entropie pour maximiser la s�curit�
function GenererTokenReset($Pseudonyme) {
	// Source 1 : ID unique bas� sur le temps avec entropie suppl�mentaire
	$random1 = uniqid($Pseudonyme, true);

	// Source 2 : Nombre al�atoire (Mersenne Twister)
	$random2 = mt_rand();

	// Source 3 : Microtime pour pr�cision � la microseconde
	$random3 = microtime();

	// Source 4 : Adresse IP du demandeur (si disponible)
	$random4 = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';

	// Source 5 : User Agent (pour encore plus d'entropie)
	$random5 = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

	// Combinaison et double hachage SHA1 (40 caract�res hexad�cimaux)
	$combined = $random1 . $random2 . $random3 . $random4 . $random5;
	$token = sha1($combined);

	return $token;
}

// V�rifie si un token est valide (existe, non expir�, non utilis�)
// Retourne le pseudonyme associ� si valide, false sinon
function VerifierTokenReset($Token) {
	GLOBAL $sdblink;

	// Protection SQL injection
	$Token = mysql_real_escape_string($Token);

	// V�rification : token existe, non expir�, non utilis�, et compte toujours valide
	$query = "SELECT r.Pseudonyme
	          FROM NPVB_PasswordReset r
	          INNER JOIN NPVB_Joueurs j ON r.Pseudonyme = j.Pseudonyme
	          WHERE r.Token='$Token'
	          AND r.DateExpiration > NOW()
	          AND r.Utilise='n'
	          AND j.Etat='V'";

	$result = mysql_query($query, $sdblink);

	if ($result && mysql_num_rows($result) > 0) {
		$row = mysql_fetch_assoc($result);
		return $row['Pseudonyme'];
	}

	return false;
}

// Marque un token comme utilis� (apr�s un reset r�ussi)
function MarquerTokenUtilise($Token) {
	GLOBAL $sdblink;

	// Protection SQL injection
	$Token = mysql_real_escape_string($Token);

	$query = "UPDATE NPVB_PasswordReset
	          SET Utilise='o'
	          WHERE Token='$Token'";

	return mysql_query($query, $sdblink);
}

// Compte le nombre de demandes r�centes pour un utilisateur (anti-spam)
function CompterDemandesRecentes($Pseudonyme, $HeuresRecentes = 1) {
	GLOBAL $sdblink;

	// Protection SQL injection
	$Pseudonyme = mysql_real_escape_string($Pseudonyme);

	// Calcul de la date de d�but (X heures avant maintenant)
	$timestampDebut = time() - ($HeuresRecentes * 3600);
	$dateDebut = date('Y-m-d H:i:s', $timestampDebut);

	$query = "SELECT COUNT(*) as nb
	          FROM NPVB_PasswordReset
	          WHERE Pseudonyme='$Pseudonyme'
	          AND DateCreation >= '$dateDebut'";

	$result = mysql_query($query, $sdblink);

	if ($result) {
		$row = mysql_fetch_assoc($result);
		return intval($row['nb']);
	}

	return 0;
}

// Nettoie les tokens expir�s (appel opportuniste pour �viter l'accumulation)
function NettoyerTokensExpires() {
	GLOBAL $sdblink;

	// Supprime tous les tokens dont la date d'expiration est d�pass�e
	$query = "DELETE FROM NPVB_PasswordReset
	          WHERE DateExpiration < NOW()";

	return mysql_query($query, $sdblink);
}

// V�rifie si un email est valide (regex PHP 4 compatible)
function EmailValide($email) {
	if (!$email) return false;

	// Regex simple mais efficace pour PHP 4
	return ereg("^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$", $email);
}

// Recherche un membre par pseudonyme OU email
// Retourne un tableau avec Pseudonyme et Email si trouv�, false sinon
function RechercherMembreParIdentifiant($Identifiant) {
	GLOBAL $sdblink;

	// Protection SQL injection
	$Identifiant = mysql_real_escape_string(trim($Identifiant));

	// Recherche par pseudo OU email pour un compte valide
	$query = "SELECT Pseudonyme, Email
	          FROM NPVB_Joueurs
	          WHERE (Pseudonyme='$Identifiant' OR Email='$Identifiant')
	          AND Etat='V'";

	$result = mysql_query($query, $sdblink);

	if ($result && mysql_num_rows($result) > 0) {
		return mysql_fetch_assoc($result);
	}

	return false;
}

?>