# Démarrage rapide - API REST v1

Guide de mise en route en 5 minutes.

## 1. Installation (2 min)

### Uploader sur Free

```bash
# Via FTP (FileZilla, Cyberduck, etc.)
# Uploader le dossier /api/ à la racine de votre hébergement
# Structure finale sur le serveur:
# /
# ├── api/
# │   └── v1/
# ├── app/ (ancien)
# └── npvb-web/ (existant)
```

### Configuration DB

Éditer `/api/v1/config/database.php` :
```php
define('DB_HOST', 'ftpperso.free.fr');     // OK par défaut
define('DB_NAME', 'nantespvb');            // OK par défaut
define('DB_USER', 'nantespvb');            // OK par défaut
define('DB_PASS', 'wozd7pdo');             // OK par défaut
```

### Configuration JWT

Éditer `/api/v1/config/constants.php` :
```php
// IMPORTANT : Générer une clé aléatoire forte
define('JWT_SECRET', 'VOTRE_CLE_ALEATOIRE_ICI');
```

Générer une clé :
```bash
php -r "echo bin2hex(random_bytes(32));"
# Exemple résultat: 8f3a9b2c5d7e1f4a6b8c9d0e2f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a
```

## 2. Test (1 min)

### Test status API

Ouvrir dans navigateur :
```
https://votre-compte.free.fr/api/v1/index.php
```

Résultat attendu :
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

✅ Si vous voyez ce JSON, l'API fonctionne !

### Test login

```bash
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{"username":"VOTRE_PSEUDO","password":"VOTRE_MDP"}' \
  https://votre-compte.free.fr/api/v1/index.php?endpoint=auth/login
```

Résultat attendu :
```json
{
  "success": true,
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "user": {
      "Pseudonyme": "VOTRE_PSEUDO",
      "isAdmin": false
    }
  }
}
```

✅ Si vous recevez un token, l'authentification fonctionne !

### Test avec script automatisé

```bash
cd api
./test_api.sh https://votre-compte.free.fr/api/v1/index.php
```

## 3. Utilisation (2 min)

### Format des requêtes

**Toutes les URLs** (sans mod_rewrite) :
```
https://votre-compte.free.fr/api/v1/index.php?endpoint={endpoint}
```

**Login** (obtenir token) :
```bash
POST /api/v1/index.php?endpoint=auth/login
Body: {"username": "...", "password": "..."}
```

**Autres requêtes** (avec token) :
```bash
GET /api/v1/index.php?endpoint=members
Header: Authorization: Bearer {votre_token}
```

### Endpoints disponibles

| Endpoint | Méthode | Auth | Description |
|----------|---------|------|-------------|
| `auth/login` | POST | Non | Login et obtenir token |
| `auth/verify` | GET | Oui | Vérifier validité token |
| `members` | GET | Oui | Liste membres |
| `memberships` | GET | Oui | Appartenances équipes |
| `events` | GET | Oui | Liste événements |
| `events/{date}/presences` | GET | Oui | Présences événement |
| `members/{pseudo}/presences?status=o` | GET | Oui | Présences membre |
| `presences` | POST | Oui | Inscription événement |
| `resources/rules` | GET | Oui | URL règles FIVB |
| `resources/competlib` | GET | Oui | URL calendrier |
| `resources/ufolep` | GET | Oui | URL résultats |

### Exemples curl

**Login** :
```bash
TOKEN=$(curl -s -X POST \
  -H "Content-Type: application/json" \
  -d '{"username":"test","password":"test"}' \
  https://npvb.free.fr/api/v1/index.php?endpoint=auth/login | jq -r '.data.token')

echo $TOKEN
```

**Get members** :
```bash
curl -H "Authorization: Bearer $TOKEN" \
  https://npvb.free.fr/api/v1/index.php?endpoint=members
```

**Get events** :
```bash
curl -H "Authorization: Bearer $TOKEN" \
  https://npvb.free.fr/api/v1/index.php?endpoint=events | jq '.'
```

**Inscription événement** :
```bash
curl -X POST \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "dateHeure": "20250125200000",
    "joueur": "test",
    "libelle": "MATCH",
    "presence": "o"
  }' \
  https://npvb.free.fr/api/v1/index.php?endpoint=presences
```

## 4. Migration apps mobiles

### iOS (Swift)

**1. Modifier AuthService** :
```swift
// Ancien
let url = "\(baseURL)?type=connection&id=\(user)&pwd=\(pass)"

// Nouveau
let url = "\(baseURL)?endpoint=auth/login"
let body = ["username": user, "password": pass]
// Stocker token retourné
```

**2. Ajouter token dans requêtes** :
```swift
request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
```

**3. Mettre à jour les endpoints** :
```swift
// Ancien: ?type=get_members
// Nouveau: ?endpoint=members
```

Voir `MIGRATION_GUIDE.md` pour détails complets.

### Android (Kotlin)

Même principe que iOS.

## 5. Mise en production

### Checklist

- [ ] JWT_SECRET changé (clé aléatoire forte)
- [ ] HTTPS activé (forcer avec .htaccess)
- [ ] Tests tous endpoints OK
- [ ] Apps mobiles mises à jour
- [ ] Ancien flux_v3.php désactivé (après migration complète)

### HTTPS obligatoire

Ajouter au début de `index.php` :
```php
// Forcer HTTPS
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}
```

### Monitoring

Vérifier régulièrement :
- Logs PHP : `/logs/php_errors.log` (si configuré)
- Performances : Temps de réponse
- Erreurs : Codes 500, 401

## Résolution problèmes

### Erreur : "Database connection failed"

→ Vérifier credentials dans `/api/v1/config/database.php`

### Erreur : "Invalid or expired token"

→ Token expiré (24h), redemander login

### Erreur : "CORS blocked"

→ Vérifier origine autorisée dans `Response::send()`

### Erreur : "Method not allowed"

→ Vérifier méthode HTTP (GET vs POST)

### Page blanche

→ Activer `display_errors` temporairement :
```php
// Dans index.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

## Documentation complète

- **README.md** : Documentation API complète
- **ARCHITECTURE.md** : Architecture technique détaillée
- **MIGRATION_GUIDE.md** : Guide migration apps mobiles
- **SECURITY.md** : Sécurité et recommandations
- **test_api.sh** : Script de test automatisé

## Support

Questions ? Consultez d'abord :
1. README.md
2. MIGRATION_GUIDE.md
3. SECURITY.md

---

**Prêt en 5 minutes !** 🚀

L'API REST v1 est maintenant opérationnelle. Vous pouvez commencer à mettre à jour vos applications mobiles pour utiliser les nouveaux endpoints.
