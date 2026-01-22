# 🔍 AUDIT TECHNIQUE - NPVB-WEB

## 📋 RÉSUMÉ EXÉCUTIF

**Type de projet:** Application web PHP legacy pour gestion de club de volleyball
**Hébergement:** FTPerso chez Free (hébergement mutualisé)
**Technologies:** PHP (ancien), MySQL, HTML/CSS/JavaScript vanilla
**Architecture:** MVC basique avec routing simple
**Volume de code:** ~7 500 lignes de PHP
**API mobile:** 3 versions de fichiers flux dans `/app/`

**Verdict global:** ⚠️ **Le projet fonctionne mais présente des vulnérabilités critiques et une dette technique importante**

---

## 🏗️ 1. STRUCTURE DU PROJET

### Architecture actuelle

```
npvb-web/
├── app/                          # 🔴 CRITIQUE - API pour apps iOS/Android
│   ├── flux_v3.php              # Version actuelle de l'API (342 lignes)
│   ├── flux.php                 # API v1 legacy (224 lignes)
│   ├── flux_limite.php          # API avec gestion des limites (235 lignes)
│   └── connexion_mysql.php      # Connexion directe MySQL (61 lignes)
│
├── Feuilles de style/           # CSS organisés par page
├── PASSWD/                      # 🔴 Configuration des credentials (VIDE !)
├── sessions/                    # Sessions PHP
├── index.php                    # 🔴 Point d'entrée principal (181 lignes)
├── _entete.inc.php             # 🔴 Authentification (64 lignes)
├── classes.inc.php             # Classes métier (141 lignes)
├── fonctions.inc.php           # Fonctions DB (154 lignes)
├── variables.inc.php           # Configuration (53 lignes)
└── *.inc.php                   # Pages de l'application
```

### Points positifs ✅

- Organisation claire des fichiers par fonction
- Séparation des concerns (classes, fonctions, variables)
- CSS modulaires par page
- Versioning de l'API (`flux.php` → `flux_v3.php`)

### Points négatifs ❌

- Pas de gestion de dépendances
- Pas de structure MVC moderne
- Mélange de logique métier et présentation
- Pas de tests automatisés
- Configuration manquante (`PASSWD/` vide)

---

## 📱 2. FICHIERS CRITIQUES POUR LES APPS MOBILES

### Fichier principal : `app/flux_v3.php`

**Rôle:** API REST basique en JSON pour les applications iOS et Android

**Endpoints exposés:**

| Endpoint | Méthode | Description | Vulnérabilité |
|----------|---------|-------------|---------------|
| `?type=get_members` | GET | Liste des joueurs actifs | ⚠️ Expose emails/téléphones sans auth |
| `?type=get_appartenances` | GET | Appartenance équipes | ✅ OK |
| `?type=get_events` | GET | Liste des événements | ✅ OK |
| `?type=get_presence` | GET | Présences pour une date | ⚠️ SQL Injection (ligne 170) |
| `?type=get_presences` | GET | Présences d'un joueur | ⚠️ SQL Injection (ligne 195) |
| `?type=inscription` | GET | Inscription/désinscription | ⚠️ SQL Injection multiples |
| `?type=connection` | GET | Authentification | 🔴 CRITIQUE - SQL Injection (ligne 146) |
| `?type=rules` | GET | Lien vers règlement FIVB | ✅ OK |
| `?type=competlib` | GET | Lien calendrier Competlib | ✅ OK |
| `?type=ufolep` | GET | Lien résultats UFOLEP | ✅ OK |

### Exemple de vulnérabilité SQL Injection

```php
// flux_v3.php:146 - Authentification
$request = "SELECT Pseudonyme FROM NPVB_Joueurs
            WHERE etat = 'V'
            AND Pseudonyme = '".$identifiant."'
            AND Password = OLD_PASSWORD('".$pwd."')";
```

**Exploitation possible:**
```
?type=connection&id=admin' OR '1'='1&pwd=anything
```

### Problème majeur : Credentials en clair (lignes 62-65)

```php
$server = "ftpperso.free.fr";
$database = "nantespvb";
$username = "nantespvb";
$pwd = "wozd7pdo";  // ⚠️ MOT DE PASSE EN CLAIR DANS LE CODE
```

---

## 🔐 3. PROBLÈMES DE SÉCURITÉ CRITIQUES

### 🔴 Niveau CRITIQUE

#### 1. **SQL Injection massive**
- **Fichiers concernés:** `flux_v3.php`, `flux.php`, toutes les pages `*.inc.php`
- **Impact:** Accès complet à la base de données, vol de données, modification/suppression
- **Localisation:** index.php:25,29 + flux_v3.php:146,170,195,222,228,248,259,282,293

```php
// index.php:25 - Utilisation de eval() !!!
eval("$".$key." = \"".$val."\";");
```

#### 2. **Credentials hardcodés**
- **Fichier:** app/flux_v3.php:64-65
- **Impact:** Accès direct à la base de données si le fichier est exposé
- **Données exposées:** Login/password MySQL

#### 3. **Exposition de données sensibles**
- **Endpoint:** `?type=get_members`
- **Impact:** TOUS les emails, téléphones, adresses accessibles sans authentification
- **Risque:** RGPD non conforme, spam, phishing

#### 4. **Utilisation de `mysql_*` dépréciées**
- **Toute l'application** utilise `mysql_connect()`, `mysql_query()`, etc.
- **Problème:** Supprimé depuis PHP 7.0 (2015)
- **Impact:** Application non portable, vulnérable

### ⚠️ Niveau ÉLEVÉ

#### 5. **Authentification faible**
- Pas de limitation des tentatives (brute force possible)
- Pas de CSRF token
- Sessions non sécurisées (pas de HttpOnly, Secure, SameSite)
- Utilisation de `OLD_PASSWORD()` MySQL (obsolète)

#### 6. **Pas de HTTPS forcé**
- Identifiants envoyés en clair sur le réseau
- Sessions interceptables (man-in-the-middle)

#### 7. **Gestion des sessions obsolète**
```php
// _entete.inc.php:26 - session_register() supprimée en PHP 7
session_register("Pseudonyme");
```

---

## ⚙️ 4. DETTE TECHNIQUE

### Technologies obsolètes

| Technologie | Version actuelle | Problème |
|-------------|-----------------|----------|
| PHP | Probablement < 7.0 | Fin de support |
| MySQL API | `mysql_*` | Supprimée en PHP 7.0 |
| Sessions | `session_register()` | Supprimée en PHP 7.0 |
| Password | `OLD_PASSWORD()` | Obsolète depuis MySQL 5.7 |
| JSON | Polyfill custom | Native depuis PHP 5.2 |

### Mauvaises pratiques identifiées

1. **Pas de séparation des concerns**
   - Logique métier dans les vues
   - SQL dans les contrôleurs
   - Pas de couche d'abstraction

2. **Code redondant**
   - 3 versions de l'API flux (v1, v2, v3)
   - Logique dupliquée entre fichiers

3. **Pas de validation des données**
   - Inputs non filtrés
   - Pas de sanitization
   - Pas de typage

4. **Encodage mixte**
   - ISO-8859-1 dans le HTML
   - UTF-8 dans MySQL
   - Risques d'erreurs d'affichage

5. **Gestion des erreurs inexistante**
   - `die()` affiche les erreurs SQL
   - Pas de logging
   - Pas de monitoring

---

## 🎯 5. RECOMMANDATIONS PRIORITAIRES

### 🔥 URGENT (À faire immédiatement)

#### 1. **Sécuriser les credentials** (2h de travail)

**Problème:** Mot de passe MySQL en clair dans `flux_v3.php:65`

**Solution:**
```php
// Créer PASSWD/_passwrds.inc.php avec :
<?php
$basesql = "ftpperso.free.fr";
$labasededonnees = "nantespvb";
$utilisateursql = "nantespvb";
$motdepassesql = "w0zd7pd0"; // Obfusqué comme dans _entete.inc.php
?>

// Modifier app/flux_v3.php:61-65 par :
<?php
include("../PASSWD/_passwrds.inc.php");
$motdepassesqlok = $motdepassesql{4}.$motdepassesql{1}...;
$mySql = mysql_connect($basesql, $utilisateursql, $motdepassesqlok);
?>
```

**Ajoutez dans `.htaccess` du dossier PASSWD:**
```apache
<Files "*">
    Order allow,deny
    Deny from all
</Files>
```

#### 2. **Ajouter authentification à l'API** (4h de travail)

**Problème:** Endpoints sensibles accessibles sans auth

**Solution minimaliste:**
```php
// En haut de flux_v3.php, ajouter :
function checkAuth() {
    if (!isset($_GET['token'])) {
        header('HTTP/1.0 401 Unauthorized');
        die(json_encode(['error' => 'Authentication required']));
    }

    // Vérifier le token dans la DB
    $token = mysql_real_escape_string($_GET['token']);
    $result = mysql_query("SELECT Pseudonyme FROM NPVB_Joueurs
                          WHERE SessionToken='$token'
                          AND Etat='V'");
    if (!mysql_num_rows($result)) {
        header('HTTP/1.0 401 Unauthorized');
        die(json_encode(['error' => 'Invalid token']));
    }
    return mysql_fetch_assoc($result)['Pseudonyme'];
}

// Avant chaque endpoint sensible :
if ($_GET['type'] == "get_members") {
    $pseudo = checkAuth(); // Vérifie l'auth
    // ... suite du code
}
```

**Migration base de données:**
```sql
ALTER TABLE NPVB_Joueurs
ADD COLUMN SessionToken VARCHAR(64) NULL,
ADD COLUMN TokenExpiry DATETIME NULL;
```

#### 3. **Protéger contre SQL Injection** (8h de travail)

**Problème:** Toutes les requêtes sont vulnérables

**Solution (workaround compatible FTPerso):**
```php
// Remplacer toutes les requêtes par mysql_real_escape_string()
// AVANT :
$request = "SELECT * FROM NPVB_Presence
            WHERE DateHeure = ".$_GET['date'];

// APRÈS :
$date = mysql_real_escape_string($_GET['date']);
$request = "SELECT * FROM NPVB_Presence
            WHERE DateHeure = '$date'";
```

**Meilleure solution (si possible):** Migrer vers `mysqli_*` avec prepared statements

#### 4. **Restreindre l'accès aux données sensibles** (2h)

**Problème:** `get_members` expose emails/téléphones sans auth

**Solution:**
```php
// flux_v3.php:85 - Ajouter filtre selon auth
if ($_GET['type'] == "get_members") {
    $pseudo = checkAuth(); // Vérifie l'auth

    // Vérifier si admin
    $isAdmin = mysql_fetch_assoc(mysql_query(
        "SELECT DieuToutPuissant FROM NPVB_Joueurs
         WHERE Pseudonyme='".mysql_real_escape_string($pseudo)."'"
    ))['DieuToutPuissant'] == 'o';

    if ($isAdmin) {
        // Admin : toutes les données
        $request = "SELECT Pseudonyme, Nom, Prenom, Sexe, Email, Telephones, ...
                    FROM NPVB_Joueurs WHERE etat = 'V'";
    } else {
        // Membre : données limitées
        $request = "SELECT Pseudonyme, Nom, Prenom, Sexe
                    FROM NPVB_Joueurs WHERE etat = 'V' AND Accord='o'";
    }
    // ... suite
}
```

---

### 🟡 IMPORTANT (À faire dans les 3 mois)

#### 5. **Migrer de mysql_* vers mysqli_***

**Pourquoi:** `mysql_*` ne fonctionne plus sur PHP 7+

**Comment (compatible FTPerso):**

**Créer un fichier `db.inc.php`:**
```php
<?php
// Wrapper de compatibilité mysqli
function db_connect($host, $user, $pass) {
    return mysqli_connect($host, $user, $pass);
}

function db_select_db($db, $link) {
    return mysqli_select_db($link, $db);
}

function db_query($query, $link) {
    return mysqli_query($link, $query);
}

function db_fetch_assoc($result) {
    return mysqli_fetch_assoc($result);
}

function db_fetch_object($result) {
    return mysqli_fetch_object($result);
}

function db_num_rows($result) {
    return mysqli_num_rows($result);
}

function db_escape_string($link, $string) {
    return mysqli_real_escape_string($link, $string);
}
?>
```

**Puis remplacer progressivement:**
```php
// AVANT :
$result = mysql_query($request);

// APRÈS :
$result = db_query($request, $sdblink);
```

#### 6. **Nettoyer le code obsolète**

- Supprimer `flux.php` et `flux_limite.php` (si apps mobiles utilisent uniquement v3)
- Supprimer `connexion_mysql.php` (dangereux)
- Supprimer le polyfill JSON (lignes 4-39 de flux_v3.php)

#### 7. **Ajouter un système de logging**

**Créer `logger.inc.php`:**
```php
<?php
function logError($message, $context = []) {
    $logFile = 'logs/error.log';
    $date = date('Y-m-d H:i:s');
    $line = "[$date] $message " . json_encode($context) . "\n";
    file_put_contents($logFile, $line, FILE_APPEND);
}

function logAuth($pseudo, $success, $ip) {
    $logFile = 'logs/auth.log';
    $date = date('Y-m-d H:i:s');
    $status = $success ? 'SUCCESS' : 'FAILED';
    $line = "[$date] $status - $pseudo from $ip\n";
    file_put_contents($logFile, $line, FILE_APPEND);
}
?>
```

#### 8. **Améliorer les sessions**

```php
// _entete.inc.php - Ajouter AVANT session_start():
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => 'nantespvb.free.fr',
    'secure' => false, // Pas de HTTPS chez Free
    'httponly' => true,
    'samesite' => 'Strict'
]);
```

---

### 🟢 AMÉLIORATIONS (Quand vous aurez le temps)

#### 9. **Créer une vraie API REST**

Créer `app/api.php` avec routing propre:
```php
<?php
// Routing simple
$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['path'] ?? '';

switch ($path) {
    case 'members':
        if ($method == 'GET') getMembersHandler();
        break;
    case 'events':
        if ($method == 'GET') getEventsHandler();
        break;
    case 'presence':
        if ($method == 'POST') setPresenceHandler();
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
}
?>
```

#### 10. **Versioning de l'API propre**

```
app/
├── v1/
│   └── flux.php (legacy - déprécié)
├── v2/
│   └── api.php (version actuelle)
└── v3/
    └── api.php (future version)
```

#### 11. **Documentation de l'API**

Créer `app/README.md`:
```markdown
# API NPVB - Documentation

## Authentification
Toutes les requêtes nécessitent un token d'authentification :
`?token=YOUR_TOKEN`

## Endpoints

### GET /app/api.php?path=members&token=XXX
Récupère la liste des membres actifs.

**Réponse:**
```json
[
  {
    "Pseudonyme": "jdoe",
    "Nom": "Doe",
    "Prenom": "John"
  }
]
```
```

#### 12. **Optimisations base de données**

```sql
-- Ajouter des index pour les requêtes fréquentes
ALTER TABLE NPVB_Evenements ADD INDEX idx_date (DateHeure);
ALTER TABLE NPVB_Presence ADD INDEX idx_joueur_date (Joueur, DateHeure);
ALTER TABLE NPVB_Appartenance ADD INDEX idx_joueur (Joueur);

-- Nettoyer les anciennes données
DELETE FROM NPVB_Evenements WHERE DateHeure < 20200000000000 AND Etat = 'E';
```

---

## 🚀 6. CONTRAINTES LIÉES À L'HÉBERGEMENT FTPerso

### Ce que vous POUVEZ faire ✅

- ✅ Utiliser PHP (probablement version 5.x ou 7.x)
- ✅ Utiliser MySQL/MariaDB
- ✅ Créer/modifier des fichiers PHP
- ✅ Utiliser `.htaccess` pour la config Apache
- ✅ Stocker des sessions PHP
- ✅ Faire des includes/requires
- ✅ Utiliser `mysqli_*` (si PHP >= 5.5)

### Ce que vous NE POUVEZ PAS faire ❌

- ❌ Installer Composer ou packages externes
- ❌ Accès SSH/terminal
- ❌ Forcer HTTPS (Free ne le propose pas)
- ❌ Utiliser des frameworks modernes (Laravel, Symfony)
- ❌ Cronjobs (peut-être avec un service externe)
- ❌ Node.js, Python, ou autres langages
- ❌ Configuration PHP avancée (php.ini)
- ❌ Certificat SSL/TLS personnalisé

### Workarounds recommandés 🔧

1. **Pas de Composer :** Créer vos propres helpers dans `helpers/`
2. **Pas de HTTPS :** Utiliser un reverse proxy Cloudflare (gratuit)
3. **Pas de cronjobs :** Utiliser un service comme cron-job.org pour appeler un endpoint
4. **Pas de framework :** Créer votre propre micro-framework simple

---

## 📊 7. PLAN D'ACTION PRIORISÉ

### Phase 1 : SÉCURITÉ (1-2 semaines) 🔴

| Tâche | Effort | Impact | Fichiers concernés |
|-------|--------|--------|-------------------|
| Déplacer credentials dans PASSWD/ | 2h | ⭐⭐⭐⭐⭐ | flux_v3.php |
| Protéger PASSWD/ avec .htaccess | 30min | ⭐⭐⭐⭐⭐ | PASSWD/.htaccess |
| Ajouter mysql_real_escape_string() | 8h | ⭐⭐⭐⭐⭐ | Tous les fichiers PHP |
| Ajouter auth à l'API | 4h | ⭐⭐⭐⭐ | flux_v3.php |
| Restreindre get_members | 2h | ⭐⭐⭐⭐ | flux_v3.php:85 |

**Total : ~16.5h**

### Phase 2 : MODERNISATION (1 mois) 🟡

| Tâche | Effort | Impact | Fichiers concernés |
|-------|--------|--------|-------------------|
| Migrer mysql_* → mysqli_* | 16h | ⭐⭐⭐⭐ | Tous |
| Ajouter logging | 4h | ⭐⭐⭐ | Nouveau fichier |
| Améliorer sessions | 2h | ⭐⭐⭐ | _entete.inc.php |
| Nettoyer code obsolète | 4h | ⭐⭐ | flux.php, etc. |

**Total : ~26h**

### Phase 3 : OPTIMISATION (2-3 mois) 🟢

| Tâche | Effort | Impact |
|-------|--------|--------|
| Créer vraie API REST | 16h | ⭐⭐⭐ |
| Documentation API | 4h | ⭐⭐ |
| Optimiser BDD | 4h | ⭐⭐⭐ |
| Tests unitaires basiques | 8h | ⭐⭐ |

**Total : ~32h**

---

## 🎓 8. RESSOURCES POUR ALLER PLUS LOIN

### Documentation
- [PHP mysqli Documentation](https://www.php.net/manual/fr/book.mysqli.php)
- [OWASP Top 10 2021](https://owasp.org/www-project-top-ten/)
- [SQL Injection Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html)

### Outils gratuits
- **Cloudflare** : Protection DDoS, cache, SSL gratuit
- **cron-job.org** : Cronjobs externes gratuits
- **GitHub** : Versioning du code
- **PHPMyAdmin** : Gestion MySQL (probablement déjà disponible chez Free)

---

## 📝 CONCLUSION

### État actuel
Le projet **fonctionne correctement** d'un point de vue fonctionnel, mais présente des **vulnérabilités critiques** qui exposent les données sensibles des membres et la base de données à des attaques.

### Priorités absolues
1. **Sécuriser les credentials** (2h)
2. **Protéger contre SQL Injection** (8h)
3. **Ajouter authentification API** (4h)
4. **Restreindre accès aux données sensibles** (2h)

**Total urgent : ~16h de travail**

### Vision à long terme
Avec les contraintes de FTPerso, le projet peut être **significativement amélioré** sans migration complète. En 1-2 mois de travail, vous pouvez avoir :
- Une application sécurisée ✅
- Une API REST propre ✅
- Un code maintenable ✅
- Des performances optimisées ✅

### Alternative future
Si le projet continue de grandir, envisager une **migration vers un hébergeur moderne** (OVH, Hostinger, DigitalOcean) qui offre :
- PHP 8.x
- SSH/Git
- Composer
- SSL/TLS gratuit
- Cronjobs

**Coût :** ~3-5€/mois pour un VPS ou hébergement mutualisé moderne

---

**Date de l'audit :** 22 janvier 2026
**Réalisé par :** Claude Sonnet 4.5
**Version du document :** 1.0
