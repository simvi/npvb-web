<?php
/**
 * Classe AuthController - Contrôleur de l'authentification
 */
class AuthController {

    private $authService;

    public function __construct() {
        $this->authService = new AuthService();
    }

    /**
     * POST /api/v1/auth/login
     * Login et obtention d'un token JWT
     */
    public function login() {
        // Vérifier la méthode HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::methodNotAllowed(['POST']);
        }

        // Récupérer les données POST
        $input = $this->getJsonInput();

        if (!$input) {
            Response::error('Invalid JSON input', ERROR_INVALID_INPUT, 400);
        }

        $username = isset($input['username']) ? trim($input['username']) : null;
        $password = isset($input['password']) ? $input['password'] : null;

        // Appeler le service
        $result = $this->authService->login($username, $password);

        if (!$result['success']) {
            if (isset($result['errors'])) {
                Response::validationError($result['errors']);
            } else {
                Response::error(
                    $result['message'] ?? 'Authentication failed',
                    ERROR_INVALID_CREDENTIALS,
                    401
                );
            }
        }

        // Retourner le token
        Response::success($result['data'], 'Login successful');
    }

    /**
     * GET /api/v1/auth/verify
     * Vérifie la validité d'un token
     */
    public function verify() {
        // Vérifier la méthode HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::methodNotAllowed(['GET']);
        }

        // Authentifier et récupérer l'utilisateur
        $payload = Auth::authenticate();

        Response::success([
            'username' => $payload['sub'],
            'exp' => $payload['exp'],
            'valid' => true
        ], 'Token is valid');
    }

    /**
     * Récupère et décode les données JSON depuis le body
     */
    private function getJsonInput() {
        $input = file_get_contents('php://input');
        return json_decode($input, true);
    }
}
