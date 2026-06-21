<?php
// ============================================================================
// Notifications push via Firebase Cloud Messaging (API HTTP v1)
//
// CONFIG (config.php) :
//   'fcm_project_id'      => 'mon-projet-firebase'
//   'fcm_service_account' => '/chemin/hors-git/service-account.json'  (clé Firebase)
//
// Tant que ces deux valeurs ne sont pas renseignées (ou le fichier absent),
// toutes les fonctions sont des no-op : l'envoi des messages chat continue
// normalement, aucune notification n'est tentée.
//
// Dépendances : ext curl + openssl (présentes sur PHP 8.4). Aucune lib externe.
// ============================================================================

// Vrai si le push est configuré et utilisable
function pushActif() {
	global $config;
	return !empty($config['fcm_project_id'])
	    && !empty($config['fcm_service_account'])
	    && is_file($config['fcm_service_account']);
}

// Enregistre/actualise le token d'un appareil pour un membre
function enregistrerAppareilPush($pseudo, $token, $plateforme, $dblink) {
	if (!$pseudo || !$token) return false;
	$p = mysql_real_escape_string($pseudo, $dblink);
	$t = mysql_real_escape_string($token, $dblink);
	$pf = ($plateforme === 'ios') ? 'ios' : 'android';
	return mysql_query("INSERT INTO NPVB_AppareilsPush (Joueur, Token, Plateforme, DateMaj)
	                    VALUES ('$p', '$t', '$pf', NOW())
	                    ON DUPLICATE KEY UPDATE Joueur='$p', Plateforme='$pf', DateMaj=NOW()", $dblink);
}

// Obtient un access token OAuth2 (Bearer) pour FCM, mis en cache fichier ~1h
function fcmAccessToken() {
	global $config;
	$sa = @json_decode(@file_get_contents($config['fcm_service_account']), true);
	if (!$sa || empty($sa['client_email']) || empty($sa['private_key'])) return null;

	$cache = sys_get_temp_dir().'/fcm_'.md5($config['fcm_project_id']).'.tok';
	if (is_file($cache)) {
		$c = @json_decode(@file_get_contents($cache), true);
		if ($c && isset($c['exp']) && $c['exp'] > time() + 60) return $c['token'];
	}

	$now = time();
	$header = base64url(json_encode(array('alg' => 'RS256', 'typ' => 'JWT')));
	$claim  = base64url(json_encode(array(
		'iss'   => $sa['client_email'],
		'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
		'aud'   => 'https://oauth2.googleapis.com/token',
		'iat'   => $now,
		'exp'   => $now + 3600
	)));
	$signature = '';
	if (!openssl_sign($header.'.'.$claim, $signature, $sa['private_key'], 'sha256')) return null;
	$jwt = $header.'.'.$claim.'.'.base64url($signature);

	$ch = curl_init('https://oauth2.googleapis.com/token');
	curl_setopt_array($ch, array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => http_build_query(array(
			'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
			'assertion'  => $jwt
		))
	));
	$resp = curl_exec($ch);
	curl_close($ch);
	$data = @json_decode($resp, true);
	if (!$data || empty($data['access_token'])) return null;

	@file_put_contents($cache, json_encode(array(
		'token' => $data['access_token'],
		'exp'   => $now + (isset($data['expires_in']) ? (int)$data['expires_in'] : 3600)
	)));
	return $data['access_token'];
}

function base64url($data) {
	return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// Envoie une notification aux appareils des membres listés.
// $pseudos : tableau de pseudonymes destinataires
// $silencieux=true : push "data-only" (réveille l'app pour sync, sans bannière)
function envoyerPush($pseudos, $titre, $corps, $dblink, $data = array(), $silencieux = false) {
	global $config;
	if (!pushActif() || empty($pseudos)) return;
	$access = fcmAccessToken();
	if (!$access) return;

	// Récupère les tokens des destinataires
	$in = array();
	foreach ($pseudos as $p) { $in[] = "'".mysql_real_escape_string($p, $dblink)."'"; }
	$res = mysql_query("SELECT Token FROM NPVB_AppareilsPush WHERE Joueur IN (".implode(',', $in).")", $dblink);
	if (!$res) return;

	$url = 'https://fcm.googleapis.com/v1/projects/'.$config['fcm_project_id'].'/messages:send';
	$dataStr = array();
	foreach ($data as $k => $v) { $dataStr[$k] = (string)$v; } // FCM exige des valeurs string

	while ($row = mysql_fetch_object($res)) {
		$message = array('token' => $row->Token, 'data' => $dataStr);
		if (!$silencieux) {
			$message['notification'] = array('title' => $titre, 'body' => $corps);
		}
		// iOS : pour un push silencieux, content-available=1
		if ($silencieux) {
			$message['apns'] = array('payload' => array('aps' => array('content-available' => 1)));
		}
		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_HTTPHEADER => array(
				'Authorization: Bearer '.$access,
				'Content-Type: application/json'
			),
			CURLOPT_POSTFIELDS => json_encode(array('message' => $message)),
			CURLOPT_TIMEOUT => 5
		));
		curl_exec($ch);
		curl_close($ch);
		// (les tokens invalides pourront être purgés plus tard via le code retour 404/UNREGISTERED)
	}
}

// Destinataires d'un message chat = participants de la conversation sauf l'auteur.
// Type-aware (autonome, utilisable depuis le web et l'API mobile).
function destinatairesChat($convId, $auteur, $dblink) {
	$a = mysql_real_escape_string($auteur, $dblink);
	$c = (int)$convId;
	$conv = mysql_fetch_object(mysql_query("SELECT Type, Equipe FROM NPVB_Conversations WHERE Id=".$c, $dblink));
	if (!$conv) return array();

	if ($conv->Type == 'generale') {
		$where = "j.Etat='V'";
	} else if ($conv->Type == 'equipe') {
		$eq = mysql_real_escape_string($conv->Equipe, $dblink);
		$where = "(a.Joueur IN (SELECT Joueur FROM NPVB_Appartenance WHERE Equipe='".$eq."')
		           OR a.Joueur IN (SELECT Responsable FROM NPVB_Equipes WHERE Nom='".$eq."')
		           OR a.Joueur IN (SELECT Supleant FROM NPVB_Equipes WHERE Nom='".$eq."'))";
	} else {
		$where = "a.Joueur IN (SELECT Joueur FROM NPVB_ConversationMembres WHERE Conversation=".$c.")";
	}

	$res = mysql_query("SELECT DISTINCT a.Joueur FROM NPVB_AppareilsPush a
	                    JOIN NPVB_Joueurs j ON j.Pseudonyme=a.Joueur
	                    WHERE a.Joueur <> '".$a."' AND ".$where, $dblink);
	$liste = array();
	if ($res) { while ($row = mysql_fetch_object($res)) { $liste[] = $row->Joueur; } }
	return $liste;
}
?>
