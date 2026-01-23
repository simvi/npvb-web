# Sécurité et Recommandations - API REST v1

Ce document détaille les mesures de sécurité implémentées et les recommandations pour un déploiement en production.

## 1. Sécurité implémentée

### Protection SQL Injection

✅ **Prepared Statements MySQLi**
- Tous les repositories utilisent des prepared statements
- Aucune concaténation de chaînes dans les requêtes SQL
- Binding des paramètres avec `bind_param()`

**Exemple** :
```php
$stmt = $connection->prepare(
    "SELECT * FROM NPVB_Joueurs WHERE Pseudonyme = ? AND Password = ?"
);
$stmt->bind_param('ss', $username, $passwordHash);
```

### Validation des inputs

✅ **Classe Validator**
- Validation de tous les inputs utilisateurs
- Sanitization automatique (trim, htmlspecialchars)
- Règles de validation : required, email, username, numeric, etc.
- Suppression des caractères de contrôle

**Exemple** :
```php
$validator = new Validator($data);
$validator->required('username')
          ->username('username')
          ->minLength('password', 8);

if ($validator->fails()) {
    Response::validationError($validator->getErrors());
}
```

### Authentification JWT

✅ **Token sécurisé**
- Implémentation JWT native (HS256)
- Signature HMAC-SHA256
- Expiration configurable (24h par défaut)
- Vérification de signature avec `hash_equals()` (timing-safe)

✅ **Token Bearer**
- Token transmis via header `Authorization: Bearer {token}`
- Jamais dans l'URL ou les cookies
- Vérifié sur chaque requête authentifiée

### Headers de sécurité

✅ **Headers HTTP**
```php
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
```

### Gestion des erreurs

✅ **Pas d'exposition de détails**
- Messages d'erreur génériques pour l'utilisateur
- Détails sensibles logués côté serveur uniquement
- Pas de stack traces exposées

## 2. Limitations actuelles

### ⚠️ OLD_PASSWORD() MySQL

**Problème** : Utilise OLD_PASSWORD() obsolète pour compatibilité
- Fonction MySQL dépréciée
- Hash MD5-like peu sécurisé
- Vulnérable aux attaques par rainbow tables

**Plan de migration** :
```sql
-- 1. Ajouter colonne pour nouveaux hash
ALTER TABLE NPVB_Joueurs ADD COLUMN new_password VARCHAR(255);

-- 2. Migration progressive au login
-- Lors d'un login réussi avec OLD_PASSWORD:
UPDATE NPVB_Joueurs
SET new_password = 'bcrypt_hash'
WHERE Pseudonyme = 'user';

-- 3. Après migration complète (tous users loggés)
ALTER TABLE NPVB_Joueurs DROP COLUMN Password;
ALTER TABLE NPVB_Joueurs CHANGE new_password Password VARCHAR(255);
```

**Code pour migration progressive** :
```php
// Dans UserRepository::findByCredentials()
if ($user) {
    // Vérifier si new_password est vide
    if (empty($user['new_password'])) {
        // Créer nouveau hash et le sauvegarder
        $newHash = password_hash($password, PASSWORD_BCRYPT);
        $this->updatePassword($username, $newHash);
    }
}
```

### ⚠️ CORS ouvert

**Actuel** : `Access-Control-Allow-Origin: *`
- Accepte requêtes de toute origine

**Recommandation production** :
```php
// Dans Response::send()
$allowedOrigins = [
    'https://npvb.free.fr',
    'https://www.npvb.fr',
    'app://npvb-ios',
    'app://npvb-android'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header('Access-Control-Allow-Origin: https://npvb.free.fr');
}
```

### ⚠️ Pas de rate limiting

**Risque** : Attaques brute force sur login

**Recommandation** : Implémenter rate limiting
```php
class RateLimiter {
    public static function checkLimit($ip, $action, $maxAttempts = 5, $windowMinutes = 15) {
        // Stocker en session ou DB
        $key = "ratelimit_{$ip}_{$action}";
        $attempts = $_SESSION[$key]['attempts'] ?? 0;
        $firstAttempt = $_SESSION[$key]['time'] ?? time();

        if (time() - $firstAttempt > $windowMinutes * 60) {
            // Reset window
            $_SESSION[$key] = ['attempts' => 1, 'time' => time()];
            return true;
        }

        if ($attempts >= $maxAttempts) {
            Response::error('Too many attempts', 'RATE_LIMIT_EXCEEDED', 429);
        }

        $_SESSION[$key]['attempts']++;
        return true;
    }
}

// Usage dans AuthController::login()
RateLimiter::checkLimit($_SERVER['REMOTE_ADDR'], 'login');
```

### ⚠️ Pas de logging

**Actuel** : Logging désactivé par défaut

**Recommandation** : Logger événements critiques
```php
// Dans config/constants.php
define('ENABLE_LOGGING', true);
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR

// Logger dans Auth, présences critiques
Logger::info('User login successful', ['username' => $username]);
Logger::warning('Failed login attempt', ['username' => $username, 'ip' => $ip]);
Logger::error('Database error', ['query' => $query, 'error' => $error]);
```

## 3. Recommandations pour production

### HTTPS obligatoire

**Critique** : Forcer HTTPS pour éviter interception token

**Configuration .htaccess** :
```apache
# Forcer HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

**Configuration PHP** :
```php
// Au début de index.php
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}
```

### Credentials hors webroot

**Actuel** : Credentials dans `/api/v1/config/database.php`

**Recommandation** :
```
/home/user/
  ├── www/           # Webroot
  │   └── api/
  └── config/        # Hors webroot
      └── db_credentials.php
```

```php
// Dans config/database.php
if (file_exists('/home/user/config/db_credentials.php')) {
    require_once '/home/user/config/db_credentials.php';
} else {
    // Fallback pour dev
    define('DB_HOST', 'localhost');
    define('DB_USER', 'dev');
    define('DB_PASS', 'dev');
    define('DB_NAME', 'npvb_dev');
}
```

### JWT Secret fort

**Générer clé aléatoire** :
```bash
# Ligne de commande
php -r "echo bin2hex(random_bytes(32));"

# Ou en ligne
openssl rand -hex 32
```

**Stocker dans variable d'environnement** :
```php
// .env (hors webroot)
JWT_SECRET=8f3a9b2c5d7e1f4a6b8c9d0e2f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a

// config/constants.php
$jwtSecret = getenv('JWT_SECRET');
if (!$jwtSecret) {
    throw new Exception('JWT_SECRET not configured');
}
define('JWT_SECRET', $jwtSecret);
```

### Permissions fichiers

```bash
# Fichiers
chmod 644 api/v1/index.php
chmod 644 api/v1/**/*.php

# Répertoires
chmod 755 api/v1

# Configuration (si dans webroot)
chmod 600 api/v1/config/*.php

# Logs (si utilisés)
chmod 640 api/logs/*.log
```

### Désactiver display_errors

```php
// En production dans index.php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/logs/php_errors.log');
```

### Filtrer logs Apache/Nginx

Ne pas logger les tokens dans les access logs :
```nginx
# Nginx
log_format secure '$remote_addr - $remote_user [$time_local] '
                  '"$request_method $uri $server_protocol" '
                  '$status $body_bytes_sent '
                  '"$http_referer" "$http_user_agent"';

access_log /var/log/nginx/access.log secure;
```

## 4. Checklist de déploiement

### Configuration

- [ ] HTTPS activé et forcé
- [ ] Credentials DB hors webroot ou en env vars
- [ ] JWT_SECRET généré aléatoirement
- [ ] display_errors désactivé
- [ ] Logging activé avec rotation
- [ ] CORS restreint aux origines légitimes
- [ ] Rate limiting implémenté

### Sécurité

- [ ] Prepared statements vérifiés partout
- [ ] Validation inputs sur tous endpoints
- [ ] Headers de sécurité présents
- [ ] Gestion erreurs sans exposition détails
- [ ] Permissions fichiers correctes
- [ ] OLD_PASSWORD() migration planifiée

### Tests

- [ ] Tests tous endpoints avec authentification
- [ ] Tests gestion erreurs (401, 404, 500)
- [ ] Tests validation inputs (champs manquants, invalides)
- [ ] Tests token expiré
- [ ] Tests capacité max événements
- [ ] Tests charge (load testing)

### Monitoring

- [ ] Logs centralisés
- [ ] Alertes erreurs critiques
- [ ] Monitoring uptime
- [ ] Monitoring performance DB

## 5. Réponse aux incidents

### Token compromis

1. Changer JWT_SECRET immédiatement
2. Invalider tous les tokens existants
3. Forcer re-login tous utilisateurs
4. Vérifier logs pour activité suspecte

### Injection SQL détectée

1. Identifier endpoint vulnérable
2. Vérifier si exploitation réussie (logs DB)
3. Patcher endpoint avec prepared statement
4. Audit complet de tous les endpoints
5. Informer utilisateurs si données exposées

### Brute force sur login

1. Activer rate limiting immédiatement
2. Bloquer IPs suspectes (fail2ban)
3. Notifier utilisateurs avec activité anormale
4. Forcer reset password si nécessaire

## 6. Audit de sécurité

### Outils recommandés

```bash
# Scan vulnérabilités PHP
composer require --dev roave/security-advisories:dev-latest

# Scan code statique
./vendor/bin/phpstan analyse api/

# Test injection SQL
sqlmap -u "https://npvb.free.fr/api/v1/..." --batch

# Test OWASP
owasp-zap-cli quick-scan https://npvb.free.fr/api/v1/
```

### Audit manuel

- [ ] Vérifier tous points d'entrée utilisateur
- [ ] Tester tous endpoints sans auth
- [ ] Tester tous endpoints avec token invalide
- [ ] Tenter injections SQL sur tous paramètres
- [ ] Tenter XSS sur tous champs texte
- [ ] Vérifier exposition informations sensibles
- [ ] Tester upload fichiers (si implémenté)

## 7. Conformité RGPD

### Données personnelles collectées

- Pseudonyme, Nom, Prénom
- Email, Téléphone
- Adresse postale
- Date de naissance
- Numéro de licence

### Mesures à implémenter

- [ ] Politique de confidentialité
- [ ] Consentement explicite collecte données
- [ ] Droit d'accès (endpoint GET user data)
- [ ] Droit de rectification (endpoint UPDATE user)
- [ ] Droit à l'effacement (endpoint DELETE user)
- [ ] Export données format portable (JSON)
- [ ] Durée conservation définie
- [ ] Journalisation accès données sensibles

## 8. Ressources

### Documentation

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Guide](https://paragonie.com/blog/2017/12/2018-guide-building-secure-php-software)
- [JWT Best Practices](https://tools.ietf.org/html/rfc8725)

### Outils

- [OWASP ZAP](https://www.zaproxy.org/)
- [SQLMap](http://sqlmap.org/)
- [PHPStan](https://phpstan.org/)

---

**Dernière mise à jour** : 2025-01-22
**Version** : 1.0
