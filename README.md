# nantespvb — Site Nantes Plaisir du Volley Ball

Site de gestion d'un club de volley : membres, équipes, événements, présences, messagerie, API mobile.

## Migration en cours

- **Origine** : hébergé sur Free (PHP 4.4.3 + MySQL)
- **Cible** : PHP 8.4 + MariaDB
- Modernisation du code legacy (`mysql_*` → `mysqli`, short tags, etc.)

## Environnements

| Env  | Emplacement                | Accès                          |
|------|----------------------------|--------------------------------|
| dev  | `/var/www/nantespvb-dev`   | LAN `:8080` + Tailscale privé  |
| prod | `/var/www/nantespvb-prod`  | Tailscale Funnel (public)      |

## Configuration

1. Copier `_passwrds.inc.php.example` → `PASSWD/_passwrds.inc.php`
2. Renseigner les identifiants de base de données
3. Importer le schéma : `mariadb -u user -p nantespvb_dev < nantespvb.sql`

## Branches

- `main` — production stable
- `develop` — développement en cours
