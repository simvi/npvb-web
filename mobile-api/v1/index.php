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

// Statut admin calculé depuis les rôles (remplace l'ancienne colonne DieuToutPuissant)
function estAdminParRole($pseudo) {
    $p = mysql_real_escape_string($pseudo);
    $r = mysql_query("SELECT 1 FROM NPVB_JoueurRoles WHERE Pseudonyme='$p' AND Role='admin' LIMIT 1");
    return ($r && mysql_num_rows($r) > 0);
}

// Capacité 'poster_annonce' = rôle admin ou redacteur (cf. permissions.inc.php)
function peutPosterChatParRole($pseudo) {
    $p = mysql_real_escape_string($pseudo);
    $r = mysql_query("SELECT 1 FROM NPVB_JoueurRoles WHERE Pseudonyme='$p' AND Role IN ('admin','redacteur') LIMIT 1");
    return ($r && mysql_num_rows($r) > 0);
}

// Nombre de messages non lus d'une conversation pour un membre (exclut ses propres messages)
function chatNonLus($pseudo, $convId) {
    $p = mysql_real_escape_string($pseudo);
    $c = (int)$convId;
    $sql = "SELECT COUNT(*) AS n FROM NPVB_MessagesChat m
            LEFT JOIN NPVB_MessagesLus l ON l.Conversation=m.Conversation AND l.Joueur='$p'
            WHERE m.Conversation=$c AND m.Supprime='n' AND m.Auteur<>'$p'
              AND m.Id > COALESCE(l.DernierLuId, 0)";
    $r = mysql_query($sql);
    if ($r) { $row = mysql_fetch_assoc($r); return (int)$row['n']; }
    return 0;
}

// Accès d'un membre à une conversation (miroir de permissions.inc.php)
function mobileConvAccessible($pseudo, $convId) {
    $p = mysql_real_escape_string($pseudo);
    $c = (int)$convId;
    $conv = mysql_fetch_object(mysql_query("SELECT Type, Equipe FROM NPVB_Conversations WHERE Id=$c"));
    if (!$conv) return false;
    if ($conv->Type == 'generale') return true;
    if ($conv->Type == 'equipe') {
        $eq = mysql_real_escape_string($conv->Equipe);
        $r = mysql_query("SELECT 1 FROM NPVB_Appartenance WHERE Joueur='$p' AND Equipe='$eq'
                          UNION SELECT 1 FROM NPVB_Equipes WHERE Nom='$eq' AND (Responsable='$p' OR Supleant='$p') LIMIT 1");
        return ($r && mysql_num_rows($r) > 0);
    }
    $r = mysql_query("SELECT 1 FROM NPVB_ConversationMembres WHERE Conversation=$c AND Joueur='$p' LIMIT 1");
    return ($r && mysql_num_rows($r) > 0);
}

// Peut poster (accès + non archivée + capacité). PosterCapacite NULL = participants.
function mobilePeutPoster($pseudo, $convId) {
    $c = (int)$convId;
    $conv = mysql_fetch_object(mysql_query("SELECT PosterCapacite, Archive FROM NPVB_Conversations WHERE Id=$c"));
    if (!$conv || $conv->Archive == 'o') return false;
    if (!mobileConvAccessible($pseudo, $convId)) return false;
    if (!$conv->PosterCapacite) return true;
    if ($conv->PosterCapacite == 'poster_annonce') return peutPosterChatParRole($pseudo);
    return false;
}

// Conversations accessibles au membre (id, type, nom, archive, nonlus, peutPoster)
function mobileConvsAccessibles($pseudo) {
    $p = mysql_real_escape_string($pseudo);
    $ids = array();
    $r = mysql_query("SELECT Id FROM NPVB_Conversations WHERE Type='generale'");
    while ($x = mysql_fetch_object($r)) $ids[(int)$x->Id] = true;
    $r = mysql_query("SELECT c.Id FROM NPVB_Conversations c WHERE c.Type='equipe' AND (
                        c.Equipe IN (SELECT Equipe FROM NPVB_Appartenance WHERE Joueur='$p')
                        OR c.Equipe IN (SELECT Nom FROM NPVB_Equipes WHERE Responsable='$p' OR Supleant='$p'))");
    while ($x = mysql_fetch_object($r)) $ids[(int)$x->Id] = true;
    $r = mysql_query("SELECT Conversation AS Id FROM NPVB_ConversationMembres WHERE Joueur='$p'");
    while ($x = mysql_fetch_object($r)) $ids[(int)$x->Id] = true;
    if (empty($ids)) return array();
    $in = implode(',', array_keys($ids));
    $res = mysql_query("SELECT * FROM NPVB_Conversations WHERE Id IN ($in) ORDER BY Archive, FIELD(Type,'generale','equipe','bureau','prive'), Nom");
    $out = array();
    while ($c = mysql_fetch_object($res)) {
        $nom = $c->Nom;
        if ($c->Type == 'prive') {
            $rr = mysql_query("SELECT j.Prenom, j.Nom, j.Pseudonyme FROM NPVB_ConversationMembres cm
                               JOIN NPVB_Joueurs j ON j.Pseudonyme=cm.Joueur
                               WHERE cm.Conversation=".(int)$c->Id." AND cm.Joueur<>'$p' LIMIT 1");
            if ($rr && ($jj = mysql_fetch_object($rr))) { $n = trim($jj->Prenom.' '.$jj->Nom); $nom = ($n != '') ? $n : $jj->Pseudonyme; }
        }
        $out[] = array(
            'id' => (int)$c->Id, 'type' => $c->Type, 'nom' => $nom,
            'archive' => ($c->Archive == 'o'),
            'nonlus' => ($c->Archive == 'o') ? 0 : chatNonLus($pseudo, $c->Id),
            'peutPoster' => mobilePeutPoster($pseudo, $c->Id)
        );
    }
    return $out;
}

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
        $token = md5($username . time() . $TOKEN_SECRET);
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
        while ($row = mysql_fetch_assoc($result)) $data[] = $row;

        echo json_encode(array('success' => true, 'data' => $data));
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
    preg_match('/"presence"\s*:\s*"([^"]+)"/', $input, $pres_match);

    $dateHeure = isset($date_match[1]) ? mysql_real_escape_string($date_match[1]) : '';
    $joueur = isset($joueur_match[1]) ? mysql_real_escape_string($joueur_match[1]) : '';
    $libelle = isset($libelle_match[1]) ? mysql_real_escape_string($libelle_match[1]) : '';
    $presence = isset($pres_match[1]) ? $pres_match[1] : '';

    // Validation des champs requis pour rétrocompatibilité
    if (empty($dateHeure) || empty($joueur) || empty($presence)) {
        echo json_encode(array(
            'success' => false,
            'error' => array(
                'code' => 'MISSING_FIELDS',
                'message' => 'Champs requis manquants: dateHeure, joueur, presence'
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

    // Vérifier existence
    $checkQuery = "SELECT * FROM NPVB_Presence WHERE Joueur='$joueur' AND DateHeure='$dateHeure' AND Libelle='$libelle'";
    $exists = mysql_num_rows(mysql_query($checkQuery)) > 0;

    if ($presence == 'n') {
        // DESINSCRIPTION
        if ($exists) {
            mysql_query("DELETE FROM NPVB_Presence WHERE Joueur='$joueur' AND DateHeure='$dateHeure' AND Libelle='$libelle'");
            if (function_exists('promouvoirListeAttente')) promouvoirListeAttente($dateHeure, $libelle, $dblink);
            echo json_encode(array('success' => true, 'data' => array('status' => true), 'message' => 'Désinscription réussie'));
        } else {
            echo json_encode(array('success' => false, 'error' => array('code' => 'NOT_REGISTERED', 'message' => 'Présence non enregistrée')));
        }
    } elseif ($presence == '!') {
        // ABSENT
        $prevue = 'n';
        if ($exists) {
            mysql_query("UPDATE NPVB_Presence SET Prevue='$prevue' WHERE Joueur='$joueur' AND DateHeure='$dateHeure' AND Libelle='$libelle'");
        } else {
            mysql_query("INSERT INTO NPVB_Presence (Joueur, DateHeure, Libelle, Prevue) VALUES ('$joueur', '$dateHeure', '$libelle', '$prevue')");
        }
        if (function_exists('promouvoirListeAttente')) promouvoirListeAttente($dateHeure, $libelle, $dblink);
        echo json_encode(array('success' => true, 'data' => array('status' => true), 'message' => 'Absence enregistrée'));
    } elseif ($presence == 'o') {
        // PRESENT - Vérifier capacité pour SEANCE
        if ($libelle == 'SEANCE') {
            $countQuery = "SELECT COUNT(*) as count FROM NPVB_Presence
                           WHERE DateHeure='$dateHeure' AND Libelle='$libelle' AND Prevue='o'";
            $countResult = mysql_query($countQuery);
            $countRow = mysql_fetch_assoc($countResult);
            $currentCount = $countRow['count'];

            $maxQuery = "SELECT InscritsMax FROM NPVB_Evenements
                         WHERE DateHeure='$dateHeure' AND Libelle='$libelle'";
            $maxResult = mysql_query($maxQuery);
            $maxRow = mysql_fetch_assoc($maxResult);
            $maxCount = $maxRow['InscritsMax'];

            if (!$exists && $currentCount >= $maxCount) {
                echo json_encode(array(
                    'success' => false,
                    'error' => array(
                        'code' => 'CAPACITY_REACHED',
                        'message' => "Nombre d'inscrits maximum déjà atteint"
                    )
                ));
                mysql_close($dblink);
                exit;
            }
        }

        $prevue = 'o';
        if ($exists) {
            mysql_query("UPDATE NPVB_Presence SET Prevue='$prevue' WHERE Joueur='$joueur' AND DateHeure='$dateHeure' AND Libelle='$libelle'");
        } else {
            mysql_query("INSERT INTO NPVB_Presence (Joueur, DateHeure, Libelle, Prevue) VALUES ('$joueur', '$dateHeure', '$libelle', '$prevue')");
        }
        echo json_encode(array('success' => true, 'data' => array('status' => true), 'message' => 'Inscription réussie'));
    } else {
        echo json_encode(array('success' => false, 'error' => array('code' => 'INVALID_INPUT', 'message' => 'Valeur presence invalide')));
    }
    mysql_close($dblink);
    exit;
}

// === CHAT ===
if ($resource == 'chat') {
    $sousRes = isset($segments[1]) ? $segments[1] : '';

    // Conversation : param conv, défaut = conversation 'generale'
    $convId = isset($_GET['conv']) ? (int)$_GET['conv'] : 0;
    if (!$convId) {
        $cr = mysql_query("SELECT Id FROM NPVB_Conversations WHERE Type='generale' ORDER BY Id LIMIT 1");
        if ($cr && mysql_num_rows($cr) > 0) { $crow = mysql_fetch_assoc($cr); $convId = (int)$crow['Id']; }
    }

    // GET /chat/conversations?username=XXX  (toutes les conversations accessibles)
    if ($sousRes == 'conversations' && $_SERVER['REQUEST_METHOD'] != 'POST') {
        $username = isset($_GET['username']) ? $_GET['username'] : '';
        $convs = $username ? mobileConvsAccessibles($username) : array();
        echo json_encode(array('success' => true, 'data' => $convs));
        mysql_close($dblink); exit;
    }

    // GET /chat/messages?conv=&since=&username=
    if ($sousRes == 'messages' && $_SERVER['REQUEST_METHOD'] != 'POST') {
        $since = isset($_GET['since']) ? (int)$_GET['since'] : 0;
        $username = isset($_GET['username']) ? $_GET['username'] : '';
        if (!$username || !mobileConvAccessible($username, $convId)) {
            echo json_encode(array('success' => false, 'error' => array('code' => 'FORBIDDEN', 'message' => 'Accès refusé à cette conversation')));
            mysql_close($dblink); exit;
        }
        $q = "SELECT m.Id, m.Auteur, m.Contenu, m.DateEnvoi, j.Prenom, j.Nom
              FROM NPVB_MessagesChat m LEFT JOIN NPVB_Joueurs j ON j.Pseudonyme=m.Auteur
              WHERE m.Conversation=$convId AND m.Supprime='n' AND m.Id > $since
              ORDER BY m.Id ASC";
        $r = mysql_query($q);
        $msgs = array();
        while ($row = mysql_fetch_assoc($r)) {
            $nom = trim($row['Prenom'].' '.$row['Nom']); if ($nom == '') $nom = $row['Auteur'];
            $msgs[] = array(
                'id' => (int)$row['Id'], 'auteur' => $row['Auteur'], 'nom' => $nom,
                'contenu' => $row['Contenu'], 'dateEnvoi' => $row['DateEnvoi'],
                'moi' => ($username && $row['Auteur'] == $username)
            );
        }
        echo json_encode(array('success' => true, 'data' => array(
            'conversation' => $convId, 'messages' => $msgs,
            'nonlus' => $username ? chatNonLus($username, $convId) : 0
        )));
        mysql_close($dblink); exit;
    }

    // POST /chat/messages  (body: conv, contenu, username)
    if ($sousRes == 'messages' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $input = file_get_contents('php://input');
        preg_match('/"username"\s*:\s*"([^"]+)"/', $input, $u);
        preg_match('/"contenu"\s*:\s*"(.*?)"\s*[,}]/s', $input, $c);
        preg_match('/"conv"\s*:\s*(\d+)/', $input, $cv);
        $username = isset($u[1]) ? $u[1] : '';
        $contenu = isset($c[1]) ? trim($c[1]) : '';
        if (isset($cv[1])) $convId = (int)$cv[1];
        if (empty($username) || $contenu == '') {
            echo json_encode(array('success' => false, 'error' => array('code' => 'MISSING_FIELDS', 'message' => 'username et contenu requis')));
            mysql_close($dblink); exit;
        }
        if (!mobilePeutPoster($username, $convId)) {
            echo json_encode(array('success' => false, 'error' => array('code' => 'FORBIDDEN', 'message' => 'Publication non autorisée dans cette conversation')));
            mysql_close($dblink); exit;
        }
        $ue = mysql_real_escape_string($username);
        $ce = mysql_real_escape_string($contenu);
        if (mysql_query("INSERT INTO NPVB_MessagesChat (Conversation, Auteur, Contenu, DateEnvoi) VALUES ($convId, '$ue', '$ce', NOW())")) {
            $newId = mysql_insert_id();
            // Notification push aux autres membres (no-op si FCM non configuré)
            include_once(__DIR__ . '/../../push.inc.php');
            $cr = mysql_query("SELECT Nom FROM NPVB_Conversations WHERE Id=$convId");
            $cn = ($cr && mysql_num_rows($cr) > 0) ? mysql_fetch_assoc($cr) : array('Nom' => 'Annonce');
            $apercu = (strlen($contenu) > 80) ? substr($contenu, 0, 77) . '...' : $contenu;
            envoyerPush(destinatairesChat($convId, $username, $dblink), $cn['Nom'], $apercu, $dblink, array('conv' => $convId, 'type' => 'chat'));
            echo json_encode(array('success' => true, 'data' => array('id' => $newId)));
        } else {
            echo json_encode(array('success' => false, 'error' => array('code' => 'DB_ERROR', 'message' => 'Enregistrement impossible')));
        }
        mysql_close($dblink); exit;
    }

    // POST /chat/read  (body: conv, lastid, username)
    if ($sousRes == 'read' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $input = file_get_contents('php://input');
        preg_match('/"username"\s*:\s*"([^"]+)"/', $input, $u);
        preg_match('/"lastid"\s*:\s*(\d+)/', $input, $li);
        preg_match('/"conv"\s*:\s*(\d+)/', $input, $cv);
        $username = isset($u[1]) ? $u[1] : '';
        $lastid = isset($li[1]) ? (int)$li[1] : 0;
        if (isset($cv[1])) $convId = (int)$cv[1];
        if (empty($username) || !mobileConvAccessible($username, $convId)) {
            echo json_encode(array('success' => false, 'error' => array('code' => 'FORBIDDEN', 'message' => 'Accès refusé à cette conversation')));
            mysql_close($dblink); exit;
        }
        $ue = mysql_real_escape_string($username);
        mysql_query("INSERT INTO NPVB_MessagesLus (Joueur, Conversation, DernierLuId) VALUES ('$ue', $convId, $lastid)
                     ON DUPLICATE KEY UPDATE DernierLuId=GREATEST(DernierLuId, $lastid)");
        echo json_encode(array('success' => true, 'data' => array('nonlus' => chatNonLus($username, $convId))));
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

// === MESSAGES (news du club) ===
if ($resource == 'messages') {
    $query = "SELECT id, title, content, created_at FROM NPVB_Messages
              WHERE is_active = 1 ORDER BY created_at DESC";
    $result = mysql_query($query);
    $data = array();
    while ($row = mysql_fetch_assoc($result)) $data[] = $row;

    echo json_encode(array('success' => true, 'data' => $data));
    mysql_close($dblink);
    exit;
}

// Endpoint non trouvé
echo json_encode(array('success' => false, 'error' => array('code' => 'NOT_FOUND', 'message' => 'Endpoint not found')));
mysql_close($dblink);
?>
