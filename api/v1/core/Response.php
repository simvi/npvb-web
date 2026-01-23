<?php
/**
 * Classe Response - Gestion des réponses JSON normalisées
 */
class Response {

    /**
     * Envoie une réponse JSON de succès
     */
    public static function success($data = null, $message = null, $httpCode = 200) {
        self::send([
            'success' => true,
            'data' => $data,
            'message' => $message
        ], $httpCode);
    }

    /**
     * Envoie une réponse JSON d'erreur
     */
    public static function error($message, $errorCode = ERROR_INTERNAL, $httpCode = 500, $details = null) {
        self::send([
            'success' => false,
            'error' => [
                'code' => $errorCode,
                'message' => $message,
                'details' => $details
            ]
        ], $httpCode);
    }

    /**
     * Envoie une réponse de validation échouée
     */
    public static function validationError($errors, $message = 'Validation failed') {
        self::error($message, ERROR_INVALID_INPUT, 400, $errors);
    }

    /**
     * Envoie une réponse non autorisée
     */
    public static function unauthorized($message = 'Unauthorized') {
        self::error($message, ERROR_INVALID_TOKEN, 401);
    }

    /**
     * Envoie une réponse non trouvée
     */
    public static function notFound($message = 'Resource not found') {
        self::error($message, ERROR_NOT_FOUND, 404);
    }

    /**
     * Envoie une réponse de méthode non autorisée
     */
    public static function methodNotAllowed($allowedMethods = []) {
        header('Allow: ' . implode(', ', $allowedMethods));
        self::error('Method not allowed', ERROR_INVALID_INPUT, 405);
    }

    /**
     * Fonction interne pour envoyer la réponse
     */
    private static function send($data, $httpCode) {
        // Définir le code HTTP
        http_response_code($httpCode);

        // Définir les headers
        header('Content-Type: ' . RESPONSE_CONTENT_TYPE . '; charset=' . RESPONSE_ENCODING);
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');

        // CORS headers (à adapter selon vos besoins)
        header('Access-Control-Allow-Origin: *'); // TODO: Restreindre en production
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        // Encoder et envoyer la réponse JSON
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Log si activé
        if (ENABLE_LOGGING) {
            self::log($data, $httpCode);
        }

        exit;
    }

    /**
     * Log la réponse (optionnel)
     */
    private static function log($data, $httpCode) {
        $logEntry = date('Y-m-d H:i:s') . " - HTTP $httpCode - " . json_encode($data) . "\n";
        @file_put_contents(LOG_FILE, $logEntry, FILE_APPEND);
    }
}
