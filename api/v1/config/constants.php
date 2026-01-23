<?php
/**
 * Constantes globales de l'application
 */

// Configuration JWT
define('JWT_SECRET', 'npvb_secret_key_2025_change_me_in_production'); // TODO: Générer une clé aléatoire forte
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRATION', 86400); // 24 heures en secondes

// Configuration API
define('API_VERSION', 'v1');
define('API_BASE_PATH', '/api/v1');

// Configuration des réponses
define('RESPONSE_ENCODING', 'UTF-8');
define('RESPONSE_CONTENT_TYPE', 'application/json');

// Codes d'erreur personnalisés
define('ERROR_INVALID_CREDENTIALS', 'INVALID_CREDENTIALS');
define('ERROR_INVALID_TOKEN', 'INVALID_TOKEN');
define('ERROR_EXPIRED_TOKEN', 'EXPIRED_TOKEN');
define('ERROR_MISSING_TOKEN', 'MISSING_TOKEN');
define('ERROR_INVALID_INPUT', 'INVALID_INPUT');
define('ERROR_NOT_FOUND', 'NOT_FOUND');
define('ERROR_DATABASE', 'DATABASE_ERROR');
define('ERROR_CAPACITY_REACHED', 'CAPACITY_REACHED');
define('ERROR_ALREADY_REGISTERED', 'ALREADY_REGISTERED');
define('ERROR_NOT_REGISTERED', 'NOT_REGISTERED');
define('ERROR_INTERNAL', 'INTERNAL_ERROR');

// Configuration des logs (optionnel)
define('ENABLE_LOGGING', false); // Activer uniquement pour debug
define('LOG_FILE', __DIR__ . '/../../logs/api.log');
