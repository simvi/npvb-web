<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}

// ============================================================================
// Classe d'envoi d'emails via Gmail SMTP
// Compatible PHP 4.3+
// ============================================================================

// Configuration Gmail SMTP
$GMAIL_SMTP_USER = "simon.viaud@gmail.com";      // Votre email Gmail
$GMAIL_SMTP_PASS = "vevwkstqluomtlkh";     // Mot de passe d'application (16 caracteres)
$GMAIL_SMTP_HOST = "smtp.gmail.com";
$GMAIL_SMTP_PORT = 465;                          // Port SSL direct (pas STARTTLS)

// Fonction principale d'envoi d'email via Gmail
function EnvoyerEmailGmail($destinataire, $sujet, $contenu, $expediteur = "", $nomExpediteur = "NPVB") {
    GLOBAL $GMAIL_SMTP_USER, $GMAIL_SMTP_PASS, $GMAIL_SMTP_HOST, $GMAIL_SMTP_PORT;

    // Si pas d'expediteur specifie, utiliser le compte Gmail
    if (!$expediteur) {
        $expediteur = $GMAIL_SMTP_USER;
    }

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
