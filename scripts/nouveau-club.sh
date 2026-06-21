#!/bin/bash
# ============================================================
# nouveau-club.sh — Déploiement d'un nouveau club
# Usage : sudo ./scripts/nouveau-club.sh <slug>
# Exemple : sudo ./scripts/nouveau-club.sh monclub
#
# Pré-requis :
#   - Apache, PHP 8.4-FPM, MariaDB installés
#   - Le dépôt git cloné une fois (modèle dans /var/www/npvb-web/)
#   - Accès sudo
# ============================================================

set -e

REPO_DIR="$(cd "$(dirname "$0")/.." && pwd)"  # répertoire du dépôt (parent de scripts/)
SLUG="${1:-}"

# --- Validation ---
if [ -z "$SLUG" ]; then
    echo "Usage : sudo $0 <slug>"
    echo "Exemple : sudo $0 monclub"
    exit 1
fi

if [[ ! "$SLUG" =~ ^[a-z0-9_-]+$ ]]; then
    echo "Erreur : le slug ne doit contenir que des lettres minuscules, chiffres, - ou _"
    exit 1
fi

SITE_DIR="/var/www/${SLUG}"
DB_NAME="${SLUG}_db"
DB_USER="${SLUG}_user"
PHP_POOL="/etc/php/8.4/fpm/pool.d/${SLUG}.conf"
APACHE_CONF="/etc/apache2/sites-available/${SLUG}.conf"

echo ""
echo "============================================================"
echo "  Nouveau club : $SLUG"
echo "  Répertoire   : $SITE_DIR"
echo "  Base de données : $DB_NAME"
echo "============================================================"
echo ""

# --- 1. Cloner / copier le code ---
if [ -d "$SITE_DIR" ]; then
    echo "[!] $SITE_DIR existe déjà. Abandon."
    exit 1
fi

echo "[1/6] Copie du code depuis $REPO_DIR..."
git clone "$REPO_DIR" "$SITE_DIR" 2>/dev/null || {
    # Fallback si pas un repo git propre : copie directe
    cp -r "$REPO_DIR" "$SITE_DIR"
    rm -rf "$SITE_DIR/.git"
}
echo "    OK"

# --- 2. Dossiers inscriptibles ---
echo "[2/6] Création des dossiers inscriptibles..."
mkdir -p "$SITE_DIR/sessions"
mkdir -p "$SITE_DIR/Photos"
mkdir -p "$SITE_DIR/Images/contenu"
mkdir -p "$SITE_DIR/RelevesFNP"
mkdir -p "$SITE_DIR/DEPOT"
chown -R www-data:www-data "$SITE_DIR/sessions" "$SITE_DIR/Photos" \
    "$SITE_DIR/Images/contenu" "$SITE_DIR/RelevesFNP" "$SITE_DIR/DEPOT"
chmod 775 "$SITE_DIR/sessions" "$SITE_DIR/Photos" \
    "$SITE_DIR/Images/contenu" "$SITE_DIR/RelevesFNP" "$SITE_DIR/DEPOT"
echo "    OK"

# --- 3. Base de données ---
echo "[3/6] Création de la base de données..."
read -s -p "    Mot de passe root MariaDB : " MYSQL_ROOT_PASS
echo ""

DB_PASS=$(openssl rand -base64 18 | tr -dc 'a-zA-Z0-9' | head -c 20)

mysql -u root -p"$MYSQL_ROOT_PASS" <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

echo "    Importation du schéma..."
mysql -u root -p"$MYSQL_ROOT_PASS" "$DB_NAME" < "$REPO_DIR/scripts/schema.sql"
echo "    OK  (user=$DB_USER  pass=$DB_PASS)"

# --- 4. config.php ---
echo "[4/6] Création de config.php..."
echo ""
echo "    Renseignez les informations du club :"
read -p "    Nom complet du club       : " CLUB_NOM
read -p "    Sigle (ex: MONCLUB)       : " CLUB_SIGLE
read -p "    Email du club             : " CLUB_EMAIL
read -p "    URL du site (https://...) : " CLUB_URL
read -p "    Expéditeur emails (SMTP)  : " SMTP_FROM

cat > "$SITE_DIR/config.php" <<PHP
<?php
// Configuration du club — ${CLUB_SIGLE}
// Ce fichier est exclu du dépôt git. Ne jamais le commiter.

\$config = [
    // --- Base de données ---
    'db_host'  => 'localhost',
    'db_user'  => '${DB_USER}',
    'db_pass'  => '${DB_PASS}',
    'db_name'  => '${DB_NAME}',

    // --- Identité du club ---
    'club_nom'   => '${CLUB_NOM}',
    'club_sigle' => '${CLUB_SIGLE}',
    'club_email' => '${CLUB_EMAIL}',
    'club_url'   => '${CLUB_URL}',
    'club_logo'  => 'Images/logo.svg',

    // --- Email sortant (expéditeur Brevo/SMTP) ---
    'smtp_from' => '${SMTP_FROM}',

    // --- Couleurs de la charte graphique ---
    'couleur_primaire'   => '#172446',
    'couleur_secondaire' => '#0066cc',
    'couleur_danger'     => '#dc3545',
    'couleur_succes'     => '#28a745',
    'couleur_alerte'     => '#ff956c',
    'couleur_accent'     => '#e5c10d',
    'couleur_texte'      => '#4c4c4c',
];
PHP
echo "    OK  ($SITE_DIR/config.php)"

# --- 5. Pool PHP-FPM ---
echo "[5/6] Création du pool PHP-FPM..."
cat > "$PHP_POOL" <<POOL
[${SLUG}]
user  = www-data
group = www-data

listen = /run/php/${SLUG}-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode  = 0660

pm                   = dynamic
pm.max_children      = 10
pm.start_servers     = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3

php_admin_value[session.save_path] = ${SITE_DIR}/sessions
php_flag[short_open_tag]           = On

; Compatibilité mysql_* → mysqli
php_admin_value[auto_prepend_file] = ${SITE_DIR}/app/mysql_compat.inc.php

php_admin_value[error_log] = /var/log/php8.4-fpm-${SLUG}.log
POOL

systemctl reload php8.4-fpm
echo "    OK  ($PHP_POOL)"

# --- 6. Vhost Apache ---
echo "[6/6] Création du vhost Apache..."
read -p "    Nom de domaine (ex: monclub.fr) : " DOMAIN
read -p "    Port HTTP (laisser vide = 80)   : " PORT
PORT="${PORT:-80}"

cat > "$APACHE_CONF" <<VHOST
<VirtualHost *:${PORT}>
    ServerName ${DOMAIN}
    DocumentRoot ${SITE_DIR}

    <Directory ${SITE_DIR}>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php/${SLUG}-fpm.sock|fcgi://localhost"
    </FilesMatch>

    ErrorLog  \${APACHE_LOG_DIR}/${SLUG}-error.log
    CustomLog \${APACHE_LOG_DIR}/${SLUG}-access.log combined
</VirtualHost>
VHOST

a2ensite "${SLUG}.conf" >/dev/null 2>&1
systemctl reload apache2
echo "    OK  ($APACHE_CONF)"

# --- Résumé ---
echo ""
echo "============================================================"
echo "  Déploiement terminé !"
echo ""
echo "  Site     : http://${DOMAIN}:${PORT}"
echo "  Répert.  : $SITE_DIR"
echo "  DB       : $DB_NAME  (user: $DB_USER)"
echo ""
echo "  Prochaines étapes :"
echo "  1. Remplacer Images/logo.svg par le logo du club"
echo "  2. Ajuster les couleurs dans $SITE_DIR/config.php"
echo "  3. Créer le premier compte admin via phpMyAdmin ou :"
echo "     INSERT INTO NPVB_Joueurs (Pseudonyme, Password, Etat, Nom, Prenom, NumeroLicence)"
echo "       VALUES ('admin', OLD_PASSWORD('motdepasse'), 'V', 'Admin', 'Club', '');"
echo "     INSERT INTO NPVB_JoueurRoles (Pseudonyme, Role) VALUES ('admin', 'admin');"
echo "  4. Configurer la clé API Brevo dans brevo_api.inc.php"
echo "============================================================"
echo ""
