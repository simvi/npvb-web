<?php
/**
 * Classe ResourcesController - Contrôleur des ressources externes
 * (URLs des règles, calendrier, résultats)
 */
class ResourcesController {

    /**
     * GET /api/v1/resources/rules
     * Retourne l'URL des règles FIVB
     */
    public function getRules() {
        // Vérifier la méthode HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::methodNotAllowed(['GET']);
        }

        // Authentifier
        Auth::authenticate();

        Response::success([
            'url' => 'https://www.fivb.com/wp-content/uploads/2025/06/FIVB-Volleyball_Rules2025_2028-FR-v04.pdf'
        ]);
    }

    /**
     * GET /api/v1/resources/competlib
     * Retourne l'URL du calendrier des compétitions
     */
    public function getCompetlib() {
        // Vérifier la méthode HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::methodNotAllowed(['GET']);
        }

        // Authentifier
        Auth::authenticate();

        Response::success([
            'url' => 'https://www.ffvbbeach.org/ffvbapp/resu/vbspo_calendrier_export.php'
        ]);
    }

    /**
     * GET /api/v1/resources/ufolep
     * Retourne l'URL des résultats UFOLEP
     */
    public function getUfolep() {
        // Vérifier la méthode HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::methodNotAllowed(['GET']);
        }

        // Authentifier
        Auth::authenticate();

        Response::success([
            'url' => 'https://www.ufolep44.com/resultats/resultats-volley-ball'
        ]);
    }
}
