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
	$res = mySql_query("SELECT m.Id, m.Auteur, m.Contenu, m.DateEnvoi, j.Prenom, j.Nom
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
			'moi'     => ($row->Auteur == $Joueur->Pseudonyme)
		);
	}
	echo json_encode(array('ok' => true, 'messages' => $msgs, 'nonlus' => compterNonLus($Joueur, $sdblink)));
	exit;
}

// --- Envoyer un message ---
if ($action == 'send') {
	if (!peutPosterConversation($Joueur, $conv->PosterCapacite)) {
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

// --- Supprimer un message (admin) ---
if ($action == 'delete') {
	if (!peut($Joueur, 'gerer_roles')) {
		http_response_code(403); echo json_encode(array('ok' => false, 'err' => 'Accès refusé')); exit;
	}
	$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
	mySql_query("UPDATE NPVB_MessagesChat SET Supprime='o' WHERE Id=".$id." AND Conversation=".$convId, $sdblink);
	echo json_encode(array('ok' => true));
	exit;
}

echo json_encode(array('ok' => false, 'err' => 'Action inconnue'));
