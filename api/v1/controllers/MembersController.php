<?php
/**
 * Classe MembersController - Contrôleur des membres
 */
class MembersController {

    private $memberService;

    public function __construct() {
        $this->memberService = new MemberService();
    }

    /**
     * GET /api/v1/members
     * Récupère tous les membres actifs
     */
    public function getAll() {
        // Vérifier la méthode HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::methodNotAllowed(['GET']);
        }

        // Authentifier
        Auth::authenticate();

        // Appeler le service
        $result = $this->memberService->getAllMembers();

        if (!$result['success']) {
            Response::error($result['message'] ?? 'Failed to fetch members', ERROR_INTERNAL);
        }

        Response::success($result['data']);
    }

    /**
     * GET /api/v1/members/{username}
     * Récupère un membre par son pseudonyme
     */
    public function getByUsername($username) {
        // Vérifier la méthode HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::methodNotAllowed(['GET']);
        }

        // Authentifier
        Auth::authenticate();

        // Appeler le service
        $result = $this->memberService->getMemberByUsername($username);

        if (!$result['success']) {
            Response::notFound($result['message'] ?? 'Member not found');
        }

        Response::success($result['data']);
    }

    /**
     * GET /api/v1/memberships
     * Récupère toutes les appartenances aux équipes
     */
    public function getMemberships() {
        // Vérifier la méthode HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::methodNotAllowed(['GET']);
        }

        // Authentifier
        Auth::authenticate();

        // Appeler le service
        $result = $this->memberService->getAllMemberships();

        if (!$result['success']) {
            Response::error($result['message'] ?? 'Failed to fetch memberships', ERROR_INTERNAL);
        }

        Response::success($result['data']);
    }
}
