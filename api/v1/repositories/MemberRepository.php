<?php
/**
 * Classe MemberRepository - Accès aux données des membres
 */
class MemberRepository {

    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Récupère tous les membres actifs
     */
    public function findAll() {
        $connection = $this->db->getConnection();

        $stmt = $connection->prepare(
            "SELECT Pseudonyme, DieuToutPuissant, Nom, Prenom, Sexe, DateNaissance,
                    Profession, Adresse, CPVille, Telephones, Email, Accord, NumeroLicence
             FROM NPVB_Joueurs
             WHERE etat = 'V'
             ORDER BY Nom, Prenom"
        );

        $stmt->execute();
        $result = $stmt->get_result();

        $members = [];
        while ($row = $result->fetch_assoc()) {
            // Nettoyer les données (trim whitespace, remove control chars)
            $members[] = $this->cleanMemberData($row);
        }

        $stmt->close();

        return $members;
    }

    /**
     * Récupère un membre par son pseudonyme
     */
    public function findByUsername($username) {
        $connection = $this->db->getConnection();

        $stmt = $connection->prepare(
            "SELECT Pseudonyme, DieuToutPuissant, Nom, Prenom, Sexe, DateNaissance,
                    Profession, Adresse, CPVille, Telephones, Email, Accord, NumeroLicence
             FROM NPVB_Joueurs
             WHERE etat = 'V' AND Pseudonyme = ?"
        );

        $stmt->bind_param('s', $username);
        $stmt->execute();

        $result = $stmt->get_result();
        $member = $result->fetch_assoc();

        $stmt->close();

        return $member ? $this->cleanMemberData($member) : null;
    }

    /**
     * Récupère les appartenances aux équipes
     */
    public function findAllMemberships() {
        $connection = $this->db->getConnection();

        $stmt = $connection->prepare(
            "SELECT Joueur, Equipe
             FROM NPVB_Appartenance
             ORDER BY Equipe, Joueur"
        );

        $stmt->execute();
        $result = $stmt->get_result();

        $memberships = [];
        while ($row = $result->fetch_assoc()) {
            $memberships[] = $row;
        }

        $stmt->close();

        return $memberships;
    }

    /**
     * Nettoie les données d'un membre
     */
    private function cleanMemberData($member) {
        foreach ($member as $key => $value) {
            if (is_string($value)) {
                // Supprimer les caractères de contrôle (TAB, etc.)
                $value = preg_replace('/[\x00-\x1F\x7F]/', '', $value);
                // Trim les espaces
                $member[$key] = trim($value);
            }
        }
        return $member;
    }
}
