<?php
/**
 * Classe AuthService - Logique métier de l'authentification
 */
class AuthService {

    private $userRepository;

    public function __construct() {
        $this->userRepository = new UserRepository();
    }

    /**
     * Authentifie un utilisateur et retourne un token
     */
    public function login($username, $password) {
        // Valider les inputs
        $validator = new Validator(['username' => $username, 'password' => $password]);
        $validator->required('username')
                  ->required('password')
                  ->username('username')
                  ->minLength('password', 1);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->getErrors()
            ];
        }

        // Vérifier les credentials
        $user = $this->userRepository->findByCredentials($username, $password);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid credentials'
            ];
        }

        // Générer un token JWT
        $token = Auth::generateToken($user['Pseudonyme']);

        return [
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => [
                    'Pseudonyme' => $user['Pseudonyme'],
                    'isAdmin' => $user['DieuToutPuissant'] === 'o'
                ]
            ]
        ];
    }

    /**
     * Vérifie si un utilisateur est authentifié
     */
    public function verifyToken($token) {
        $payload = Auth::verifyToken($token);

        if (!$payload) {
            return [
                'success' => false,
                'message' => 'Invalid or expired token'
            ];
        }

        return [
            'success' => true,
            'data' => [
                'username' => $payload['sub'],
                'exp' => $payload['exp']
            ]
        ];
    }
}
