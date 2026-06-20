<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}

// ============================================================================
// Envoi d'emails via l'API Brevo (ex-Sendinblue)
//
// STATUT : non utilisé actuellement — Free.fr bloque les connexions SMTP
//          sortantes, et l'API HTTP Brevo n'était pas accessible.
//          Sur debianair (dev) : smtp_gmail.inc.php + EnvoyerEmailGmail() est utilisé.
//
// ACTIVATION SUR OVH :
//   1. Créer un compte Brevo (https://www.brevo.com) et générer une clé API
//   2. Ajouter dans config.php : 'brevo_api_key' => 'xkeysib-...'
//   3. Dans motdepasseoublie.inc.php :
//      - remplacer include('smtp_gmail.inc.php') par include('brevo_api.inc.php')
//      - remplacer EnvoyerEmailGmail() par EnvoyerEmailBrevo()
// ============================================================================

/*
function EnvoyerEmailBrevo($destinataire, $sujet, $contenu, $expediteur = "", $nomExpediteur = "") {
    global $config;

    if (!$expediteur)    $expediteur    = $config['smtp_from'];
    if (!$nomExpediteur) $nomExpediteur = $config['club_sigle'];

    $payload = json_encode([
        'sender'     => ['name' => $nomExpediteur, 'email' => $expediteur],
        'to'         => [['email' => $destinataire]],
        'subject'    => $sujet,
        'textContent'=> $contenu,
    ]);

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'api-key: ' . $config['brevo_api_key'],
        ],
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($httpCode >= 200 && $httpCode < 300);
}
*/
?>
