<?php
/**
 * Classe EventRepository - Accès aux données des événements
 */
class EventRepository {

    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Récupère tous les événements après 2019, non inactifs
     */
    public function findAll() {
        $connection = $this->db->getConnection();

        $stmt = $connection->prepare(
            "SELECT DateHeure, Libelle, Etat, Titre, Intitule, Lieu, Adresse,
                    Adversaire, Analyse, InscritsMax
             FROM NPVB_Evenements
             WHERE DateHeure > 20190000000000 AND etat != 'I'
             ORDER BY DateHeure ASC"
        );

        $stmt->execute();
        $result = $stmt->get_result();

        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = $this->cleanEventData($row);
        }

        $stmt->close();

        return $events;
    }

    /**
     * Récupère un événement spécifique
     */
    public function findByDateAndLibelle($dateHeure, $libelle) {
        $connection = $this->db->getConnection();

        $stmt = $connection->prepare(
            "SELECT DateHeure, Libelle, Etat, Titre, Intitule, Lieu, Adresse,
                    Adversaire, Analyse, InscritsMax
             FROM NPVB_Evenements
             WHERE DateHeure = ? AND Libelle = ?"
        );

        $stmt->bind_param('ss', $dateHeure, $libelle);
        $stmt->execute();

        $result = $stmt->get_result();
        $event = $result->fetch_assoc();

        $stmt->close();

        return $event ? $this->cleanEventData($event) : null;
    }

    /**
     * Récupère le nombre maximum d'inscrits pour un événement
     */
    public function getMaxSubscribers($dateHeure, $libelle) {
        $connection = $this->db->getConnection();

        $stmt = $connection->prepare(
            "SELECT InscritsMax
             FROM NPVB_Evenements
             WHERE DateHeure = ? AND Libelle = ?"
        );

        $stmt->bind_param('ss', $dateHeure, $libelle);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $stmt->close();

        return $row ? (int)$row['InscritsMax'] : 0;
    }

    /**
     * Nettoie les données d'un événement
     */
    private function cleanEventData($event) {
        foreach ($event as $key => $value) {
            if (is_string($value)) {
                // Supprimer les caractères de contrôle
                $value = preg_replace('/[\x00-\x1F\x7F]/', '', $value);
                // Trim les espaces
                $event[$key] = trim($value);
            }
        }
        return $event;
    }
}
