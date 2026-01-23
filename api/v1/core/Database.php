<?php
/**
 * Classe Database - Singleton pour la connexion à la base de données
 * Utilise MySQLi avec prepared statements pour la sécurité
 */
class Database {

    private static $instance = null;
    private $connection = null;

    /**
     * Constructeur privé pour empêcher l'instanciation directe
     */
    private function __construct() {
        try {
            // Configuration du mode de rapport d'erreur MySQLi
            mysqli_report(DB_REPORT_MODE);

            // Connexion à la base de données
            $this->connection = new mysqli(
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME
            );

            // Vérification de la connexion
            if ($this->connection->connect_error) {
                throw new Exception('Connection failed: ' . $this->connection->connect_error);
            }

            // Configuration de l'encodage
            $this->connection->set_charset(DB_CHARSET);

        } catch (Exception $e) {
            error_log('Database connection error: ' . $e->getMessage());
            throw new Exception('Database connection failed');
        }
    }

    /**
     * Empêche le clonage de l'instance
     */
    private function __clone() {}

    /**
     * Récupère l'instance unique de Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Récupère la connexion MySQLi
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Prépare une requête SQL
     */
    public function prepare($query) {
        return $this->connection->prepare($query);
    }

    /**
     * Récupère le dernier ID inséré
     */
    public function getLastInsertId() {
        return $this->connection->insert_id;
    }

    /**
     * Échappe une chaîne pour la sécurité (à utiliser uniquement si pas de prepared statements)
     */
    public function escape($value) {
        return $this->connection->real_escape_string($value);
    }

    /**
     * Ferme la connexion (appelé automatiquement à la fin du script)
     */
    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }

    /**
     * Destructeur pour fermer la connexion
     */
    public function __destruct() {
        $this->close();
    }
}
