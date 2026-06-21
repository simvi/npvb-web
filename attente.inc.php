<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}

// ============================================================================
// Liste d'attente des événements à places limitées (InscritsMax > 0)
//
// Quand un événement est complet, les inscriptions vont en liste d'attente
// (NPVB_ListeAttente, FIFO). Dès qu'une place se libère, le premier de la file
// est inscrit automatiquement (Prevue='o') et notifié (push + email).
//
// Socle inerte : ces fonctions ne sont déclenchées que par les hooks de
// désinscription / inscription (phases suivantes).
// ============================================================================

// Places restantes d'un événement. null = pas de limite (InscritsMax = 0).
function placesLibres($dh, $lib, $sdblink) {
	$dhe = mysql_real_escape_string($dh, $sdblink);
	$libe = mysql_real_escape_string($lib, $sdblink);
	$e = mySql_fetch_object(mySql_query("SELECT InscritsMax FROM NPVB_Evenements WHERE DateHeure='".$dhe."' AND Libelle='".$libe."'", $sdblink));
	if (!$e) return null;
	$max = (int)$e->InscritsMax;
	if ($max <= 0) return null; // pas de limite
	$c = mySql_fetch_object(mySql_query("SELECT COUNT(*) AS n FROM NPVB_Presence WHERE DateHeure='".$dhe."' AND Libelle='".$libe."' AND Prevue='o'", $sdblink));
	$inscrits = $c ? (int)$c->n : 0;
	return $max - $inscrits;
}

// Vrai si l'événement a une limite et qu'elle est atteinte.
function estComplet($dh, $lib, $sdblink) {
	$libres = placesLibres($dh, $lib, $sdblink);
	return ($libres !== null && $libres <= 0);
}

// Ajoute un joueur à la liste d'attente (idempotent).
function ajouterListeAttente($joueur, $dh, $lib, $sdblink) {
	$je = mysql_real_escape_string($joueur, $sdblink);
	$dhe = mysql_real_escape_string($dh, $sdblink);
	$libe = mysql_real_escape_string($lib, $sdblink);
	return mySql_query("INSERT IGNORE INTO NPVB_ListeAttente (Joueur, DateHeure, Libelle, DateInscription)
	                    VALUES ('".$je."', '".$dhe."', '".$libe."', NOW())", $sdblink);
}

// Retire un joueur de la liste d'attente.
function retirerListeAttente($joueur, $dh, $lib, $sdblink) {
	$je = mysql_real_escape_string($joueur, $sdblink);
	$dhe = mysql_real_escape_string($dh, $sdblink);
	$libe = mysql_real_escape_string($lib, $sdblink);
	return mySql_query("DELETE FROM NPVB_ListeAttente WHERE Joueur='".$je."' AND DateHeure='".$dhe."' AND Libelle='".$libe."'", $sdblink);
}

// Vrai si le joueur est en liste d'attente pour cet événement.
function estEnListeAttente($joueur, $dh, $lib, $sdblink) {
	$je = mysql_real_escape_string($joueur, $sdblink);
	$dhe = mysql_real_escape_string($dh, $sdblink);
	$libe = mysql_real_escape_string($lib, $sdblink);
	$r = mySql_query("SELECT 1 FROM NPVB_ListeAttente WHERE Joueur='".$je."' AND DateHeure='".$dhe."' AND Libelle='".$libe."' LIMIT 1", $sdblink);
	return ($r && mySql_num_rows($r) > 0);
}

// Nombre de personnes en liste d'attente pour cet événement.
function nbListeAttente($dh, $lib, $sdblink) {
	$dhe = mysql_real_escape_string($dh, $sdblink);
	$libe = mysql_real_escape_string($lib, $sdblink);
	$c = mySql_fetch_object(mySql_query("SELECT COUNT(*) AS n FROM NPVB_ListeAttente WHERE DateHeure='".$dhe."' AND Libelle='".$libe."'", $sdblink));
	return $c ? (int)$c->n : 0;
}

// Position (1 = prochain) du joueur dans la file, 0 s'il n'y est pas.
function positionListeAttente($joueur, $dh, $lib, $sdblink) {
	$je = mysql_real_escape_string($joueur, $sdblink);
	$dhe = mysql_real_escape_string($dh, $sdblink);
	$libe = mysql_real_escape_string($lib, $sdblink);
	$mine = mySql_fetch_object(mySql_query("SELECT DateInscription FROM NPVB_ListeAttente WHERE Joueur='".$je."' AND DateHeure='".$dhe."' AND Libelle='".$libe."'", $sdblink));
	if (!$mine) return 0;
	$de = mysql_real_escape_string($mine->DateInscription, $sdblink);
	$c = mySql_fetch_object(mySql_query("SELECT COUNT(*) AS n FROM NPVB_ListeAttente WHERE DateHeure='".$dhe."' AND Libelle='".$libe."' AND DateInscription < '".$de."'", $sdblink));
	return ($c ? (int)$c->n : 0) + 1;
}

// Promeut automatiquement le(s) premier(s) de la liste d'attente tant qu'il
// reste des places. Chaque promu est inscrit (Prevue='o'), retiré de l'attente
// et notifié (push + email). À appeler après chaque désinscription.
function promouvoirListeAttente($dh, $lib, $sdblink) {
	$dhe = mysql_real_escape_string($dh, $sdblink);
	$libe = mysql_real_escape_string($lib, $sdblink);
	$garde = 0;
	while ($garde++ < 100) {
		$libres = placesLibres($dh, $lib, $sdblink);
		if ($libres === null || $libres <= 0) break; // pas de limite, ou plein
		$r = mySql_query("SELECT Joueur FROM NPVB_ListeAttente WHERE DateHeure='".$dhe."' AND Libelle='".$libe."' ORDER BY DateInscription ASC LIMIT 1", $sdblink);
		if (!$r || mySql_num_rows($r) == 0) break; // file vide
		$row = mySql_fetch_object($r);
		$pseudo = $row->Joueur;
		$pe = mysql_real_escape_string($pseudo, $sdblink);
		// Inscrire (Prevue='o')
		if (mySql_fetch_object(mySql_query("SELECT 1 FROM NPVB_Presence WHERE Joueur='".$pe."' AND DateHeure='".$dhe."' AND Libelle='".$libe."'", $sdblink))) {
			mySql_query("UPDATE NPVB_Presence SET Prevue='o' WHERE Joueur='".$pe."' AND DateHeure='".$dhe."' AND Libelle='".$libe."'", $sdblink);
		} else {
			mySql_query("INSERT INTO NPVB_Presence (Joueur, DateHeure, Libelle, Prevue) VALUES ('".$pe."', '".$dhe."', '".$libe."', 'o')", $sdblink);
		}
		// Retirer de la file
		mySql_query("DELETE FROM NPVB_ListeAttente WHERE Joueur='".$pe."' AND DateHeure='".$dhe."' AND Libelle='".$libe."'", $sdblink);
		// Notifier
		notifierPlaceLiberee($pseudo, $dh, $lib, $sdblink);
	}
}

// Notifie un joueur promu : push (si configuré) + email (si adresse connue).
function notifierPlaceLiberee($pseudo, $dh, $lib, $sdblink) {
	global $PasseParIndex, $config;
	$jour  = substr($dh, 6, 2)."/".substr($dh, 4, 2)."/".substr($dh, 0, 4);
	$heure = substr($dh, 8, 2)."h".substr($dh, 10, 2);
	$titre = "Place libérée";
	$corps = "Bonjour,\n\nUne place s'est libérée : vous êtes désormais inscrit(e) à l'entraînement du ".$jour." à ".$heure.".\n\nSi vous ne pouvez pas venir, pensez à vous désinscrire pour laisser la place à un autre membre.\n\nA bientôt !";

	// Push (no-op si FCM non configuré)
	include_once(dirname(__FILE__).'/push.inc.php');
	if (function_exists('envoyerPush')) {
		envoyerPush(array($pseudo), $titre, "Une place s'est libérée pour l'entraînement du ".$jour, $sdblink, array('type' => 'attente'));
	}

	// Email
	$pe = mysql_real_escape_string($pseudo, $sdblink);
	$j = mySql_fetch_object(mySql_query("SELECT Email FROM NPVB_Joueurs WHERE Pseudonyme='".$pe."'", $sdblink));
	if ($j && $j->Email) {
		include_once(dirname(__FILE__).'/smtp_gmail.inc.php');
		if (function_exists('EnvoyerEmailGmail')) {
			EnvoyerEmailGmail($j->Email, $titre, $corps);
		}
	}
}
?>
