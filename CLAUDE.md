# Règles Claude — projet nantespvb-dev

## Déploiement en production

**Toujours demander une confirmation explicite avant toute mise en production.**

Cela inclut :
- rsync / copie de fichiers vers le serveur OVH (`nantespvb.fr`)
- `git push` vers un remote de production
- Toute commande SSH modifiant l'environnement prod (`/var/www/nantespvb/`)
- Upload FTP vers `ftpperso.free.fr`

Ne jamais exécuter ces actions sans avoir reçu un "oui" explicite de l'utilisateur dans le même tour de conversation.
