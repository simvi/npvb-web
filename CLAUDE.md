# Règles Claude — projet nantespvb-dev

## Git : ne jamais travailler directement sur `main`

Toujours travailler sur `develop` ou sur une branche `feature/...` (ou `fix/...`), jamais de commit direct sur `main`. Avant tout `git commit`, vérifier la branche courante (`git status`/`git branch`) et créer/checkout une branche appropriée si on se trouve sur `main`.

Raison : `develop` et `main` ont divergé sur `npvb-ios` (nécessité d'un merge de rattrapage) suite à des commits faits directement sur `main`.

## Déploiement en production

**Toujours demander une confirmation explicite avant toute mise en production.**

Cela inclut :
- rsync / copie de fichiers vers le serveur OVH (`nantespvb.fr`)
- `git push` vers un remote de production
- Toute commande SSH modifiant l'environnement prod (`/var/www/nantespvb/`)
- Upload FTP vers `ftpperso.free.fr`

Ne jamais exécuter ces actions sans avoir reçu un "oui" explicite de l'utilisateur dans le même tour de conversation.
