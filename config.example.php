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

    // --- API mobile (token d'authentification — changer pour chaque club) ---
    'mobile_token_secret' => 'changez_moi_secret_unique_par_club',

    // --- Notifications push (Firebase Cloud Messaging HTTP v1) ---
    // Vide = push désactivé (no-op). Pour activer : créer un projet Firebase,
    // télécharger le JSON de compte de service (hors git) et renseigner :
    'fcm_project_id'      => '',
    'fcm_service_account' => '',  // ex: /home/user/.nantespvb/fcm-service-account.json

    // --- SMTP (Gmail : créer un "mot de passe d'application" dans les paramètres Google) ---
    'smtp_from' => 'noreply@monclub.fr',
    'smtp_user' => 'votre.email@gmail.com',
    'smtp_pass' => 'xxxx xxxx xxxx xxxx',
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 465,

    // --- Couleurs de la charte graphique ---
    'couleur_primaire'   => '#172446',
    'couleur_secondaire' => '#0066cc',
    'couleur_danger'     => '#dc3545',
    'couleur_succes'     => '#28a745',
    'couleur_alerte'     => '#ff956c',
    'couleur_accent'     => '#e5c10d',
    'couleur_texte'      => '#4c4c4c',
];
