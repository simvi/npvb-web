<?php
/**
 * Configuration de la base de données
 *
 * IMPORTANT : Pour la production, déplacer ces informations dans un fichier
 * en dehors du webroot (ex: /home/user/config/db_credentials.php)
 */

// Constantes de connexion à la base de données
define('DB_HOST', 'ftpperso.free.fr');
define('DB_NAME', 'nantespvb');
define('DB_USER', 'nantespvb');
define('DB_PASS', 'wozd7pdo'); // TODO: Sécuriser ce mot de passe

// Configuration de l'encodage
define('DB_CHARSET', 'utf8');

// Configuration pour le comportement de MySQLi
define('DB_REPORT_MODE', MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
