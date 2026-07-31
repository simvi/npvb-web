<?php
header('Content-Type: application/json');

if (!isset($Joueur) || !is_object($Joueur)) {
	http_response_code(403);
	echo json_encode(array('ok' => false, 'err' => 'Non connecté'));
	exit;
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$convId = isset($_REQUEST['conv']) ? (int)$_REQUEST['conv'] : 0;

$conv = mySql_fetch_object(mySql_query("SELECT * FROM NPVB_Conversations WHERE Id=".$convId, $sdblink));
if (!$conv) { echo json_encode(array('ok' => false, 'err' => 'Conversation introuvable')); exit; }

// Accès à la conversation requis pour toute action (lecture comprise)
if (!peutAccederConversation($Joueur, $conv, $sdblink)) {
	http_response_code(403);
	echo json_encode(array('ok' => false, 'err' => 'Accès refusé'));
	exit;
}

$pseudoEcap = mysql_real_escape_string($Joueur->Pseudonyme, $sdblink);

// --- Récupérer les nouveaux messages depuis un id donné ---
if ($action == 'poll') {
	$since = isset($_REQUEST['since']) ? (int)$_REQUEST['since'] : 0;
	$res = mySql_query("SELECT m.Id, m.Auteur, m.Contenu, m.DateEnvoi, m.DateModif, j.Prenom, j.Nom
	                    FROM NPVB_MessagesChat m LEFT JOIN NPVB_Joueurs j ON j.Pseudonyme=m.Auteur
	                    WHERE m.Conversation=".$convId." AND m.Supprime='n' AND m.Id > ".$since."
	                    ORDER BY m.Id ASC", $sdblink);
	$msgs = array();
	while ($row = mySql_fetch_object($res)) {
		$nom = trim($row->Prenom.' '.$row->Nom);
		if ($nom == '') $nom = $row->Auteur;
		$msgs[] = array(
			'id'      => (int)$row->Id,
			'nom'     => $nom,
			'contenu' => $row->Contenu,
			'date'    => substr($row->DateEnvoi, 8, 2).'/'.substr($row->DateEnvoi, 5, 2).' '.substr($row->DateEnvoi, 11, 5),
			'modifie' => (!empty($row->DateModif)),
			'moi'     => ($row->Auteur == $Joueur->Pseudonyme)
		);
	}
	echo json_encode(array('ok' => true, 'messages' => $msgs, 'nonlus' => compterNonLus($Joueur, $sdblink)));
	exit;
}

// --- Envoyer un message ---
if ($action == 'send') {
	if (!peutPosterDansConv($Joueur, $conv, $sdblink)) {
		http_response_code(403); echo json_encode(array('ok' => false, 'err' => 'Accès refusé')); exit;
	}
	$contenu = isset($_POST['contenu']) ? trim($_POST['contenu']) : '';
	if ($contenu == '') { echo json_encode(array('ok' => false, 'err' => 'Message vide')); exit; }
	$c = mysql_real_escape_string($contenu, $sdblink);
	if (mySql_query("INSERT INTO NPVB_MessagesChat (Conversation, Auteur, Contenu, DateEnvoi) VALUES (".$convId.", '".$pseudoEcap."', '".$c."', NOW())", $sdblink)) {
		$newId = mysql_insert_id($sdblink);
		// Notification push aux autres membres (no-op si FCM non configuré)
		include_once('push.inc.php');
		$dest = destinatairesChat($convId, $Joueur->Pseudonyme, $sdblink);
		$titre = $conv->Nom;
		$apercu = (strlen($contenu) > 80) ? substr($contenu, 0, 77).'...' : $contenu;
		envoyerPush($dest, $titre, $apercu, $sdblink, array('conv' => $convId, 'type' => 'chat'));
		echo json_encode(array('ok' => true, 'id' => $newId));
	} else {
		echo json_encode(array('ok' => false, 'err' => 'Erreur enregistrement'));
	}
	exit;
}

// --- Marquer comme lu ---
if ($action == 'markread') {
	$lastid = isset($_REQUEST['lastid']) ? (int)$_REQUEST['lastid'] : 0;
	mySql_query("INSERT INTO NPVB_MessagesLus (Joueur, Conversation, DernierLuId)
	             VALUES ('".$pseudoEcap."', ".$convId.", ".$lastid.")
	             ON DUPLICATE KEY UPDATE DernierLuId=GREATEST(DernierLuId, ".$lastid.")", $sdblink);
	echo json_encode(array('ok' => true, 'nonlus' => compterNonLus($Joueur, $sdblink)));
	exit;
}

// --- Éditer un message (Axe 3b) ---
if ($action == 'edit') {
	$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
	$contenu = isset($_POST['contenu']) ? trim($_POST['contenu']) : '';
	if ($contenu == '') { echo json_encode(array('ok' => false, 'err' => 'Message vide')); exit; }

	$msg = mySql_fetch_object(mySql_query("SELECT Auteur, DateEnvoi, Supprime FROM NPVB_MessagesChat WHERE Id=".$id." AND Conversation=".$convId, $sdblink));
	if (!$msg || !peutEditerMessage($Joueur, $msg, $sdblink)) {
		http_response_code(403); echo json_encode(array('ok' => false, 'err' => 'Accès refusé')); exit;
	}

	$c = mysql_real_escape_string($contenu, $sdblink);
	if (mySql_query("UPDATE NPVB_MessagesChat SET Contenu='".$c."', DateModif=NOW() WHERE Id=".$id." AND Conversation=".$convId, $sdblink)) {
		echo json_encode(array('ok' => true));
	} else {
		echo json_encode(array('ok' => false, 'err' => 'Erreur enregistrement'));
	}
	exit;
}

// --- Supprimer un message (admin ou auteur) ---
if ($action == 'delete') {
	$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
	$msg = mySql_fetch_object(mySql_query("SELECT Auteur FROM NPVB_MessagesChat WHERE Id=".$id." AND Conversation=".$convId, $sdblink));
	if (!$msg || !peutSupprimerMessage($Joueur, $msg, $sdblink)) {
		http_response_code(403); echo json_encode(array('ok' => false, 'err' => 'Accès refusé')); exit;
	}
	mySql_query("UPDATE NPVB_MessagesChat SET Supprime='o' WHERE Id=".$id." AND Conversation=".$convId, $sdblink);
	echo json_encode(array('ok' => true));
	exit;
}

// --- Lecteurs d'un message épinglé (Axe 1b) ---
if ($action == 'lecteurs') {
	if (!peut($Joueur, 'gerer_roles')) {
		http_response_code(403); echo json_encode(array('ok' => false, 'err' => 'Accès refusé')); exit;
	}
	$msgId = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
	$result = lecteursMessage($convId, $msgId, $sdblink);
	$lectList = array();
	$nonLectList = array();
	foreach ($result['lu'] as $p) {
		$r = mysql_query("SELECT Prenom, Nom FROM NPVB_Joueurs WHERE Pseudonyme='".mysql_real_escape_string($p, $sdblink)."'", $sdblink);
		$j = mysql_fetch_object($r);
		$nom = $j ? trim($j->Prenom.' '.$j->Nom) : $p;
		if ($nom == '') $nom = $p;
		$lectList[] = $nom;
	}
	foreach ($result['nonlu'] as $p) {
		$r = mysql_query("SELECT Prenom, Nom FROM NPVB_Joueurs WHERE Pseudonyme='".mysql_real_escape_string($p, $sdblink)."'", $sdblink);
		$j = mysql_fetch_object($r);
		$nom = $j ? trim($j->Prenom.' '.$j->Nom) : $p;
		if ($nom == '') $nom = $p;
		$nonLectList[] = $nom;
	}
	echo json_encode(array('ok' => true, 'lu' => $lectList, 'nonlu' => $nonLectList, 'total_lu' => count($lectList), 'total' => count($lectList) + count($nonLectList)));
	exit;
}

// --- Lister les membres disponibles pour un message privé (Axe 4) ---
if ($action == 'membres') {
	$res = mySql_query("SELECT Pseudonyme, Prenom, Nom FROM NPVB_Joueurs WHERE Etat='V' AND Pseudonyme != '".$pseudoEcap."' ORDER BY Nom ASC", $sdblink);
	$membres = array();
	while ($row = mySql_fetch_object($res)) {
		$nom = trim($row->Prenom.' '.$row->Nom);
		if ($nom == '') $nom = $row->Pseudonyme;
		$membres[] = array('pseudo' => $row->Pseudonyme, 'nom' => $nom);
	}
	echo json_encode(array('ok' => true, 'membres' => $membres));
	exit;
}

echo json_encode(array('ok' => false, 'err' => 'Action inconnue'));
