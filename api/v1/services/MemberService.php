<?php
/**
 * Classe MemberService - Logique métier des membres
 */
class MemberService {

    private $memberRepository;

    public function __construct() {
        $this->memberRepository = new MemberRepository();
    }

    /**
     * Récupère tous les membres
     * Note: Retourne un tableau de tableaux pour compatibilité avec l'ancien format [[{}]]
     */
    public function getAllMembers() {
        $members = $this->memberRepository->findAll();

        // Format de compatibilité: chaque membre dans son propre tableau
        $formattedMembers = [];
        foreach ($members as $member) {
            $formattedMembers[] = [$member];
        }

        return [
            'success' => true,
            'data' => $formattedMembers
        ];
    }

    /**
     * Récupère un membre par son pseudonyme
     */
    public function getMemberByUsername($username) {
        $member = $this->memberRepository->findByUsername($username);

        if (!$member) {
            return [
                'success' => false,
                'message' => 'Member not found'
            ];
        }

        return [
            'success' => true,
            'data' => $member
        ];
    }

    /**
     * Récupère toutes les appartenances aux équipes
     */
    public function getAllMemberships() {
        $memberships = $this->memberRepository->findAllMemberships();

        return [
            'success' => true,
            'data' => $memberships
        ];
    }
}
