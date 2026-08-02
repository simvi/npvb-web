<?php
/**
 * API REST NPVB v1 - Compatible PHP 4.4.3 (Free.fr)
 * Architecture REST moderne adaptée aux contraintes PHP 4
 */

// Reimplementation de OLD_PASSWORD() de MySQL (supprime en MySQL 5.7)
function old_password_hash($password) {
	$nr  = 1345345333;
	$add = 7;
	$nr2 = 0x12345671;
	for ($i = 0; $i < strlen($password); $i++) {
		$c = ord($password[$i]);
		if ($c == 32 || $c == 9) continue;
		$nr  = $nr ^ (((($nr & 63) + $add) * $c) + ($nr * 256));
		$nr  = (($nr % 4294967296) + 4294967296) % 4294967296;
		$nr2 = $nr2 + (($nr2 * 256) ^ $nr);
		$nr2 = (($nr2 % 4294967296) + 4294967296) % 4294967296;
		$add += $c;
	}
	return sprintf("%08x%08x", $nr & 0x7FFFFFFF, $nr2 & 0x7FFFFFFF);
}

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// json_encode pour PHP 4
if (!function_exists('json_encode')) {
    function json_encode($data) {
        switch ($type = gettype($data)) {
            case 'NULL':
                return 'null';
            case 'boolean':
                return ($data ? 'true' : 'false');
            case 'integer':
            case 'double':
            case 'float':
                return $data;
            case 'string':
                // Échapper correctement les caractères spéciaux pour JSON
                // Important: échapper les backslashes en premier!
                $data = str_replace('\\', '\\\\', $data);
                $data = str_replace('"', '\\"', $data);
                $data = str_replace("\r", "\\r", $data);
                $data = str_replace("\n", "\\n", $data);
                $data = str_replace("\t", "\\t", $data);
                return '"' . $data . '"';
            case 'object':
                $data = get_object_vars($data);
            case 'array':
                $output_index_count = 0;
                $output_indexed = array();
                $output_associative = array();
                foreach ($data as $key => $value) {
                    $output_indexed[] = json_encode($value);
                    $output_associative[] = json_encode($key) . ':' . json_encode($value);
                    if ($output_index_count !== NULL && $output_index_count++ !== $key) {
                        $output_index_count = NULL;
                    }
                }
                if ($output_index_count !== NULL) {
                    return '[' . implode(',', $output_indexed) . ']';
                } else {
                    return '{' . implode(',', $output_associative) . '}';
                }
            default:
                return '';
        }
    }
}

// Configuration — chargée depuis config.php (hors dépôt)
include(__DIR__ . "/../../config.php");
$DB_HOST = $config['db_host'];
$DB_NAME = $config['db_name'];
$DB_USER = $config['db_user'];
$DB_PASS = $config['db_pass'];
$TOKEN_SECRET = $config['mobile_token_secret'];

// Connexion DB
$dblink = mysql_connect($DB_HOST, $DB_USER, $DB_PASS);
if (!$dblink) {
    echo json_encode(array('success' => false, 'error' => array('code' => 'DB_ERROR', 'message' => 'Database connection failed')));
    exit;
}

mysql_select_db($DB_NAME, $dblink);
mysql_query("SET CHARACTER SET utf8mb4", $dblink);
mysql_query("SET NAMES utf8mb4", $dblink);

// Liste d'attente (promotion auto). $PasseParIndex requis par attente.inc.php/push/smtp.
$PasseParIndex = true;
include_once(__DIR__ . '/../../attente.inc.php');

// Réutilise les fonctions de permission du chat web au lieu de les dupliquer
// (permissions.inc.php exige $PasseParIndex, déjà positionné ci-dessus)
include_once(__DIR__ . '/../../permissions.inc.php');

// Statut admin calculé depuis les rôles (remplace l'ancienne colonne DieuToutPuissant)
function estAdminParRole($pseudo) {
    $p = mysql_real_escape_string($pseudo);
    $r = mysql_query("SELECT 1 FROM NPVB_JoueurRoles WHERE Pseudonyme='$p' AND Role='admin' LIMIT 1");
    return ($r && mysql_num_rows($r) > 0);
}

// Token mobile HMAC signé + expiration (remplace l'ancien md5 décoratif)
function genererTokenMobile($pseudo, $secret, $dureeSecondes = 2592000) { // 30 jours
    $expire = time() + $dureeSecondes;
    $sig = hash_hmac('sha256', $pseudo.'|'.$expire, $secret);
    return base64_encode($pseudo.'|'.$expire.'|'.$sig);
}

function verifierTokenMobile($tokenBrut, $secret) {
    $decoded = base64_decode($tokenBrut, true);
    if ($decoded === false) return null;
    $parts = explode('|', $decoded, 3);
    if (count($parts) != 3) return null;
    list($pseudo, $expire, $sig) = $parts;
    if (!ctype_digit($expire) || time() > (int)$expire) return null;
    $attendu = hash_hmac('sha256', $pseudo.'|'.$expire, $secret);
    if (!hash_equals($attendu, $sig)) return null;
    return $pseudo;
}

// Dérive le pseudo authentifié depuis le header Authorization: Bearer <token>, ou null
function utilisateurRequete($secret) {
    $headers = function_exists('getallheaders') ? getallheaders() : array();
    $auth = isset($headers['Authorization']) ? $headers['Authorization']
          : (isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '');
    if ($auth && preg_match('/^Bearer\s+(.+)$/i', trim($auth), $m)) {
        return verifierTokenMobile(trim($m[1]), $secret);
    }
    return null;
}

// Charge un $Joueur (avec ->Roles) depuis un pseudo, pour réutiliser permissions.inc.php
function mobileChargerJoueur($pseudo) {
    global $dblink;
    $p = mysql_real_escape_string($pseudo, $dblink);
    $j = mysql_fetch_object(mysql_query("SELECT * FROM NPVB_Joueurs WHERE Etat='V' AND Pseudonyme='".$p."'", $dblink));
    if (!$j) return null;
    chargerRolesJoueur($j, $dblink);
    return $j;
}

// Accès d'un membre à une conversation (délègue à permissions.inc.php)
function mobileConvAccessible($pseudo, $convId) {
    global $dblink;
    $Joueur = mobileChargerJoueur($pseudo);
    if (!$Joueur) return false;
    $conv = mysql_fetch_object(mysql_query("SELECT * FROM NPVB_Conversations WHERE Id=".(int)$convId, $dblink));
    return peutAccederConversation($Joueur, $conv, $dblink);
}

// Peut poster (délègue à permissions.inc.php)
function mobilePeutPoster($pseudo, $convId) {
    global $dblink;
    $Joueur = mobileChargerJoueur($pseudo);
    if (!$Joueur) return false;
    $conv = mysql_fetch_object(mysql_query("SELECT * FROM NPVB_Conversations WHERE Id=".(int)$convId, $dblink));
    return peutPosterDansConv($Joueur, $conv, $dblink);
}

// Conversations accessibles au membre (id, type, nom, archive, nonlus, peutPoster)
function mobileConvsAccessibles($pseudo) {
    global $dblink;
    $Joueur = mobileChargerJoueur($pseudo);
    if (!$Joueur) return array();
    $convs = conversationsAccessibles($Joueur, $dblink);
    $out = array();
    foreach ($convs as $c) {
        $nom = $c->Nom;
        if ($c->Type == 'prive') {
            $pe = mysql_real_escape_string($pseudo, $dblink);
            $rr = mysql_query("SELECT j.Prenom, j.Nom, j.Pseudonyme FROM NPVB_ConversationMembres cm
                               JOIN NPVB_Joueurs j ON j.Pseudonyme=cm.Joueur
                               WHERE cm.Conversation=".(int)$c->Id." AND cm.Joueur<>'".$pe."' LIMIT 1", $dblink);
            if ($rr && ($jj = mysql_fetch_object($rr))) { $n = trim($jj->Prenom.' '.$jj->Nom); $nom = ($n != '') ? $n : $jj->Pseudonyme; }
        }
        $out[] = array(
            'id' => (int)$c->Id, 'type' => $c->Type, 'nom' => $nom,
            'archive' => ($c->Archive == 'o'),
            'nonlus' => (int)$c->nonlus,
            'peutPoster' => peutPosterDansConv($Joueur, $c, $dblink)
        );
    }
    return $out;
}

// Fonctions push mutualisées (NPVB_AppareilsPush + FCM HTTP v1)
include_once(__DIR__ . '/../../push.inc.php');

// Récupérer endpoint
$endpoint = isset($_GET['endpoint']) ? trim($_GET['endpoint'], '/') : '';

// Page d'accueil API
if (empty($endpoint)) {
    echo json_encode(array(
        'success' => true,
        'data' => array(
            'name' => $config['club_sigle'] . ' API',
            'version' => 'v1',
            'status' => 'online',
            'php' => phpversion()
        ),
        'message' => $config['club_sigle'] . ' API v1'
    ));
    mysql_close($dblink);
    exit;
}

// Parser endpoint
$segments = explode('/', $endpoint);
$resource = $segments[0];

// === AUTH ===
if ($resource == 'auth' && isset($segments[1]) && $segments[1] == 'login') {
    $input = file_get_contents('php://input');
    // Parse JSON manuel (PHP 4 compatible)
    preg_match('/"username"\s*:\s*"([^"]+)"/', $input, $user_match);
    preg_match('/"password"\s*:\s*"([^"]+)"/', $input, $pass_match);

    $username = isset($user_match[1]) ? $user_match[1] : (isset($_POST['username']) ? $_POST['username'] : '');
    $password = isset($pass_match[1]) ? $pass_match[1] : (isset($_POST['password']) ? $_POST['password'] : '');

    if (empty($username) || empty($password)) {
        echo json_encode(array('success' => false, 'error' => array('code' => 'INVALID_INPUT', 'message' => 'Username and password required')));
        exit;
    }

    $username = mysql_real_escape_string($username);
    $query = "SELECT Pseudonyme FROM NPVB_Joueurs
              WHERE etat='V' AND Pseudonyme='$username' AND Password='".old_password_hash($password)."'";
    $result = mysql_query($query);

    if ($result && mysql_num_rows($result) > 0) {
        $user = mysql_fetch_assoc($result);
        $token = genererTokenMobile($username, $TOKEN_SECRET);
        echo json_encode(array(
            'success' => true,
            'data' => array(
                'token' => $token,
                'user' => array(
                    'Pseudonyme' => $user['Pseudonyme'],
                    'isAdmin' => estAdminParRole($user['Pseudonyme'])
                )
            ),
            'message' => 'Login successful'
        ));
    } else {
        echo json_encode(array('success' => false, 'error' => array('code' => 'INVALID_CREDENTIALS', 'message' => 'Invalid credentials')));
    }
    mysql_close($dblink);
    exit;
}

// === MEMBERS ===
if ($resource == 'members') {
    $username = isset($segments[1]) ? $segments[1] : null;

    if ($username && isset($segments[2]) && $segments[2] == 'presences') {
        // GET /members/{username}/presences?status=o
        $status = isset($_GET['status']) ? $_GET['status'] : 'o';
        $username = mysql_real_escape_string($username);
        $status = mysql_real_escape_string($status);

        $query = "SELECT Joueur, Libelle, DateHeure, Prevue FROM NPVB_Presence
                  WHERE Joueur='$username' AND Prevue='$status' ORDER BY DateHeure DESC";
        $result = mysql_query($query);
        $data = array();
        while ($row = mysql_fetch_assoc($result)) $data[] = $row;

        echo json_encode(array('success' => true, 'data' => $data));
    } elseif ($username) {
        // GET /members/{username}
        $username = mysql_real_escape_string($username);
        $query = "SELECT Pseudonyme, Nom, Prenom, Sexe, DateNaissance,
                         Profession, Adresse, CPVille, Telephones, Email, Accord, NumeroLicence
                  FROM NPVB_Joueurs WHERE etat='V' AND Pseudonyme='$username'";
        $result = mysql_query($query);

        if ($result && mysql_num_rows($result) > 0) {
            $member = mysql_fetch_assoc($result);

            // Statut admin (compat ancienne colonne) + nom de fichier Photo
            $member['DieuToutPuissant'] = estAdminParRole($member['Pseudonyme']) ? 'o' : 'n';
            $member['Photo'] = $member['Pseudonyme'];

            // Récupérer les appartenances pour ce membre
            $appQuery = "SELECT Equipe FROM NPVB_Appartenance WHERE Joueur='$username'";
            $appResult = mysql_query($appQuery);

            $appartenances = array();
            while ($appRow = mysql_fetch_assoc($appResult)) {
                $appartenances[] = array('Libelle' => $appRow['Equipe']);
            }

            // Ajouter les appartenances au membre
            $member['Appartenances'] = $appartenances;

            echo json_encode(array('success' => true, 'data' => array($member)));
        } else {
            echo json_encode(array('success' => false, 'error' => array('code' => 'NOT_FOUND', 'message' => 'Member not found')));
        }
    } else {
        // GET /members (tous)
        $query = "SELECT Pseudonyme, Nom, Prenom, Sexe, DateNaissance,
                         Profession, Adresse, CPVille, Telephones, Email, Accord, NumeroLicence
                  FROM NPVB_Joueurs WHERE etat='V' ORDER BY Nom, Prenom";
        $result = mysql_query($query);
        $data = array();

        while ($row = mysql_fetch_assoc($result)) {
            // Statut admin (compat ancienne colonne) + nom de fichier Photo
            $row['DieuToutPuissant'] = estAdminParRole($row['Pseudonyme']) ? 'o' : 'n';
            $row['Photo'] = $row['Pseudonyme'];

            // Récupérer les appartenances pour ce membre
            $pseudo = mysql_real_escape_string($row['Pseudonyme']);
            $appQuery = "SELECT Equipe FROM NPVB_Appartenance WHERE Joueur='$pseudo'";
            $appResult = mysql_query($appQuery);

            $appartenances = array();
            while ($appRow = mysql_fetch_assoc($appResult)) {
                $appartenances[] = array('Libelle' => $appRow['Equipe']);
            }

            // Ajouter les appartenances au membre
            $row['Appartenances'] = $appartenances;

            $data[] = array($row);
        }

        echo json_encode(array('success' => true, 'data' => $data));
    }
    mysql_close($dblink);
    exit;
}

// === MEMBERSHIPS ===
if ($resource == 'memberships') {
    $query = "SELECT Joueur, Equipe FROM NPVB_Appartenance ORDER BY Equipe, Joueur";
    $result = mysql_query($query);
    $data = array();
    while ($row = mysql_fetch_assoc($result)) $data[] = $row;

    echo json_encode(array('success' => true, 'data' => $data));
    mysql_close($dblink);
    exit;
}

// === RESULTS (matchs avec résultat, tout l'historique, sans filtre de date) ===
if ($resource == 'results') {
    // Mêmes colonnes que /events pour que les apps réutilisent le même modèle.
    // Inscrits=0 (non pertinent pour un match passé, évite une jointure inutile).
    $query = "SELECT DateHeure, Libelle, Etat, Titre, Intitule, Lieu, Adresse,
                     Adversaire, Domicile, Resultat, Analyse, InscritsMax, 0 AS Inscrits
              FROM NPVB_Evenements
              WHERE Resultat <> '' AND Libelle NOT IN ('ASSO','SEANCE')
              ORDER BY DateHeure DESC";
    $result = mysql_query($query);
    $data = array();
    while ($row = mysql_fetch_assoc($result)) $data[] = $row;

    echo json_encode(array('success' => true, 'data' => $data));
    mysql_close($dblink);
    exit;
}

// === EVENTS ===
if ($resource == 'events') {
    $dateHeure = isset($segments[1]) ? $segments[1] : null;

    if ($dateHeure && isset($segments[2]) && $segments[2] == 'presences') {
        // GET /events/{date}/presences?libelle=SENIOR1 (optionnel)
        $dateHeure = mysql_real_escape_string($dateHeure);
        $libelle = isset($_GET['libelle']) ? mysql_real_escape_string($_GET['libelle']) : null;

        if ($libelle) {
            // Filtrer par événement spécifique
            $query = "SELECT Joueur, Libelle, DateHeure, Prevue FROM NPVB_Presence
                      WHERE DateHeure='$dateHeure' AND Libelle='$libelle' ORDER BY Joueur";
        } else {
            // Tous les événements du jour (rétrocompat pour anciennes versions)
            $query = "SELECT Joueur, Libelle, DateHeure, Prevue FROM NPVB_Presence
                      WHERE DateHeure='$dateHeure' ORDER BY Joueur";
        }

        $result = mysql_query($query);
        $data = array();
        while ($row = mysql_fetch_assoc($result)) {
            // Statut lisible dérivé de Prevue ('o'→inscrit, 'n'→indisponible).
            $row['statut'] = ($row['Prevue'] === 'o') ? 'inscrit' : 'indisponible';
            $data[] = $row;
        }

        echo json_encode(array('success' => true, 'data' => $data));
    } elseif ($dateHeure && isset($segments[3]) && $segments[3] == 'waitlist') {
        // GET /events/{date}/{libelle}/waitlist?username=XXX
        // Statut de la liste d'attente (pour restaurer l'état à l'ouverture).
        $libelle = isset($segments[2]) ? mysql_real_escape_string($segments[2]) : '';
        $dateHeure = mysql_real_escape_string($dateHeure);
        $username = isset($_GET['username']) ? mysql_real_escape_string($_GET['username']) : '';

        $count = function_exists('nbListeAttente') ? nbListeAttente($dateHeure, $libelle, $dblink) : 0;
        $onList = ($username && function_exists('estEnListeAttente'))
                  ? estEnListeAttente($username, $dateHeure, $libelle, $dblink) : false;
        $pos = ($onList && function_exists('positionListeAttente'))
               ? positionListeAttente($username, $dateHeure, $libelle, $dblink) : 0;

        echo json_encode(array('success' => true, 'data' => array(
            'count' => $count, 'onWaitlist' => $onList, 'position' => $pos
        )));
    } elseif ($dateHeure) {
        // GET /events/{date}/{libelle}
        $libelle = isset($segments[2]) ? $segments[2] : '';
        $dateHeure = mysql_real_escape_string($dateHeure);
        $libelle = mysql_real_escape_string($libelle);

        $query = "SELECT DateHeure, Libelle, Etat, Titre, Intitule, Lieu, Adresse,
                         Adversaire, Domicile, Resultat, Analyse, InscritsMax,
                         (SELECT COUNT(*) FROM NPVB_Presence
                          WHERE NPVB_Presence.DateHeure=NPVB_Evenements.DateHeure
                          AND NPVB_Presence.Libelle=NPVB_Evenements.Libelle
                          AND NPVB_Presence.Prevue='o') AS Inscrits
                  FROM NPVB_Evenements
                  WHERE DateHeure='$dateHeure' AND Libelle='$libelle'";
        $result = mysql_query($query);

        if ($result && mysql_num_rows($result) > 0) {
            echo json_encode(array('success' => true, 'data' => mysql_fetch_assoc($result)));
        } else {
            echo json_encode(array('success' => false, 'error' => array('code' => 'NOT_FOUND', 'message' => 'Event not found')));
        }
    } else {
        // GET /events (tous) - LEFT JOIN remplace la sous-requête corrélée (N+1 → 1 requête)
        $query = "SELECT e.DateHeure, e.Libelle, e.Etat, e.Titre, e.Intitule, e.Lieu, e.Adresse,
                         e.Adversaire, e.Domicile, e.Resultat, e.Analyse, e.InscritsMax,
                         IFNULL(p.cnt, 0) AS Inscrits
                  FROM NPVB_Evenements e
                  LEFT JOIN (
                      SELECT DateHeure, Libelle, COUNT(*) AS cnt
                      FROM NPVB_Presence
                      WHERE Prevue='o'
                      GROUP BY DateHeure, Libelle
                  ) p ON p.DateHeure=e.DateHeure AND p.Libelle=e.Libelle
                  WHERE e.DateHeure > 20190000000000 AND e.etat != 'I'
                  ORDER BY e.DateHeure ASC";
        $result = mysql_query($query);
        $data = array();
        while ($row = mysql_fetch_assoc($result)) $data[] = $row;

        echo json_encode(array('success' => true, 'data' => $data));
    }
    mysql_close($dblink);
    exit;
}

// === PRESENCES (POST) ===
if ($resource == 'presences' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = file_get_contents('php://input');
    preg_match('/"dateHeure"\s*:\s*"([^"]+)"/', $input, $date_match);
    preg_match('/"joueur"\s*:\s*"([^"]+)"/', $input, $joueur_match);
    preg_match('/"libelle"\s*:\s*"([^"]+)"/', $input, $libelle_match);
    preg_match('/"statut"\s*:\s*"([^"]+)"/', $input, $statut_match);

    $dateHeure = isset($date_match[1]) ? mysql_real_escape_string($date_match[1]) : '';
    $joueur = isset($joueur_match[1]) ? mysql_real_escape_string($joueur_match[1]) : '';
    $libelle = isset($libelle_match[1]) ? mysql_real_escape_string($libelle_match[1]) : '';
    $statut = isset($statut_match[1]) ? $statut_match[1] : '';

    // Le client envoie un STATUT cible explicite ; le serveur en déduit l'opération.
    // Statuts acceptés : inscrit | indisponible | absent_reponse | liste_attente
    if (empty($dateHeure) || empty($joueur) || empty($statut)) {
        echo json_encode(array(
            'success' => false,
            'error' => array(
                'code' => 'MISSING_FIELDS',
                'message' => 'Champs requis manquants: dateHeure, joueur, statut'
            )
        ));
        mysql_close($dblink);
        exit;
    }

    // Si le libelle est manquant, essayer de le récupérer du premier événement de ce jour
    // (pour rétrocompatibilité avec anciennes versions de l'app)
    if (empty($libelle)) {
        $fallbackQuery = "SELECT Libelle FROM NPVB_Evenements
                          WHERE DateHeure='$dateHeure'
                          ORDER BY Libelle ASC LIMIT 1";
        $fallbackResult = mysql_query($fallbackQuery);
        if ($fallbackResult && mysql_num_rows($fallbackResult) > 0) {
            $fallbackRow = mysql_fetch_assoc($fallbackResult);
            $libelle = $fallbackRow['Libelle'];
        } else {
            echo json_encode(array(
                'success' => false,
                'error' => array(
                    'code' => 'MISSING_LIBELLE',
                    'message' => 'Le champ libelle est requis et aucun événement trouvé pour cette date'
                )
            ));
            mysql_close($dblink);
            exit;
        }
    }

    // État courant du membre (Prevue) pour décider de l'opération.
    $cur = mysql_query("SELECT Prevue FROM NPVB_Presence WHERE Joueur='$joueur' AND DateHeure='$dateHeure' AND Libelle='$libelle'");
    $exists = $cur && mysql_num_rows($cur) > 0;
    $dejaPresent = false;
    if ($exists) { $crow = mysql_fetch_assoc($cur); $dejaPresent = ($crow['Prevue'] === 'o'); }

    // Réponse normalisée : on renvoie toujours le statut RÉSULTANT (le client ne devine pas).
    function reponsePresence($statut, $position, $message) {
        global $dblink;
        echo json_encode(array(
            'success' => true,
            'data' => array('statut' => $statut, 'positionAttente' => $position),
            'message' => $message
        ));
        mysql_close($dblink);
        exit;
    }

    if ($statut == 'inscrit') {
        // S'inscrire (Prevue='o'). Refusé si complet et pas déjà présent →
        // statut résultant 'complet' (l'app proposera la liste d'attente).
        if (!$dejaPresent && function_exists('estComplet') && estComplet($dateHeure, $libelle, $dblink)) {
            reponsePresence('complet', null, 'Événement complet');
        }
        if ($exists) {
            mysql_query("UPDATE NPVB_Presence SET Prevue='o' WHERE Joueur='$joueur' AND DateHeure='$dateHeure' AND Libelle='$libelle'");
        } else {
            mysql_query("INSERT INTO NPVB_Presence (Joueur, DateHeure, Libelle, Prevue) VALUES ('$joueur', '$dateHeure', '$libelle', 'o')");
        }
        if (function_exists('retirerListeAttente')) retirerListeAttente($joueur, $dateHeure, $libelle, $dblink);
        reponsePresence('inscrit', null, 'Inscription réussie');

    } elseif ($statut == 'indisponible') {
        // Se déclarer indisponible (Prevue='n', la ligne est conservée).
        if ($exists) {
            mysql_query("UPDATE NPVB_Presence SET Prevue='n' WHERE Joueur='$joueur' AND DateHeure='$dateHeure' AND Libelle='$libelle'");
        } else {
            mysql_query("INSERT INTO NPVB_Presence (Joueur, DateHeure, Libelle, Prevue) VALUES ('$joueur', '$dateHeure', '$libelle', 'n')");
        }
        if (function_exists('retirerListeAttente')) retirerListeAttente($joueur, $dateHeure, $libelle, $dblink);
        if (function_exists('promouvoirListeAttente')) promouvoirListeAttente($dateHeure, $libelle, $dblink);
        reponsePresence('indisponible', null, 'Indisponibilité enregistrée');

    } elseif ($statut == 'absent_reponse') {
        // Effacer la réponse (supprime la ligne) et sortir de la liste d'attente.
        if ($exists) {
            mysql_query("DELETE FROM NPVB_Presence WHERE Joueur='$joueur' AND DateHeure='$dateHeure' AND Libelle='$libelle'");
        }
        if (function_exists('retirerListeAttente')) retirerListeAttente($joueur, $dateHeure, $libelle, $dblink);
        if (function_exists('promouvoirListeAttente')) promouvoirListeAttente($dateHeure, $libelle, $dblink);
        reponsePresence('absent_reponse', null, 'Réponse effacée');

    } elseif ($statut == 'liste_attente') {
        // Rejoindre la liste d'attente (action explicite). Idempotent.
        if (function_exists('ajouterListeAttente')) ajouterListeAttente($joueur, $dateHeure, $libelle, $dblink);
        $pos = function_exists('positionListeAttente') ? positionListeAttente($joueur, $dateHeure, $libelle, $dblink) : 0;
        reponsePresence('liste_attente', $pos, "Vous êtes en liste d'attente (position " . $pos . ")");

    } else {
        echo json_encode(array('success' => false, 'error' => array('code' => 'INVALID_INPUT', 'message' => 'Statut invalide')));
    }
    mysql_close($dblink);
    exit;
}

// === CHAT ===
if ($resource == 'chat') {
    $sousRes = isset($segments[1]) ? $segments[1] : '';
    $username = '';
    $convId = 0;
    // Token vérifiable (Authorization: Bearer) prioritaire ; fallback username client
    // le temps de l'adoption des nouvelles versions d'app (à retirer une fois suffisante).
    $usernameToken = utilisateurRequete($TOKEN_SECRET);

    // GET /chat/conversations?username=XXX
    if ($sousRes == 'conversations' && $_SERVER['REQUEST_METHOD'] != 'POST') {
        $username = $usernameToken ?: (isset($_GET['username']) ? trim($_GET['username']) : '');
        if (!$username) {
            echo json_encode(array('success' => false, 'error' => array('code' => 'MISSING_FIELDS', 'message' => 'username requis')));
            mysql_close($dblink); exit;
        }
        $convs = mobileConvsAccessibles($username);
        $result = array();
        foreach ($convs as $c) {
            $cid = (int)$c['id'];
            $lr = mysql_query("SELECT Contenu, DateEnvoi FROM NPVB_MessagesChat WHERE Conversation=$cid AND Supprime='n' ORDER BY Id DESC LIMIT 1");
            $lastMessage = null; $lastDate = null;
            if ($lr && mysql_num_rows($lr) > 0) {
                $lrow = mysql_fetch_assoc($lr);
                $lastMessage = $lrow['Contenu'];
                $lastDate = $lrow['DateEnvoi'];
            }
            $result[] = array(
                'id' => $cid,
                'type' => $c['type'],
                'nom' => $c['nom'],
                'lastMessage' => $lastMessage,
                'lastDate' => $lastDate,
                'unread' => (int)$c['nonlus']
            );
        }
        echo json_encode(array('success' => true, 'data' => $result));
        mysql_close($dblink); exit;
    }

    // GET /chat/messages/{id}/lecteurs?username=X
    if ($sousRes == 'messages' && isset($segments[2]) && ctype_digit($segments[2]) && isset($segments[3]) && $segments[3] == 'lecteurs' && $_SERVER['REQUEST_METHOD'] != 'POST') {
        $msgId = (int)$segments[2];
        $username = $usernameToken ?: (isset($_GET['username']) ? trim($_GET['username']) : '');
        if (empty($username)) {
            echo json_encode(array('success' => false, 'error' => array('code' => 'MISSING_FIELDS', 'message' => 'username requis')));
            mysql_close($dblink); exit;
        }
        $Joueur = mobileChargerJoueur($username);
        $msg = mysql_fetch_object(mysql_query("SELECT Conversation FROM NPVB_MessagesChat WHERE Id=$msgId"));
        if (!$Joueur || !$msg || !peut($Joueur, 'gerer_roles')) {
            echo json_encode(array('success' => false, 'error' => array('code' => 'FORBIDDEN', 'message' => 'Accès refusé')));
            mysql_close($dblink); exit;
        }
        $result = lecteursMessage((int)$msg->Conversation, $msgId, $dblink);
        $lu = array();
        $nonlu = array();
        foreach ($result['lu'] as $p) {
            $r = mysql_query("SELECT Prenom, Nom FROM NPVB_Joueurs WHERE Pseudonyme='".mysql_real_escape_string($p)."'");
            $j = mysql_fetch_object($r);
            $nom = $j ? trim($j->Prenom.' '.$j->Nom) : $p;
            $lu[] = ($nom != '') ? $nom : $p;
        }
        foreach ($result['nonlu'] as $p) {
            $r = mysql_query("SELECT Prenom, Nom FROM NPVB_Joueurs WHERE Pseudonyme='".mysql_real_escape_string($p)."'");
            $j = mysql_fetch_object($r);
            $nom = $j ? trim($j->Prenom.' '.$j->Nom) : $p;
            $nonlu[] = ($nom != '') ? $nom : $p;
        }
        echo json_encode(array('success' => true, 'data' => array('lu' => $lu, 'nonlu' => $nonlu, 'totalLu' => count($lu), 'total' => count($lu) + count($nonlu))));
        mysql_close($dblink); exit;
    }

    // GET /chat/messages?conv=X&since=Y&username=Z  (polling avant)
    // GET /chat/messages?conv=X&avant=Y&username=Z   (pagination arrière, 50 messages précédant l'id `avant`)
    if ($sousRes == 'messages' && $_SERVER['REQUEST_METHOD'] != 'POST') {
        $convId = isset($_GET['conv']) ? (int)$_GET['conv'] : 0;
        $since = isset($_GET['since']) ? (int)$_GET['since'] : 0;
        $avant = isset($_GET['avant']) ? (int)$_GET['avant'] : 0;
        $username = $usernameToken ?: (isset($_GET['username']) ? trim($_GET['username']) : '');
        if (!$username || !$convId) {
            echo json_encode(array('success' => false, 'error' => array('code' => 'MISSING_FIELDS', 'message' => 'conv et username requis')));
            mysql_close($dblink); exit;
        }
        if (!mobileConvAccessible($username, $convId)) {
            echo json_encode(array('success' => false, 'error' => array('code' => 'FORBIDDEN', 'message' => 'Accès refusé')));
            mysql_close($dblink); exit;
        }
        $champs = "Id, Conversation AS conv, Auteur AS auteur, Contenu AS contenu, DateEnvoi AS dateEnvoi,
                   DateModif AS dateModif, Epingle AS epingle";
        // Trois cas : pagination arrière (avant), chargement initial (since=0, borné aux 50
        // derniers pour rester cohérent avec la pagination), poll incrémental (since>0, non
        // borné : on veut TOUS les nouveaux messages depuis le dernier since connu du client).
        if ($avant > 0) {
            $q = "SELECT $champs
                  FROM NPVB_MessagesChat
                  WHERE Conversation=$convId AND Supprime='n' AND Id < $avant
                  ORDER BY Id DESC LIMIT 50";
        } elseif ($since == 0) {
            $q = "SELECT $champs
                  FROM NPVB_MessagesChat
                  WHERE Conversation=$convId AND Supprime='n'
                  ORDER BY Id DESC LIMIT 50";
        } else {
            $q = "SELECT $champs
                  FROM NPVB_MessagesChat
                  WHERE Conversation=$convId AND Supprime='n' AND Id > $since
                  ORDER BY Id ASC";
        }
        $r = mysql_query($q);
        $msgs = array();
        while ($row = mysql_fetch_assoc($r)) {
            $msgs[] = array(
                'id' => (int)$row['Id'],
                'conv' => (int)$row['conv'],
                'auteur' => $row['auteur'],
                'contenu' => $row['contenu'],
                'dateEnvoi' => $row['dateEnvoi'],
                'epingle' => ($row['epingle'] == 'o'),
                'modifie' => !empty($row['dateModif'])
            );
        }
        $borne = ($avant > 0 || $since == 0);
        if ($borne) {
            $msgs = array_reverse($msgs); // remettre en ordre chronologique ascendant
        }
        echo json_encode(array('success' => true, 'data' => array('messages' => $msgs, 'hasMore' => ($borne && count($msgs) == 50))));
        mysql_close($dblink); exit;
    }

    // POST /chat/messages/{id}/edit  body: {contenu, username}
    if ($sousRes == 'messages' && isset($segments[2]) && ctype_digit($segments[2]) && isset($segments[3]) && $segments[3] == 'edit' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $msgId = (int)$segments[2];
        $input = file_get_contents('php://input');
        preg_match('/"username"\s*:\s*"([^"]+)"/', $input, $u);
        preg_match('/"contenu"\s*:\s*"(.*?)(?<!\\\\)"\s*[,}]/s', $input, $c);
        $username = $usernameToken ?: (isset($u[1]) ? trim($u[1]) : '');
        $contenu  = isset($c[1]) ? trim($c[1]) : '';
        if (empty($username) || $contenu === '') {
            echo json_encode(array('success' => false, 'error' => array('code' => 'MISSING_FIELDS', 'message' => 'contenu et username requis')));
            mysql_close($dblink); exit;
        }
        $Joueur = mobileChargerJoueur($username);
        $msg = $Joueur ? mysql_fetch_object(mysql_query("SELECT Auteur, DateEnvoi, Supprime FROM NPVB_MessagesChat WHERE Id=$msgId")) : null;
        if (!$Joueur || !$msg || !peutEditerMessage($Joueur, $msg, $dblink)) {
            echo json_encode(array('success' => false, 'error' => array('code' => 'FORBIDDEN', 'message' => 'Accès refusé')));
            mysql_close($dblink); exit;
        }
        $ce = mysql_real_escape_string($contenu);
        if (mysql_query("UPDATE NPVB_MessagesChat SET Contenu='$ce', DateModif=NOW() WHERE Id=$msgId")) {
            echo json_encode(array('success' => true, 'data' => array('success' => true)));
        } else {
            echo json_encode(array('success' => false, 'error' => array('code' => 'DB_ERROR', 'message' => 'Erreur enregistrement')));
        }
        mysql_close($dblink); exit;
    }

    // POST /chat/messages/{id}/delete  body: {username}
    if ($sousRes == 'messages' && isset($segments[2]) && ctype_digit($segments[2]) && isset($segments[3]) && $segments[3] == 'delete' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $msgId = (int)$segments[2];
        $input = file_get_contents('php://input');
        preg_match('/"username"\s*:\s*"([^"]+)"/', $input, $u);
        $username = $usernameToken ?: (isset($u[1]) ? trim($u[1]) : '');
        if (empty($username)) {
            echo json_encode(array('success' => false, 'error' => array('code' => 'MISSING_FIELDS', 'message' => 'username requis')));
            mysql_close($dblink); exit;
        }
        $Joueur = mobileChargerJoueur($username);
        $msg = $Joueur ? mysql_fetch_object(mysql_query("SELECT Auteur FROM NPVB_MessagesChat WHERE Id=$msgId")) : null;
        if (!$Joueur || !$msg || !peutSupprimerMessage($Joueur, $msg, $dblink)) {
            echo json_encode(array('success' => false, 'error' => array('code' => 'FORBIDDEN', 'message' => 'Accès refusé')));
            mysql_close($dblink); exit;
        }
        mysql_query("UPDATE NPVB_MessagesChat SET Supprime='o' WHERE Id=$msgId");
        echo json_encode(array('success' => true, 'data' => array('success' => true)));
        mysql_close($dblink); exit;
    }

    // POST /chat/messages/{id}/pin  body: {username}
    if ($sousRes == 'messages' && isset($segments[2]) && ctype_digit($segments[2]) && isset($segments[3]) && $segments[3] == 'pin' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $msgId = (int)$segments[2];
        $input = file_get_contents('php://input');
        preg_match('/"username"\s*:\s*"([^"]+)"/', $input, $u);
        $username = $usernameToken ?: (isset($u[1]) ? trim($u[1]) : '');
        if (empty($username)) {
            echo json_encode(array('success' => false, 'error' => array('code' => 'MISSING_FIELDS', 'message' => 'username requis')));
            mysql_close($dblink); exit;
        }
        $Joueur = mobileChargerJoueur($username);
        $msg = mysql_fetch_object(mysql_query("SELECT Conversation FROM NPVB_MessagesChat WHERE Id=$msgId"));
        if (!$Joueur || !$msg || !peut($Joueur, 'gerer_roles')) {
            echo json_encode(array('success' => false, 'error' => array('code' => 'FORBIDDEN', 'message' => 'Accès refusé')));
            mysql_close($dblink); exit;
        }
        $cid = (int)$msg->Conversation;
        mysql_query("UPDATE NPVB_MessagesChat SET Epingle='n' WHERE Conversation=$cid AND Epingle='o'");
        mysql_query("UPDATE NPVB_MessagesChat SET Epingle='o' WHERE Id=$msgId AND Conversation=$cid");
        echo json_encode(array('success' => true, 'data' => array('success' => true)));
        mysql_close($dblink); exit;
    }

    // POST /chat/messages/{id}/unpin  body: {username}
    if ($sousRes == 'messages' && isset($segments[2]) && ctype_digit($segments[2]) && isset($segments[3]) && $segments[3] == 'unpin' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $msgId = (int)$segments[2];
        $input = file_get_contents('php://input');
        preg_match('/"username"\s*:\s*"([^"]+)"/', $input, $u);
        $username = $usernameToken ?: (isset($u[1]) ? trim($u[1]) : '');
        if (empty($username)) {
            echo json_encode(array('success' => false, 'error' => array('code' => 'MISSING_FIELDS', 'message' => 'username requis')));
            mysql_close($dblink); exit;
        }
        $Joueur = mobileChargerJoueur($username);
        $msg = mysql_fetch_object(mysql_query("SELECT Conversation FROM NPVB_MessagesChat WHERE Id=$msgId"));
        if (!$Joueur || !$msg || !peut($Joueur, 'gerer_roles')) {
            echo json_encode(array('success' => false, 'error' => array('code' => 'FORBIDDEN', 'message' => 'Accès refusé')));
            mysql_close($dblink); exit;
        }
        $cid = (int)$msg->Conversation;
        mysql_query("UPDATE NPVB_MessagesChat SET Epingle='n' WHERE Id=$msgId AND Conversation=$cid");
        echo json_encode(array('success' => true, 'data' => array('success' => true)));
        mysql_close($dblink); exit;
    }

    // POST /chat/messages  body: {conv, contenu, username}
    if ($sousRes == 'messages' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $input = file_get_contents('php://input');
        preg_match('/"username"\s*:\s*"([^"]+)"/', $input, $u);
        preg_match('/"contenu"\s*:\s*"(.*?)(?<!\\\\)"\s*[,}]/s', $input, $c);
        preg_match('/"conv"\s*:\s*(\d+)/', $input, $cv);
        $username = $usernameToken ?: (isset($u[1]) ? trim($u[1]) : '');
        $contenu  = isset($c[1]) ? trim($c[1]) : '';
        $convId   = isset($cv[1]) ? (int)$cv[1] : 0;
        if (empty($username) || $contenu === '' || !$convId) {
            echo json_encode(array('success' => false, 'error' => array('code' => 'MISSING_FIELDS', 'message' => 'conv, contenu et username requis')));
            mysql_close($dblink); exit;
        }
        if (!mobilePeutPoster($username, $convId)) {
            echo json_encode(array('success' => false, 'error' => array('code' => 'FORBIDDEN', 'message' => 'Publication non autorisée')));
            mysql_close($dblink); exit;
        }
        $ue = mysql_real_escape_string($username);
        $ce = mysql_real_escape_string($contenu);
        if (mysql_query("INSERT INTO NPVB_MessagesChat (Conversation, Auteur, Contenu, DateEnvoi) VALUES ($convId, '$ue', '$ce', NOW())")) {
            $newId = mysql_insert_id();
            echo json_encode(array('success' => true, 'data' => array('success' => true, 'id' => $newId)));
            $convRow = mysql_fetch_object(mysql_query("SELECT Nom FROM NPVB_Conversations WHERE Id=$convId"));
            $convNom = $convRow ? $convRow->Nom : 'Chat';
            $dest = destinatairesChat($convId, $username, $dblink);
            $apercu = mb_substr($contenu, 0, 80) . (mb_strlen($contenu) > 80 ? '…' : '');
            envoyerPush($dest, $convNom, $username . ' : ' . $apercu, $dblink, array('conv_id' => (string)$convId, 'type' => 'chat'));
        } else {
            echo json_encode(array('success' => false, 'error' => array('code' => 'DB_ERROR', 'message' => 'Enregistrement impossible')));
        }
        mysql_close($dblink); exit;
    }

    // POST /chat/read  body: {conv, lastid, username}
    if ($sousRes == 'read' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $input = file_get_contents('php://input');
        preg_match('/"username"\s*:\s*"([^"]+)"/', $input, $u);
        preg_match('/"lastid"\s*:\s*(\d+)/', $input, $li);
        preg_match('/"conv"\s*:\s*(\d+)/', $input, $cv);
        $username = $usernameToken ?: (isset($u[1]) ? trim($u[1]) : '');
        $lastid   = isset($li[1]) ? (int)$li[1] : 0;
        $convId   = isset($cv[1]) ? (int)$cv[1] : 0;
        if (empty($username) || !$convId) {
            echo json_encode(array('success' => false, 'error' => array('code' => 'MISSING_FIELDS', 'message' => 'conv et username requis')));
            mysql_close($dblink); exit;
        }
        if (!mobileConvAccessible($username, $convId)) {
            echo json_encode(array('success' => false, 'error' => array('code' => 'FORBIDDEN', 'message' => 'Accès refusé')));
            mysql_close($dblink); exit;
        }
        $ue = mysql_real_escape_string($username);
        mysql_query("INSERT INTO NPVB_MessagesLus (Joueur, Conversation, DernierLuId) VALUES ('$ue', $convId, $lastid)
                     ON DUPLICATE KEY UPDATE DernierLuId=GREATEST(DernierLuId, $lastid)");
        echo json_encode(array('success' => true, 'data' => array('success' => true)));
        mysql_close($dblink); exit;
    }

    // POST /chat/token — enregistrement token FCM
    if ($sousRes == 'token' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $input    = file_get_contents('php://input');
        preg_match('/"token"\s*:\s*"([^"]+)"/', $input, $tk);
        preg_match('/"username"\s*:\s*"([^"]+)"/', $input, $u);
        preg_match('/"platform"\s*:\s*"([^"]+)"/', $input, $pl);
        $token    = isset($tk[1]) ? trim($tk[1]) : '';
        $uname    = $usernameToken ?: (isset($u[1]) ? trim($u[1]) : '');
        $platform = isset($pl[1]) ? trim($pl[1]) : 'ios';
        if (!$token || !$uname) {
            echo json_encode(array('success' => false, 'error' => array('code' => 'MISSING_FIELDS', 'message' => 'token et username requis')));
            mysql_close($dblink); exit;
        }
        $ok = enregistrerAppareilPush($uname, $token, $platform, $dblink);
        echo json_encode(array('success' => (bool)$ok, 'data' => array('ok' => (bool)$ok)));
        mysql_close($dblink); exit;
    }

    echo json_encode(array('success' => false, 'error' => array('code' => 'NOT_FOUND', 'message' => 'Chat endpoint inconnu')));
    mysql_close($dblink); exit;
}

// === PUSH (enregistrement d'appareil) ===
if ($resource == 'push' && isset($segments[1]) && $segments[1] == 'register' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = file_get_contents('php://input');
    preg_match('/"username"\s*:\s*"([^"]+)"/', $input, $u);
    preg_match('/"token"\s*:\s*"([^"]+)"/', $input, $t);
    preg_match('/"plateforme"\s*:\s*"([^"]+)"/', $input, $pf);
    $username = isset($u[1]) ? $u[1] : '';
    $token = isset($t[1]) ? $t[1] : '';
    $plateforme = isset($pf[1]) ? $pf[1] : 'android';
    if (empty($username) || empty($token)) {
        echo json_encode(array('success' => false, 'error' => array('code' => 'MISSING_FIELDS', 'message' => 'username et token requis')));
        mysql_close($dblink); exit;
    }
    include_once(__DIR__ . '/../../push.inc.php');
    $ok = enregistrerAppareilPush($username, $token, $plateforme, $dblink);
    echo json_encode(array('success' => (bool)$ok));
    mysql_close($dblink); exit;
}

// === RESOURCES ===
if ($resource == 'resources') {
    $type = isset($segments[1]) ? $segments[1] : '';

    if ($type == 'rules') {
        echo json_encode(array('success' => true, 'data' => array('url' => 'https://www.fivb.com/wp-content/uploads/2025/06/FIVB-Volleyball_Rules2025_2028-FR-v04.pdf')));
    } elseif ($type == 'competlib') {
        echo json_encode(array('success' => true, 'data' => array('url' => 'https://www.ffvbbeach.org/ffvbapp/resu/vbspo_calendrier_export.php')));
    } elseif ($type == 'ufolep') {
        echo json_encode(array('success' => true, 'data' => array('url' => 'https://www.ufolep44.com/resultats/resultats-volley-ball')));
    } else {
        echo json_encode(array('success' => false, 'error' => array('code' => 'NOT_FOUND', 'message' => 'Resource not found')));
    }
    mysql_close($dblink);
    exit;
}

// Endpoint non trouvé
echo json_encode(array('success' => false, 'error' => array('code' => 'NOT_FOUND', 'message' => 'Endpoint not found')));
mysql_close($dblink);
?>
