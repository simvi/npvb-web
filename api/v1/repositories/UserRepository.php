<?php
/**
 * Classe UserRepository - Accès aux données des utilisateurs
 */
class UserRepository {

    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Trouve un utilisateur par son pseudonyme et mot de passe
     * Utilise OLD_PASSWORD pour compatibilité avec l'existant
     */
    public function findByCredentials($username, $password) {
        $connection = $this->db->getConnection();

        // Récupérer le hash du mot de passe avec OLD_PASSWORD
        $passwordHash = Auth::hashPasswordOld($password, $connection);

        if (!$passwordHash) {
            return null;
        }

        // Préparer la requête de recherche
        $stmt = $connection->prepare(
            "SELECT Pseudonyme, DieuToutPuissant
             FROM NPVB_Joueurs
             WHERE etat = 'V'
             AND Pseudonyme = ?
             AND Password = ?"
        );

        $stmt->bind_param('ss', $username, $passwordHash);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        $stmt->close();

        return $user;
    }

    /**
     * Trouve un utilisateur par son pseudonyme
     */
    public function findByUsername($username) {
        $connection = $this->db->getConnection();

        $stmt = $connection->prepare(
            "SELECT Pseudonyme, DieuToutPuissant, Nom, Prenom, Email
             FROM NPVB_Joueurs
             WHERE etat = 'V'
             AND Pseudonyme = ?"
        );

        $stmt->bind_param('s', $username);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        $stmt->close();

        return $user;
    }

    /**
     * Vérifie si un utilisateur est administrateur
     */
    public function isAdmin($username) {
        $user = $this->findByUsername($username);
        return $user && isset($user['DieuToutPuissant']) && $user['DieuToutPuissant'] === 'o';
    }
}
