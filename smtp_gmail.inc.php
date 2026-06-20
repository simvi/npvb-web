<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}

// ============================================================================
// Classe d'envoi d'emails via Gmail SMTP
// Compatible PHP 4.3+
// ============================================================================

// Fonction principale d'envoi d'email via Gmail SMTP
function EnvoyerEmailGmail($destinataire, $sujet, $contenu, $expediteur = "", $nomExpediteur = "") {
    GLOBAL $config;

    if (!$expediteur)    $expediteur    = $config['smtp_from'];
    if (!$nomExpediteur) $nomExpediteur = $config['club_sigle'];

    $GMAIL_SMTP_USER = $config['smtp_user'];
    $GMAIL_SMTP_PASS = $config['smtp_pass'];
    $GMAIL_SMTP_HOST = $config['smtp_host'];
    $GMAIL_SMTP_PORT = $config['smtp_port'];

    // Connexion SMTP
    $smtp = smtp_connect($GMAIL_SMTP_HOST, $GMAIL_SMTP_PORT);
    if (!$smtp) {
        return false;
    }

    // Lecture de la banniere
    smtp_read($smtp);

    // EHLO (connexion deja securisee via SSL)
    smtp_write($smtp, "EHLO " . $_SERVER['SERVER_NAME']);
    smtp_read($smtp);

    // Authentification
    smtp_write($smtp, "AUTH LOGIN");
    smtp_read($smtp);

    smtp_write($smtp, base64_encode($GMAIL_SMTP_USER));
    smtp_read($smtp);

    smtp_write($smtp, base64_encode($GMAIL_SMTP_PASS));
    $response = smtp_read($smtp);

    if (strpos($response, "235") === false) {
        fclose($smtp);
        return false;
    }

    // Envoi du message
    smtp_write($smtp, "MAIL FROM:<$expediteur>");
    smtp_read($smtp);

    smtp_write($smtp, "RCPT TO:<$destinataire>");
    smtp_read($smtp);

    smtp_write($smtp, "DATA");
    smtp_read($smtp);

    // Construction du message
    $message = "From: $nomExpediteur <$expediteur>\r\n";
    $message .= "To: <$destinataire>\r\n";
    $message .= "Subject: $sujet\r\n";
    $message .= "Content-Type: text/plain; charset=iso-8859-1\r\n";
    $message .= "Content-Transfer-Encoding: 8bit\r\n";
    $message .= "\r\n";
    $message .= $contenu;
    $message .= "\r\n.\r\n";

    smtp_write($smtp, $message);
    smtp_read($smtp);

    // Fermeture
    smtp_write($smtp, "QUIT");
    smtp_read($smtp);

    fclose($smtp);

    return true;
}

// Connexion au serveur SMTP
function smtp_connect($host, $port) {
    $errno = 0;
    $errstr = "";

    // Connexion SSL directe (port 465)
    $socket = @fsockopen("ssl://" . $host, $port, $errno, $errstr, 30);

    if (!$socket) {
        return false;
    }

    return $socket;
}

// Ecriture sur le socket SMTP
function smtp_write($socket, $data) {
    if (substr($data, -2) != "\r\n") {
        $data .= "\r\n";
    }
    fputs($socket, $data);
}

// Lecture depuis le socket SMTP
function smtp_read($socket) {
    $response = "";
    while ($line = fgets($socket, 515)) {
        $response .= $line;
        if (substr($line, 3, 1) == " ") {
            break;
        }
    }
    return $response;
}

?>
