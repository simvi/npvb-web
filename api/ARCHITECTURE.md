# Architecture API REST v1 - Documentation technique

## Vue d'ensemble

L'API REST v1 remplace complètement `flux_v3.php` avec une architecture moderne, maintenable et sécurisée, tout en restant compatible avec les limitations de l'hébergement Free (PHP ancien, MySQL ancien).

## Principes architecturaux

### Séparation des responsabilités (MVC adapté)

```
Requête HTTP
    ↓
Front Controller (index.php)
    ↓ routing
Controller (gestion requête HTTP)
    ↓ appel service
Service (logique métier)
    ↓ appel repository
Repository (accès données)
    ↓ requête DB
Database (connexion MySQLi)
    ↓
MySQL
```

### Avantages de cette architecture

- **Testabilité** : Chaque couche peut être testée indépendamment
- **Maintenabilité** : Code organisé par responsabilité
- **Évolutivité** : Facile d'ajouter de nouveaux endpoints
- **Sécurité** : Séparation claire entre logique et données
- **Réutilisabilité** : Services et repositories réutilisables

## Structure des fichiers

```
/api/
├── v1/
│   ├── config/                 # Configuration
│   │   ├── database.php        # Credentials DB
│   │   └── constants.php       # Constantes globales
│   │
│   ├── core/                   # Classes fondamentales
│   │   ├── Database.php        # Singleton connexion MySQLi
│   │   ├── Response.php        # Réponses JSON normalisées
│   │   ├── Auth.php            # JWT authentication
│   │   └── Validator.php       # Validation inputs
│   │
│   ├── repositories/           # Accès données (Data Layer)
│   │   ├── UserRepository.php
│   │   ├── MemberRepository.php
│   │   ├── EventRepository.php
│   │   └── PresenceRepository.php
│   │
│   ├── services/               # Logique métier (Business Layer)
│   │   ├── AuthService.php
│   │   ├── MemberService.php
│   │   ├── EventService.php
│   │   └── PresenceService.php
│   │
│   ├── controllers/            # Gestion requêtes HTTP (Presentation Layer)
│   │   ├── AuthController.php
│   │   ├── MembersController.php
│   │   ├── EventsController.php
│   │   ├── PresencesController.php
│   │   └── ResourcesController.php
│   │
│   └── index.php               # Front Controller (routing)
│
├── .htaccess                   # Configuration Apache (optionnel)
├── README.md                   # Documentation API
├── MIGRATION_GUIDE.md          # Guide migration apps mobiles
├── SECURITY.md                 # Sécurité et recommandations
├── ARCHITECTURE.md             # Ce fichier
└── test_api.sh                 # Script de test automatisé
```

## Couches de l'architecture

### 1. Config Layer

**Responsabilité** : Configuration centralisée

**Fichiers** :
- `database.php` : Credentials et paramètres DB
- `constants.php` : Constantes globales (JWT, codes erreur, etc.)

**Principe** : Configuration DRY (Don't Repeat Yourself)

### 2. Core Layer

**Responsabilité** : Fonctionnalités transverses réutilisables

#### Database.php
```php
// Singleton pattern pour connexion unique
$db = Database::getInstance();
$connection = $db->getConnection();
```

**Fonctionnalités** :
- Connexion MySQLi singleton
- Prepared statements
- Gestion encodage UTF-8
- Fermeture automatique

#### Response.php
```php
// Réponses normalisées
Response::success($data, 'Message');
Response::error('Error', ERROR_CODE, 400);
Response::notFound();
Response::unauthorized();
```

**Fonctionnalités** :
- Format JSON cohérent
- Codes HTTP appropriés
- Headers de sécurité
- Logging optionnel

#### Auth.php
```php
// JWT léger sans lib
$token = Auth::generateToken($username);
$payload = Auth::verifyToken($token);
Auth::authenticate(); // Vérifie et retourne payload ou erreur
```

**Fonctionnalités** :
- Génération JWT (HS256)
- Vérification signature
- Extraction token depuis headers
- Gestion expiration

#### Validator.php
```php
$validator = new Validator($data);
$validator->required('field')
          ->email('email')
          ->minLength('password', 8);

if ($validator->fails()) {
    $errors = $validator->getErrors();
}
```

**Fonctionnalités** :
- Validation chainable
- Règles prédéfinies
- Messages personnalisables
- Sanitization automatique

### 3. Repository Layer (Data Access)

**Responsabilité** : Accès et manipulation données DB

**Principe** : Repository Pattern - Abstraction de la source de données

**Caractéristiques** :
- Utilise uniquement prepared statements
- Retourne des tableaux associatifs
- Nettoie les données (trim, suppression caractères contrôle)
- Méthodes CRUD simples

**Exemple** :
```php
class MemberRepository {
    public function findAll() {
        $stmt = $connection->prepare("SELECT * FROM NPVB_Joueurs WHERE etat = 'V'");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function findByUsername($username) {
        $stmt = $connection->prepare("SELECT * FROM NPVB_Joueurs WHERE Pseudonyme = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
```

### 4. Service Layer (Business Logic)

**Responsabilité** : Logique métier et orchestration

**Principe** : Service Pattern - Logique réutilisable indépendante de la présentation

**Caractéristiques** :
- Valide les inputs (via Validator)
- Implémente la logique métier complexe
- Coordonne plusieurs repositories si nécessaire
- Retourne format standardisé `['success' => bool, 'data' => ...]`
- Pas de gestion HTTP (codes, headers)

**Exemple** :
```php
class PresenceService {
    public function managePresence($date, $joueur, $libelle, $presence) {
        // 1. Validation
        $validator = new Validator([...]);
        if ($validator->fails()) {
            return ['success' => false, 'errors' => $validator->getErrors()];
        }

        // 2. Logique métier (vérification capacité, etc.)
        if ($libelle === 'SEANCE') {
            $count = $this->presenceRepository->countPresent($date, $libelle);
            $max = $this->eventRepository->getMaxSubscribers($date, $libelle);
            if ($count >= $max) {
                return ['success' => false, 'code' => ERROR_CAPACITY_REACHED];
            }
        }

        // 3. Opération données
        $result = $this->presenceRepository->create(...);

        return ['success' => $result];
    }
}
```

### 5. Controller Layer (Presentation)

**Responsabilité** : Gestion requêtes/réponses HTTP

**Principe** : Controller Pattern - Interface entre HTTP et logique métier

**Caractéristiques** :
- Vérifie méthode HTTP (GET/POST/PUT/DELETE)
- Authentifie requête (sauf login)
- Parse les inputs (JSON body, query params, path params)
- Appelle le service approprié
- Transforme résultat service en réponse HTTP (via Response)

**Exemple** :
```php
class EventsController {
    public function getAll() {
        // 1. Vérifier méthode
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::methodNotAllowed(['GET']);
        }

        // 2. Authentifier
        Auth::authenticate();

        // 3. Appeler service
        $result = $this->eventService->getAllEvents();

        // 4. Répondre
        if (!$result['success']) {
            Response::error($result['message'] ?? 'Error', ERROR_INTERNAL);
        }
        Response::success($result['data']);
    }
}
```

### 6. Front Controller (index.php)

**Responsabilité** : Point d'entrée unique, routing

**Principe** : Front Controller Pattern

**Flux** :
```
1. Gestion CORS (OPTIONS)
2. Gestion erreurs globales
3. Chargement classes (require_once)
4. Parse endpoint depuis URL
5. Routing vers controller approprié
6. Extraction paramètres (path/query)
7. Appel méthode controller
```

**Routing** :
```php
// Format: /api/v1/index.php?endpoint=resource/action

$endpoint = $_GET['endpoint'];
$segments = explode('/', $endpoint);

switch ($segments[0]) {
    case 'auth':
        $controller = new AuthController();
        if ($segments[1] === 'login') {
            $controller->login();
        }
        break;

    case 'members':
        $controller = new MembersController();
        $controller->getAll();
        break;

    // ...
}
```

## Flux de données

### Exemple : Login

```
1. Client → POST /api/v1/?endpoint=auth/login
   Body: {"username": "test", "password": "test"}

2. index.php → Parse endpoint → Route vers AuthController::login()

3. AuthController::login()
   - Vérifie méthode POST
   - Parse JSON body
   - Appelle AuthService::login()

4. AuthService::login()
   - Valide inputs (Validator)
   - Appelle UserRepository::findByCredentials()

5. UserRepository::findByCredentials()
   - Hash password avec OLD_PASSWORD()
   - Prepared statement MySQLi
   - Retourne user ou null

6. AuthService::login()
   - Si user trouvé → Génère JWT token (Auth::generateToken())
   - Retourne ['success' => true, 'data' => ['token' => ...]]

7. AuthController::login()
   - Vérifie résultat
   - Response::success(['token' => ...])

8. Response::success()
   - Set headers HTTP
   - Encode JSON
   - Echo et exit

9. Client ← HTTP 200
   Body: {"success": true, "data": {"token": "..."}}
```

### Exemple : Get Events (authentifié)

```
1. Client → GET /api/v1/?endpoint=events
   Header: Authorization: Bearer {token}

2. index.php → Route vers EventsController::getAll()

3. EventsController::getAll()
   - Vérifie méthode GET
   - Auth::authenticate() → Vérifie token ou erreur 401
   - Appelle EventService::getAllEvents()

4. EventService::getAllEvents()
   - Appelle EventRepository::findAll()

5. EventRepository::findAll()
   - Prepared statement MySQLi
   - SELECT * FROM NPVB_Evenements WHERE ...
   - Nettoie résultats
   - Retourne array d'events

6. EventService::getAllEvents()
   - Retourne ['success' => true, 'data' => $events]

7. EventsController::getAll()
   - Response::success($events)

8. Client ← HTTP 200
   Body: {"success": true, "data": [{...}, {...}]}
```

## Patterns utilisés

### Singleton Pattern
- `Database::getInstance()` : Une seule connexion DB

### Repository Pattern
- Repositories : Abstraction accès données

### Service Pattern
- Services : Logique métier réutilisable

### Front Controller Pattern
- `index.php` : Point d'entrée unique

### Factory Pattern (implicite)
- Controllers instanciés à la demande selon routing

### Chain of Responsibility (partiel)
- Validator : Chaînage des règles de validation

## Conventions de code

### Naming

**Classes** : PascalCase
```php
class AuthController
class MemberRepository
```

**Méthodes** : camelCase
```php
public function getAllMembers()
public function findByUsername($username)
```

**Variables** : camelCase
```php
$username = 'test';
$maxSubscribers = 10;
```

**Constantes** : UPPER_SNAKE_CASE
```php
define('JWT_SECRET', '...');
define('ERROR_INVALID_TOKEN', 'INVALID_TOKEN');
```

### Structure méthodes

```php
public function methodName($param1, $param2) {
    // 1. Validation
    // 2. Logique métier
    // 3. Retour résultat
}
```

### Commentaires

```php
/**
 * Description de la méthode
 *
 * @param string $param1 Description
 * @return array Format du retour
 */
```

## Format des réponses

### Succès
```json
{
  "success": true,
  "data": {...},
  "message": "Optional message"
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

## Extensibilité

### Ajouter un nouveau endpoint

1. **Créer le repository** (si nouvelle table)
```php
// repositories/NewRepository.php
class NewRepository {
    public function findAll() { ... }
}
```

2. **Créer le service**
```php
// services/NewService.php
class NewService {
    private $newRepository;

    public function __construct() {
        $this->newRepository = new NewRepository();
    }

    public function getSomething() {
        $data = $this->newRepository->findAll();
        return ['success' => true, 'data' => $data];
    }
}
```

3. **Créer le controller**
```php
// controllers/NewController.php
class NewController {
    private $newService;

    public function __construct() {
        $this->newService = new NewService();
    }

    public function index() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::methodNotAllowed(['GET']);
        }
        Auth::authenticate();
        $result = $this->newService->getSomething();
        Response::success($result['data']);
    }
}
```

4. **Ajouter le routing**
```php
// index.php
require_once __DIR__ . '/repositories/NewRepository.php';
require_once __DIR__ . '/services/NewService.php';
require_once __DIR__ . '/controllers/NewController.php';

// Dans le switch
case 'new':
    $controller = new NewController();
    $controller->index();
    break;
```

### Ajouter une règle de validation

```php
// core/Validator.php
public function customRule($field, $param, $message = null) {
    if (isset($this->data[$field])) {
        // Logique de validation
        if (!$isValid) {
            $this->errors[$field][] = $message ?? "Default message";
        }
    }
    return $this;
}
```

## Compatibilité hébergement Free

### Contraintes respectées

✅ **PHP natif** : Aucune dépendance externe
✅ **MySQLi** : Compatible versions anciennes MySQL
✅ **Pas de mod_rewrite requis** : Routing via query string
✅ **Léger** : Pas de framework lourd
✅ **OLD_PASSWORD()** : Compatibilité avec DB existante

### Optimisations pour Free

- Connexion DB singleton (évite multiples connexions)
- Pas de sessions PHP (JWT stateless)
- Logs désactivés par défaut (économise I/O)
- Pas de cache (pas de extension requise)

## Tests

### Test manuel avec curl

```bash
# Login
curl -X POST -H "Content-Type: application/json" \
  -d '{"username":"test","password":"test"}' \
  "http://localhost/api/v1/index.php?endpoint=auth/login"

# Get members (authentifié)
curl -H "Authorization: Bearer {token}" \
  "http://localhost/api/v1/index.php?endpoint=members"
```

### Script automatisé

```bash
chmod +x api/test_api.sh
./api/test_api.sh https://npvb.free.fr/api/v1/index.php
```

## Maintenance

### Ajouter des logs

```php
// config/constants.php
define('ENABLE_LOGGING', true);

// Utilisation
if (ENABLE_LOGGING) {
    error_log("API: User $username logged in");
}
```

### Monitoring

Surveiller :
- Logs erreurs PHP (`error_log`)
- Logs accès Apache/Nginx
- Temps de réponse (via headers ou monitoring externe)
- Erreurs 500 (bugs)
- Erreurs 401 (tentatives accès non autorisé)

## Performance

### Optimisations implémentées

- Connexion DB persistante (singleton)
- Prepared statements compilés une fois
- Pas de sessions (overhead)
- JSON natif PHP (rapide)
- Pas de ORM (overhead)

### Optimisations futures

- Cache réponses GET (Redis/Memcached si disponible)
- Pagination résultats (LIMIT/OFFSET)
- Compression responses (gzip)
- CDN pour assets statiques

## Conclusion

Cette architecture offre un équilibre entre :
- **Modernité** : REST, JWT, architecture claire
- **Compatibilité** : Fonctionne sur hébergement limité
- **Maintenabilité** : Code organisé, extensible
- **Sécurité** : Prepared statements, validation, JWT
- **Performance** : Léger, pas de overhead

Elle constitue une base solide pour l'évolution future de l'application NPVB.

---

**Version** : 1.0
**Date** : 2025-01-22
