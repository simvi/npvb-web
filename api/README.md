# API REST NPVB v1

API REST moderne pour l'application NPVB (Nantes Plage Volley-Ball).

## Architecture

```
/api/v1/
├── config/           # Configuration (DB, constants)
├── core/             # Classes de base (Database, Response, Auth, Validator)
├── controllers/      # Contrôleurs (gestion requêtes HTTP)
├── services/         # Services (logique métier)
├── repositories/     # Repositories (accès données)
└── index.php         # Front Controller (routing)
```

## Caractéristiques

- **Architecture REST** avec séparation des responsabilités (MVC)
- **Authentification JWT** légère (implémentation native PHP)
- **Sécurité renforcée** : prepared statements MySQLi, validation inputs
- **Réponses JSON normalisées** avec codes HTTP appropriés
- **Compatible hébergement Free** : PHP natif, MySQLi, pas de dépendances
- **Versioning** : API v1 prête pour évolutions futures

## Installation

### 1. Configuration de la base de données

Éditer `/api/v1/config/database.php` :

```php
define('DB_HOST', 'votre_host');
define('DB_NAME', 'votre_database');
define('DB_USER', 'votre_user');
define('DB_PASS', 'votre_password');
```

**IMPORTANT** : En production, déplacer ces credentials hors du webroot.

### 2. Configuration JWT

Éditer `/api/v1/config/constants.php` :

```php
define('JWT_SECRET', 'GENERER_UNE_CLE_ALEATOIRE_FORTE');
define('JWT_EXPIRATION', 86400); // 24 heures
```

Pour générer une clé sécurisée :
```bash
php -r "echo bin2hex(random_bytes(32));"
```

### 3. Permissions fichiers

```bash
chmod 755 api/v1
chmod 644 api/v1/index.php
chmod 644 api/v1/config/*.php
```

### 4. Test de l'installation

Accéder à : `https://votre-domaine.free.fr/api/v1/index.php`

Vous devriez voir :
```json
{
  "success": true,
  "data": {
    "name": "NPVB API",
    "version": "v1",
    "status": "online"
  }
}
```

## Utilisation

### Format des URLs

**Sans mod_rewrite** (compatible tous hébergements) :
```
/api/v1/index.php?endpoint=auth/login
/api/v1/index.php?endpoint=members
/api/v1/index.php?endpoint=events
```

**Avec mod_rewrite** (si .htaccess activé) :
```
/api/v1/auth/login
/api/v1/members
/api/v1/events
```

### Authentification

#### 1. Login

**POST** `/api/v1/index.php?endpoint=auth/login`

**Body** :
```json
{
  "username": "pseudo",
  "password": "motdepasse"
}
```

**Réponse** :
```json
{
  "success": true,
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "user": {
      "Pseudonyme": "pseudo",
      "isAdmin": false
    }
  },
  "message": "Login successful"
}
```

#### 2. Utiliser le token

Pour toutes les requêtes suivantes, ajouter le header :
```
Authorization: Bearer {votre_token}
```

### Endpoints disponibles

#### Membres

| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `/members` | GET | Liste tous les membres actifs |
| `/members/{username}` | GET | Détails d'un membre |
| `/memberships` | GET | Appartenances aux équipes |

**Exemple** :
```bash
curl -H "Authorization: Bearer {token}" \
  https://votre-domaine.free.fr/api/v1/index.php?endpoint=members
```

**Réponse** :
```json
{
  "success": true,
  "data": [
    [{
      "Pseudonyme": "JohnDoe",
      "Nom": "Doe",
      "Prenom": "John",
      "Email": "john@example.com",
      ...
    }],
    ...
  ]
}
```

#### Événements

| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `/events` | GET | Liste tous les événements |
| `/events/{dateHeure}/{libelle}` | GET | Détails d'un événement |
| `/events/{dateHeure}/presences` | GET | Présences pour un événement |

**Exemple** :
```bash
curl -H "Authorization: Bearer {token}" \
  https://votre-domaine.free.fr/api/v1/index.php?endpoint=events
```

#### Présences

| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `/members/{pseudo}/presences?status=o` | GET | Présences d'un membre (o=oui, n=non) |
| `/presences` | POST | Inscription/désinscription à un événement |

**Inscription** :
```bash
curl -X POST \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "dateHeure": "20250125200000",
    "joueur": "JohnDoe",
    "libelle": "MATCH",
    "presence": "o"
  }' \
  https://votre-domaine.free.fr/api/v1/index.php?endpoint=presences
```

Valeurs de `presence` :
- `o` : Présent
- `n` : Désinscription
- `!` : Absent

#### Ressources

| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `/resources/rules` | GET | URL règles FIVB |
| `/resources/competlib` | GET | URL calendrier compétitions |
| `/resources/ufolep` | GET | URL résultats UFOLEP |

## Format des réponses

### Succès

```json
{
  "success": true,
  "data": {...},
  "message": "Optional success message"
}
```

### Erreur

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human readable message",
    "details": {...}
  }
}
```

### Codes d'erreur

| Code | HTTP | Description |
|------|------|-------------|
| `INVALID_CREDENTIALS` | 401 | Identifiants incorrects |
| `INVALID_TOKEN` | 401 | Token invalide ou expiré |
| `MISSING_TOKEN` | 401 | Token manquant |
| `INVALID_INPUT` | 400 | Données invalides |
| `NOT_FOUND` | 404 | Ressource non trouvée |
| `CAPACITY_REACHED` | 409 | Nombre max d'inscrits atteint |
| `NOT_REGISTERED` | 409 | Présence non enregistrée |
| `DATABASE_ERROR` | 500 | Erreur base de données |
| `INTERNAL_ERROR` | 500 | Erreur serveur |

## Sécurité

### Implémenté

- Prepared statements MySQLi (protection SQL injection)
- Validation des inputs avec classe Validator
- Authentification JWT avec signature HMAC-SHA256
- Headers de sécurité (X-Content-Type-Options, X-Frame-Options, etc.)
- Sanitization des données (trim, suppression caractères de contrôle)
- Gestion des erreurs sans exposition de détails sensibles

### Recommandations

1. **HTTPS obligatoire** : Forcer HTTPS en production
2. **Credentials sécurisés** : Stocker les credentials DB hors webroot
3. **JWT Secret** : Générer une clé aléatoire forte
4. **CORS** : Restreindre l'origine autorisée en production
5. **Rate limiting** : Implémenter une limite de requêtes par IP
6. **Logs** : Activer les logs pour débogage (désactivés par défaut)

### Migration de OLD_PASSWORD

L'API utilise actuellement `OLD_PASSWORD()` de MySQL pour compatibilité avec l'existant.

**Plan de migration** :
1. Créer une colonne `new_password` dans `NPVB_Joueurs`
2. Lors du login, si mot de passe validé avec OLD_PASSWORD :
   - Hasher avec `password_hash()` et stocker dans `new_password`
3. Après migration complète, supprimer colonne `Password` et renommer `new_password`

## Maintenance

### Logs

Activer les logs dans `/api/v1/config/constants.php` :
```php
define('ENABLE_LOGGING', true);
```

Les logs seront écrits dans `/api/logs/api.log`

### Tests

Tester tous les endpoints après déploiement :
```bash
# Test API status
curl https://votre-domaine.free.fr/api/v1/index.php

# Test login
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{"username":"test","password":"test"}' \
  https://votre-domaine.free.fr/api/v1/index.php?endpoint=auth/login
```

## Migration depuis flux_v3.php

### Correspondance des endpoints

| Ancien | Nouveau |
|--------|---------|
| `?type=connection` | `POST /auth/login` (renvoie token JWT) |
| `?type=get_members` | `GET /members` |
| `?type=get_appartenances` | `GET /memberships` |
| `?type=get_events` | `GET /events` |
| `?type=get_presence` | `GET /events/{date}/presences` |
| `?type=get_presences` | `GET /members/{pseudo}/presences?status={o\|n}` |
| `?type=inscription` | `POST /presences` |
| `?type=rules` | `GET /resources/rules` |
| `?type=competlib` | `GET /resources/competlib` |
| `?type=ufolep` | `GET /resources/ufolep` |

### Changements pour les apps mobiles

1. **Authentification** :
   - L'ancien endpoint retournait `[{"Pseudonyme": "..."}]`
   - Le nouveau retourne `{"token": "...", "user": {...}}`
   - Stocker le token et l'envoyer dans toutes les requêtes

2. **Format réponses** :
   - Toutes les réponses sont wrapped dans `{"success": true, "data": ...}`
   - Les erreurs ont un format cohérent avec codes d'erreur

3. **Codes HTTP** :
   - Utilisation correcte des codes HTTP (200, 400, 401, 404, 500)

4. **Méthodes HTTP** :
   - Respecter GET/POST/PUT/DELETE selon l'action

## Support

En cas de problème :
1. Vérifier les logs serveur PHP
2. Vérifier les credentials DB dans `/config/database.php`
3. Tester avec un client REST (Postman, curl)
4. Vérifier que MySQLi est activé sur le serveur

## TODO / Évolutions futures

- [ ] Rate limiting par IP
- [ ] Pagination pour liste membres/événements
- [ ] Filtres avancés pour événements (par équipe, par date)
- [ ] Endpoint création événements (admin uniquement)
- [ ] Endpoint gestion membres (admin uniquement)
- [ ] Statistiques de présence
- [ ] Notifications push
- [ ] Webhooks pour événements critiques
- [ ] Documentation OpenAPI/Swagger
- [ ] Tests unitaires et d'intégration

## Licence

Propriétaire - NPVB (Nantes Plage Volley-Ball)
