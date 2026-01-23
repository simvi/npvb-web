<?php
/**
 * Classe EventsController - Contrôleur des événements
 */
class EventsController {

    private $eventService;

    public function __construct() {
        $this->eventService = new EventService();
    }

    /**
     * GET /api/v1/events
     * Récupère tous les événements
     */
    public function getAll() {
        // Vérifier la méthode HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::methodNotAllowed(['GET']);
        }

        // Authentifier
        Auth::authenticate();

        // Appeler le service
        $result = $this->eventService->getAllEvents();

        if (!$result['success']) {
            Response::error($result['message'] ?? 'Failed to fetch events', ERROR_INTERNAL);
        }

        Response::success($result['data']);
    }

    /**
     * GET /api/v1/events/{dateHeure}/{libelle}
     * Récupère un événement spécifique
     */
    public function getByDateAndLibelle($dateHeure, $libelle) {
        // Vérifier la méthode HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::methodNotAllowed(['GET']);
        }

        // Authentifier
        Auth::authenticate();

        // Appeler le service
        $result = $this->eventService->getEventByDateAndLibelle($dateHeure, $libelle);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                Response::validationError($result['errors']);
            } else {
                Response::notFound($result['message'] ?? 'Event not found');
            }
        }

        Response::success($result['data']);
    }
}
