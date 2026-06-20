<?php
// Copier ce fichier en config.php et remplir les valeurs pour chaque club.
// config.php est exclu du dépôt git (.gitignore).

$config = [
    // --- Base de données ---
    'db_host'  => 'localhost',
    'db_user'  => 'monclub_user',
    'db_pass'  => 'mot_de_passe_db',
    'db_name'  => 'monclub_db',

    // --- Identité du club ---
    'club_nom'   => 'Nom complet du club',
    'club_sigle' => 'SIGLE',
    'club_email' => 'contact@monclub.fr',
    'club_url'   => 'https://monclub.fr',
    'club_logo'  => 'Images/logo.svg',

    // --- Email sortant (expéditeur Brevo/SMTP) ---
    'smtp_from' => 'noreply@monclub.fr',

    // --- Couleurs de la charte graphique ---
    'couleur_primaire'   => '#172446',
    'couleur_secondaire' => '#0066cc',
    'couleur_danger'     => '#dc3545',
    'couleur_succes'     => '#28a745',
    'couleur_alerte'     => '#ff956c',
    'couleur_accent'     => '#e5c10d',
    'couleur_texte'      => '#4c4c4c',
];
