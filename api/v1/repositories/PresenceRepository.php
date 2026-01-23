<?php
/**
 * Classe PresenceRepository - Accès aux données des présences
 */
class PresenceRepository {

    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Récupère les présences pour un événement donné
     */
    public function findByEvent($dateHeure) {
        $connection = $this->db->getConnection();

        $stmt = $connection->prepare(
            "SELECT Joueur, Libelle, DateHeure, Prevue
             FROM NPVB_Presence
             WHERE DateHeure = ?
             ORDER BY Joueur"
        );

        $stmt->bind_param('s', $dateHeure);
        $stmt->execute();

        $result = $stmt->get_result();

        $presences = [];
        while ($row = $result->fetch_assoc()) {
            $presences[] = $row;
        }

        $stmt->close();

        return $presences;
    }

    /**
     * Récupère les présences d'un joueur avec un statut donné
     */
    public function findByPlayerAndStatus($joueur, $prevue) {
        $connection = $this->db->getConnection();

        $stmt = $connection->prepare(
            "SELECT Joueur, Libelle, DateHeure, Prevue
             FROM NPVB_Presence
             WHERE Joueur = ? AND Prevue = ?
             ORDER BY DateHeure DESC"
        );

        $stmt->bind_param('ss', $joueur, $prevue);
        $stmt->execute();

        $result = $stmt->get_result();

        $presences = [];
        while ($row = $result->fetch_assoc()) {
            $presences[] = $row;
        }

        $stmt->close();

        return $presences;
    }

    /**
     * Vérifie si une présence existe
     */
    public function exists($joueur, $dateHeure, $libelle) {
        $connection = $this->db->getConnection();

        $stmt = $connection->prepare(
            "SELECT COUNT(*) as count
             FROM NPVB_Presence
             WHERE Joueur = ? AND DateHeure = ? AND Libelle = ?"
        );

        $stmt->bind_param('sss', $joueur, $dateHeure, $libelle);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $stmt->close();

        return $row['count'] > 0;
    }

    /**
     * Compte le nombre de présents pour un événement
     */
    public function countPresentByEvent($dateHeure, $libelle) {
        $connection = $this->db->getConnection();

        $stmt = $connection->prepare(
            "SELECT COUNT(*) as count
             FROM NPVB_Presence
             WHERE DateHeure = ? AND Libelle = ? AND Prevue = 'o'"
        );

        $stmt->bind_param('ss', $dateHeure, $libelle);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $stmt->close();

        return (int)$row['count'];
    }

    /**
     * Crée une nouvelle présence
     */
    public function create($joueur, $dateHeure, $libelle, $prevue) {
        $connection = $this->db->getConnection();

        $stmt = $connection->prepare(
            "INSERT INTO NPVB_Presence (Joueur, DateHeure, Libelle, Prevue)
             VALUES (?, ?, ?, ?)"
        );

        $stmt->bind_param('ssss', $joueur, $dateHeure, $libelle, $prevue);
        $success = $stmt->execute();

        $stmt->close();

        return $success;
    }

    /**
     * Met à jour une présence existante
     */
    public function update($joueur, $dateHeure, $libelle, $prevue) {
        $connection = $this->db->getConnection();

        $stmt = $connection->prepare(
            "UPDATE NPVB_Presence
             SET Prevue = ?
             WHERE Joueur = ? AND DateHeure = ? AND Libelle = ?"
        );

        $stmt->bind_param('ssss', $prevue, $joueur, $dateHeure, $libelle);
        $success = $stmt->execute();

        $stmt->close();

        return $success;
    }

    /**
     * Supprime une présence
     */
    public function delete($joueur, $dateHeure, $libelle) {
        $connection = $this->db->getConnection();

        $stmt = $connection->prepare(
            "DELETE FROM NPVB_Presence
             WHERE Joueur = ? AND DateHeure = ? AND Libelle = ?"
        );

        $stmt->bind_param('sss', $joueur, $dateHeure, $libelle);
        $success = $stmt->execute();

        $stmt->close();

        return $success;
    }
}
