<?php
/**
 * Classe PresenceService - Logique métier des présences
 */
class PresenceService {

    private $presenceRepository;
    private $eventRepository;

    public function __construct() {
        $this->presenceRepository = new PresenceRepository();
        $this->eventRepository = new EventRepository();
    }

    /**
     * Récupère les présences pour un événement
     */
    public function getPresencesByEvent($dateHeure) {
        // Valider l'input
        $validator = new Validator(['dateHeure' => $dateHeure]);
        $validator->required('dateHeure')
                  ->mysqlDateTime('dateHeure');

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->getErrors()
            ];
        }

        $presences = $this->presenceRepository->findByEvent($dateHeure);

        return [
            'success' => true,
            'data' => $presences
        ];
    }

    /**
     * Récupère les présences d'un joueur avec un statut donné
     */
    public function getPresencesByPlayerAndStatus($joueur, $prevue) {
        // Valider les inputs
        $validator = new Validator(['joueur' => $joueur, 'prevue' => $prevue]);
        $validator->required('joueur')
                  ->required('prevue')
                  ->username('joueur')
                  ->in('prevue', ['o', 'n']);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->getErrors()
            ];
        }

        $presences = $this->presenceRepository->findByPlayerAndStatus($joueur, $prevue);

        return [
            'success' => true,
            'data' => $presences
        ];
    }

    /**
     * Gère l'inscription/désinscription d'un joueur à un événement
     * Logique complexe issue de flux_v3.php
     */
    public function managePresence($dateHeure, $joueur, $libelle, $presence) {
        // Valider les inputs
        $validator = new Validator([
            'dateHeure' => $dateHeure,
            'joueur' => $joueur,
            'libelle' => $libelle,
            'presence' => $presence
        ]);

        $validator->required('dateHeure')
                  ->required('joueur')
                  ->required('libelle')
                  ->required('presence')
                  ->mysqlDateTime('dateHeure')
                  ->username('joueur')
                  ->in('presence', ['o', 'n', '!']);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->getErrors()
            ];
        }

        // Vérifier si la présence existe déjà
        $exists = $this->presenceRepository->exists($joueur, $dateHeure, $libelle);

        // Gestion selon le type de présence
        switch ($presence) {
            case 'n': // DESINSCRIPTION
                return $this->handleUnregistration($exists, $joueur, $dateHeure, $libelle);

            case '!': // ABSENT
                return $this->handleAbsence($exists, $joueur, $dateHeure, $libelle);

            case 'o': // PRESENT
                return $this->handleRegistration($exists, $joueur, $dateHeure, $libelle);

            default:
                return [
                    'success' => false,
                    'message' => 'Invalid presence value'
                ];
        }
    }

    /**
     * Gère la désinscription (suppression de la présence)
     */
    private function handleUnregistration($exists, $joueur, $dateHeure, $libelle) {
        if (!$exists) {
            return [
                'success' => false,
                'message' => 'Votre présence n\'était pas enregistrée.',
                'code' => ERROR_NOT_REGISTERED
            ];
        }

        $success = $this->presenceRepository->delete($joueur, $dateHeure, $libelle);

        return [
            'success' => $success,
            'message' => $success ? 'Désinscription réussie' : 'Erreur lors de la désinscription'
        ];
    }

    /**
     * Gère le marquage d'absence
     */
    private function handleAbsence($exists, $joueur, $dateHeure, $libelle) {
        if ($exists) {
            // Mettre à jour avec Prevue='n'
            $success = $this->presenceRepository->update($joueur, $dateHeure, $libelle, 'n');
        } else {
            // Insérer avec Prevue='n'
            $success = $this->presenceRepository->create($joueur, $dateHeure, $libelle, 'n');
        }

        return [
            'success' => $success,
            'message' => $success ? 'Absence enregistrée' : 'Erreur lors de l\'enregistrement'
        ];
    }

    /**
     * Gère l'inscription (avec vérification de capacité pour SEANCE)
     */
    private function handleRegistration($exists, $joueur, $dateHeure, $libelle) {
        // Vérifier la capacité uniquement pour les SEANCES
        if ($libelle === 'SEANCE') {
            $currentCount = $this->presenceRepository->countPresentByEvent($dateHeure, $libelle);
            $maxSubscribers = $this->eventRepository->getMaxSubscribers($dateHeure, $libelle);

            // Si déjà inscrit, on ne compte pas dans la capacité
            if (!$exists && $currentCount >= $maxSubscribers) {
                return [
                    'success' => false,
                    'message' => 'Nombre d\'inscrits maximum déjà atteint',
                    'code' => ERROR_CAPACITY_REACHED,
                    'details' => [
                        'current' => $currentCount,
                        'max' => $maxSubscribers
                    ]
                ];
            }
        }

        if ($exists) {
            // Mettre à jour avec Prevue='o'
            $success = $this->presenceRepository->update($joueur, $dateHeure, $libelle, 'o');
        } else {
            // Insérer avec Prevue='o'
            $success = $this->presenceRepository->create($joueur, $dateHeure, $libelle, 'o');
        }

        return [
            'success' => $success,
            'message' => $success ? 'Inscription réussie' : 'Erreur lors de l\'inscription'
        ];
    }
}
