<?php
/**
 * Classe Auth - Gestion de l'authentification et des tokens JWT
 * Implémentation JWT légère sans bibliothèque externe
 */
class Auth {

    /**
     * Génère un token JWT pour un utilisateur
     */
    public static function generateToken($username) {
        // Header du JWT
        $header = [
            'alg' => JWT_ALGORITHM,
            'typ' => 'JWT'
        ];

        // Payload du JWT
        $payload = [
            'sub' => $username,              // Subject (username)
            'iat' => time(),                 // Issued at
            'exp' => time() + JWT_EXPIRATION // Expiration
        ];

        // Encode header et payload en base64url
        $headerEncoded = self::base64urlEncode(json_encode($header));
        $payloadEncoded = self::base64urlEncode(json_encode($payload));

        // Crée la signature
        $signature = self::sign($headerEncoded . '.' . $payloadEncoded);

        // Retourne le token complet
        return $headerEncoded . '.' . $payloadEncoded . '.' . $signature;
    }

    /**
     * Vérifie et décode un token JWT
     */
    public static function verifyToken($token) {
        if (empty($token)) {
            return false;
        }

        // Sépare les parties du token
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return false;
        }

        list($headerEncoded, $payloadEncoded, $signature) = $parts;

        // Vérifie la signature
        $expectedSignature = self::sign($headerEncoded . '.' . $payloadEncoded);

        if (!hash_equals($signature, $expectedSignature)) {
            return false; // Signature invalide
        }

        // Décode le payload
        $payload = json_decode(self::base64urlDecode($payloadEncoded), true);

        if (!$payload) {
            return false;
        }

        // Vérifie l'expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false; // Token expiré
        }

        // Retourne le payload si valide
        return $payload;
    }

    /**
     * Récupère le token depuis les headers HTTP
     */
    public static function getTokenFromHeaders() {
        // Vérifier le header Authorization
        $headers = self::getAuthorizationHeader();

        if (!empty($headers)) {
            // Format attendu: "Bearer {token}"
            if (preg_match('/Bearer\s+(.*)$/i', $headers, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Récupère le header Authorization
     */
    private static function getAuthorizationHeader() {
        $headers = null;

        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER['Authorization']);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(
                array_map('ucwords', array_keys($requestHeaders)),
                array_values($requestHeaders)
            );
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }

        return $headers;
    }

    /**
     * Vérifie l'authentification de la requête courante
     */
    public static function authenticate() {
        $token = self::getTokenFromHeaders();

        if (!$token) {
            Response::error('Token missing', ERROR_MISSING_TOKEN, 401);
        }

        $payload = self::verifyToken($token);

        if (!$payload) {
            Response::error('Invalid or expired token', ERROR_INVALID_TOKEN, 401);
        }

        return $payload;
    }

    /**
     * Récupère l'utilisateur authentifié
     */
    public static function getAuthenticatedUser() {
        $payload = self::authenticate();
        return $payload['sub'] ?? null;
    }

    /**
     * Crée une signature HMAC
     */
    private static function sign($data) {
        $signature = hash_hmac('sha256', $data, JWT_SECRET, true);
        return self::base64urlEncode($signature);
    }

    /**
     * Encode en base64url (compatible JWT)
     */
    private static function base64urlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Décode depuis base64url
     */
    private static function base64urlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Hash un mot de passe avec OLD_PASSWORD pour compatibilité avec l'existant
     * NOTE: Cette fonction est OBSOLÈTE et INSÉCURE, à remplacer dès que possible
     */
    public static function hashPasswordOld($password, $connection) {
        // Utilise OLD_PASSWORD de MySQL pour compatibilité avec l'existant
        $stmt = $connection->prepare("SELECT OLD_PASSWORD(?) as hash");
        $stmt->bind_param('s', $password);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row['hash'] ?? null;
    }

    /**
     * Hash un mot de passe avec une méthode moderne (pour future migration)
     * NOTE: À utiliser lors de la migration des mots de passe
     */
    public static function hashPasswordModern($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Vérifie un mot de passe moderne
     */
    public static function verifyPasswordModern($password, $hash) {
        return password_verify($password, $hash);
    }
}
