<?php
/**
 * Classe EventService - Logique métier des événements
 */
class EventService {

    private $eventRepository;

    public function __construct() {
        $this->eventRepository = new EventRepository();
    }

    /**
     * Récupère tous les événements
     */
    public function getAllEvents() {
        $events = $this->eventRepository->findAll();

        return [
            'success' => true,
            'data' => $events
        ];
    }

    /**
     * Récupère un événement spécifique
     */
    public function getEventByDateAndLibelle($dateHeure, $libelle) {
        // Valider les inputs
        $validator = new Validator(['dateHeure' => $dateHeure, 'libelle' => $libelle]);
        $validator->required('dateHeure')
                  ->required('libelle')
                  ->mysqlDateTime('dateHeure');

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->getErrors()
            ];
        }

        $event = $this->eventRepository->findByDateAndLibelle($dateHeure, $libelle);

        if (!$event) {
            return [
                'success' => false,
                'message' => 'Event not found'
            ];
        }

        return [
            'success' => true,
            'data' => $event
        ];
    }
}
