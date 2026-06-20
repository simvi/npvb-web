<?
if (!$PasseParIndex) { header('Location: index.php?Page=Erreur404'); return;}

include_once('smtp_gmail.inc.php');

function EnvoyerEmailBrevo($destinataire, $sujet, $contenu, $expediteur = "", $nomExpediteur = "") {
    return EnvoyerEmailGmail($destinataire, $sujet, $contenu, $expediteur, $nomExpediteur);
}
?>
