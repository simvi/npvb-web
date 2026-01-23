<?php
/**
 * API REST v1 - Point d'entrée principal (Front Controller)
 *
 * Format des URLs sans mod_rewrite:
 * /api/v1/index.php?endpoint=auth/login
 * /api/v1/index.php?endpoint=members
 * /api/v1/index.php?endpoint=events/20250122120000/presences
 *
 * Avec mod_rewrite (.htaccess):
 * /api/v1/auth/login
 * /api/v1/members
 * /api/v1/events/20250122120000/presences
 */

// Configuration de l'environnement
error_reporting(E_ALL);
ini_set('display_errors', 0); // Ne pas afficher les erreurs en production

// Gestion des erreurs fatales
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => 'INTERNAL_ERROR',
                'message' => 'Internal server error'
            ]
        ]);
    }
});

// Gestion des exceptions non catchées
set_exception_handler(function($exception) {
    error_log('Uncaught exception: ' . $exception->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => 'INTERNAL_ERROR',
            'message' => 'Internal server error'
        ]
    ]);
});

// Gestion de la requête OPTIONS pour CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 86400'); // 24 heures
    http_response_code(200);
    exit;
}

// Chargement des fichiers de configuration
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';

// Chargement des classes core
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Response.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Validator.php';

// Chargement des repositories
require_once __DIR__ . '/repositories/UserRepository.php';
require_once __DIR__ . '/repositories/MemberRepository.php';
require_once __DIR__ . '/repositories/EventRepository.php';
require_once __DIR__ . '/repositories/PresenceRepository.php';

// Chargement des services
require_once __DIR__ . '/services/AuthService.php';
require_once __DIR__ . '/services/MemberService.php';
require_once __DIR__ . '/services/EventService.php';
require_once __DIR__ . '/services/PresenceService.php';

// Chargement des controllers
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/MembersController.php';
require_once __DIR__ . '/controllers/EventsController.php';
require_once __DIR__ . '/controllers/PresencesController.php';
require_once __DIR__ . '/controllers/ResourcesController.php';

// Récupération de l'endpoint depuis les query params ou PATH_INFO
$endpoint = '';

// Méthode 1: Via query string (compatible avec tous les hébergements)
if (isset($_GET['endpoint'])) {
    $endpoint = trim($_GET['endpoint'], '/');
}
// Méthode 2: Via PATH_INFO (si mod_rewrite ou configuration serveur)
elseif (isset($_SERVER['PATH_INFO'])) {
    $endpoint = trim($_SERVER['PATH_INFO'], '/');
}
// Méthode 3: Parsing manuel de REQUEST_URI (fallback)
else {
    $requestUri = $_SERVER['REQUEST_URI'];
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $basePath = dirname($scriptName);

    // Retirer le base path de l'URI
    if (strpos($requestUri, $basePath) === 0) {
        $requestUri = substr($requestUri, strlen($basePath));
    }

    // Retirer index.php si présent
    $requestUri = str_replace('/index.php', '', $requestUri);

    // Retirer les query params
    if (($pos = strpos($requestUri, '?')) !== false) {
        $requestUri = substr($requestUri, 0, $pos);
    }

    $endpoint = trim($requestUri, '/');
}

// Si pas d'endpoint, retourner les informations de l'API
if (empty($endpoint)) {
    Response::success([
        'name' => 'NPVB API',
        'version' => API_VERSION,
        'status' => 'online',
        'endpoints' => [
            'POST /auth/login' => 'Authentification',
            'GET /auth/verify' => 'Vérification token',
            'GET /members' => 'Liste des membres',
            'GET /members/{username}' => 'Détail d\'un membre',
            'GET /memberships' => 'Appartenances aux équipes',
            'GET /events' => 'Liste des événements',
            'GET /events/{dateHeure}/{libelle}' => 'Détail d\'un événement',
            'GET /events/{dateHeure}/presences' => 'Présences pour un événement',
            'GET /members/{pseudo}/presences?status={o|n}' => 'Présences d\'un membre',
            'POST /presences' => 'Gestion présence (inscription/désinscription)',
            'GET /resources/rules' => 'Règles FIVB',
            'GET /resources/competlib' => 'Calendrier compétitions',
            'GET /resources/ufolep' => 'Résultats UFOLEP'
        ]
    ], 'NPVB API v1');
}

// Routeur simple
try {
    // Découper l'endpoint en segments
    $segments = explode('/', $endpoint);
    $resource = $segments[0] ?? '';

    // Router vers les contrôleurs appropriés
    switch ($resource) {

        // Routes d'authentification
        case 'auth':
            $controller = new AuthController();
            $action = $segments[1] ?? '';

            switch ($action) {
                case 'login':
                    $controller->login();
                    break;

                case 'verify':
                    $controller->verify();
                    break;

                default:
                    Response::notFound('Auth endpoint not found');
            }
            break;

        // Routes des membres
        case 'members':
            $controller = new MembersController();
            $username = $segments[1] ?? null;

            if ($username) {
                // Vérifier si c'est une route de présences
                if (isset($segments[2]) && $segments[2] === 'presences') {
                    $presencesController = new PresencesController();
                    $presencesController->getByPlayer($username);
                } else {
                    // Route normale: GET /members/{username}
                    $controller->getByUsername($username);
                }
            } else {
                // Route: GET /members
                $controller->getAll();
            }
            break;

        // Routes des appartenances
        case 'memberships':
            $controller = new MembersController();
            $controller->getMemberships();
            break;

        // Routes des événements
        case 'events':
            $controller = new EventsController();
            $dateHeure = $segments[1] ?? null;
            $libelle = $segments[2] ?? null;

            if ($dateHeure && $libelle) {
                // Vérifier si c'est une route de présences
                if ($libelle === 'presences') {
                    $presencesController = new PresencesController();
                    $presencesController->getByEvent($dateHeure);
                } else {
                    // Route: GET /events/{dateHeure}/{libelle}
                    $controller->getByDateAndLibelle($dateHeure, $libelle);
                }
            } elseif ($dateHeure && !$libelle) {
                // Peut-être: GET /events/{dateHeure}/presences
                $action = $segments[2] ?? null;
                if ($action === 'presences') {
                    $presencesController = new PresencesController();
                    $presencesController->getByEvent($dateHeure);
                } else {
                    Response::notFound('Event endpoint not found');
                }
            } else {
                // Route: GET /events
                $controller->getAll();
            }
            break;

        // Routes des présences
        case 'presences':
            $controller = new PresencesController();
            $controller->manage();
            break;

        // Routes des ressources externes
        case 'resources':
            $controller = new ResourcesController();
            $resourceType = $segments[1] ?? '';

            switch ($resourceType) {
                case 'rules':
                    $controller->getRules();
                    break;

                case 'competlib':
                    $controller->getCompetlib();
                    break;

                case 'ufolep':
                    $controller->getUfolep();
                    break;

                default:
                    Response::notFound('Resource not found');
            }
            break;

        default:
            Response::notFound('Endpoint not found');
    }

} catch (Exception $e) {
    // Log l'erreur
    error_log('API Error: ' . $e->getMessage());

    // Retourner une erreur générique
    Response::error('Internal server error', ERROR_INTERNAL, 500);
}
