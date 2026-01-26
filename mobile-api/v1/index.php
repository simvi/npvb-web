<?php
/**
 * API REST NPVB v1 - Compatible PHP 4.4.3 (Free.fr)
 * Architecture REST moderne adaptée aux contraintes PHP 4
 */

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
                return '"' . addslashes($data) . '"';
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

// Configuration DB
$DB_HOST = 'ftpperso.free.fr';
$DB_NAME = 'nantespvb';
$DB_USER = 'nantespvb';
$DB_PASS = 'wozd7pdo';
$TOKEN_SECRET = 'npvb_secret_2025_CHANGEZ_MOI';

// Connexion DB
$dblink = mysql_connect($DB_HOST, $DB_USER, $DB_PASS);
if (!$dblink) {
    echo json_encode(array('success' => false, 'error' => array('code' => 'DB_ERROR', 'message' => 'Database connection failed')));
    exit;
}

mysql_select_db($DB_NAME, $dblink);
mysql_query("SET CHARACTER SET utf8", $dblink);
mysql_query("SET NAMES utf8", $dblink);

// Récupérer endpoint
$endpoint = isset($_GET['endpoint']) ? trim($_GET['endpoint'], '/') : '';

// Page d'accueil API
if (empty($endpoint)) {
    echo json_encode(array(
        'success' => true,
        'data' => array(
            'name' => 'NPVB API',
            'version' => 'v1',
            'status' => 'online',
            'php' => phpversion()
        ),
        'message' => 'NPVB API v1'
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
    $query = "SELECT Pseudonyme, DieuToutPuissant FROM NPVB_Joueurs
              WHERE etat='V' AND Pseudonyme='$username' AND Password=OLD_PASSWORD('$password')";
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
                    'isAdmin' => ($user['DieuToutPuissant'] == 'o')
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
        $query = "SELECT Pseudonyme, DieuToutPuissant, Nom, Prenom, Sexe, DateNaissance,
                         Profession, Adresse, CPVille, Telephones, Email, Accord, NumeroLicence
                  FROM NPVB_Joueurs WHERE etat='V' AND Pseudonyme='$username'";
        $result = mysql_query($query);

        if ($result && mysql_num_rows($result) > 0) {
            $member = mysql_fetch_assoc($result);

            // Ajouter le nom de fichier Photo (pas stocké en DB)
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
        $query = "SELECT Pseudonyme, DieuToutPuissant, Nom, Prenom, Sexe, DateNaissance,
                         Profession, Adresse, CPVille, Telephones, Email, Accord, NumeroLicence
                  FROM NPVB_Joueurs WHERE etat='V' ORDER BY Nom, Prenom";
        $result = mysql_query($query);
        $data = array();

        while ($row = mysql_fetch_assoc($result)) {
            // Ajouter le nom de fichier Photo (pas stocké en DB)
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
        // GET /events/{date}/presences
        $dateHeure = mysql_real_escape_string($dateHeure);
        $query = "SELECT Joueur, Libelle, DateHeure, Prevue FROM NPVB_Presence
                  WHERE DateHeure='$dateHeure' ORDER BY Joueur";
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
                         Adversaire, Domicile, Resultat, Analyse, InscritsMax FROM NPVB_Evenements
                  WHERE DateHeure='$dateHeure' AND Libelle='$libelle'";
        $result = mysql_query($query);

        if ($result && mysql_num_rows($result) > 0) {
            echo json_encode(array('success' => true, 'data' => mysql_fetch_assoc($result)));
        } else {
            echo json_encode(array('success' => false, 'error' => array('code' => 'NOT_FOUND', 'message' => 'Event not found')));
        }
    } else {
        // GET /events (tous)
        $query = "SELECT DateHeure, Libelle, Etat, Titre, Intitule, Lieu, Adresse,
                         Adversaire, Domicile, Resultat, Analyse, InscritsMax FROM NPVB_Evenements
                  WHERE DateHeure > 20190000000000 AND etat != 'I' ORDER BY DateHeure ASC";
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

    // Vérifier existence
    $checkQuery = "SELECT * FROM NPVB_Presence WHERE Joueur='$joueur' AND DateHeure='$dateHeure' AND Libelle='$libelle'";
    $exists = mysql_num_rows(mysql_query($checkQuery)) > 0;

    if ($presence == 'n') {
        // DESINSCRIPTION
        if ($exists) {
            mysql_query("DELETE FROM NPVB_Presence WHERE Joueur='$joueur' AND DateHeure='$dateHeure' AND Libelle='$libelle'");
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
