<?php
/**
 * Classe PresencesController - Contrôleur des présences
 */
class PresencesController {

    private $presenceService;

    public function __construct() {
        $this->presenceService = new PresenceService();
    }

    /**
     * GET /api/v1/events/{dateHeure}/presences
     * Récupère les présences pour un événement
     */
    public function getByEvent($dateHeure) {
        // Vérifier la méthode HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::methodNotAllowed(['GET']);
        }

        // Authentifier
        Auth::authenticate();

        // Appeler le service
        $result = $this->presenceService->getPresencesByEvent($dateHeure);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                Response::validationError($result['errors']);
            } else {
                Response::error($result['message'] ?? 'Failed to fetch presences', ERROR_INTERNAL);
            }
        }

        Response::success($result['data']);
    }

    /**
     * GET /api/v1/members/{pseudo}/presences
     * Récupère les présences d'un joueur avec un statut donné
     * Query params: ?status=o ou ?status=n
     */
    public function getByPlayer($pseudo) {
        // Vérifier la méthode HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::methodNotAllowed(['GET']);
        }

        // Authentifier
        Auth::authenticate();

        // Récupérer le paramètre status
        $status = isset($_GET['status']) ? $_GET['status'] : null;

        if (!$status) {
            Response::error('Missing required parameter: status', ERROR_INVALID_INPUT, 400);
        }

        // Appeler le service
        $result = $this->presenceService->getPresencesByPlayerAndStatus($pseudo, $status);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                Response::validationError($result['errors']);
            } else {
                Response::error($result['message'] ?? 'Failed to fetch presences', ERROR_INTERNAL);
            }
        }

        Response::success($result['data']);
    }

    /**
     * POST /api/v1/presences
     * Crée/met à jour une présence (inscription/désinscription/absence)
     */
    public function manage() {
        // Vérifier la méthode HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::methodNotAllowed(['POST']);
        }

        // Authentifier
        Auth::authenticate();

        // Récupérer les données POST
        $input = $this->getJsonInput();

        if (!$input) {
            Response::error('Invalid JSON input', ERROR_INVALID_INPUT, 400);
        }

        $dateHeure = isset($input['dateHeure']) ? $input['dateHeure'] : null;
        $joueur = isset($input['joueur']) ? trim($input['joueur']) : null;
        $libelle = isset($input['libelle']) ? trim($input['libelle']) : null;
        $presence = isset($input['presence']) ? $input['presence'] : null;

        // Appeler le service
        $result = $this->presenceService->managePresence($dateHeure, $joueur, $libelle, $presence);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                Response::validationError($result['errors']);
            } else {
                $httpCode = 400;
                if (isset($result['code'])) {
                    switch ($result['code']) {
                        case ERROR_CAPACITY_REACHED:
                        case ERROR_ALREADY_REGISTERED:
                        case ERROR_NOT_REGISTERED:
                            $httpCode = 409; // Conflict
                            break;
                    }
                }
                Response::error(
                    $result['message'] ?? 'Failed to manage presence',
                    $result['code'] ?? ERROR_INTERNAL,
                    $httpCode,
                    $result['details'] ?? null
                );
            }
        }

        Response::success(
            ['status' => $result['success']],
            $result['message'] ?? 'Presence managed successfully'
        );
    }

    /**
     * Récupère et décode les données JSON depuis le body
     */
    private function getJsonInput() {
        $input = file_get_contents('php://input');
        return json_decode($input, true);
    }
}
