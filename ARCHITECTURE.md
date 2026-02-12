# Architecture Technique - Site Web NPVB

**Version** : 3.0
**Date** : 2026-02-11
**Objectif** : Documentation technique détaillée de l'architecture du site web PHP

---

## Table des matières

1. [Vue d'ensemble](#1-vue-densemble)
2. [Architecture Monolithique](#2-architecture-monolithique)
3. [Structure des fichiers](#3-structure-des-fichiers)
4. [Base de données](#4-base-de-données)
5. [Sécurité](#5-sécurité)
6. [API Mobile](#6-api-mobile)
7. [Flux de données](#7-flux-de-données)
8. [Patterns utilisés](#8-patterns-utilisés)

---

## 1. Vue d'ensemble

### 1.1 Contexte Technique

Le site web NPVB est une application PHP **legacy** développée pour être compatible avec l'hébergement gratuit Free.fr.

**Contraintes :**
- ⚠️ PHP 4.4.3 (release 2006)
- ⚠️ MySQL 4.x avec `OLD_PASSWORD()`
- ⚠️ Pas d'accès SSH
- ⚠️ Pas de Composer, pas d'autoload
- ⚠️ Fonctions `mysql_*` (deprecated depuis PHP 5.5)

**Conséquences :**
- Architecture monolithique include-based
- Pas de POO avancée
- Pas de framework (pas de Laravel, Symfony, etc.)
- SQL injection protection manuelle
- Sessions PHP fichiers (pas de Redis)

### 1.2 Stack Technique

```
┌─────────────────────────────────────┐
│      Frontend (HTML/CSS/JS)         │
│  - HTML 4.01 Transitional           │
│  - CSS 2.1 (tables-based layout)    │
│  - JavaScript ES3 (libGene.js)      │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│      Backend (PHP 4.4.3)            │
│  - index.php (router)               │
│  - *.inc.php (pages)                │
│  - classes.inc.php                  │
│  - fonctions.inc.php                │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│      Database (MySQL 4.x)           │
│  - mysql_* functions                │
│  - OLD_PASSWORD() hashing           │
└─────────────────────────────────────┘
```

---

## 2. Architecture Monolithique

### 2.1 Pattern Include-Based

**Principe :** Un fichier `index.php` central inclut dynamiquement les pages selon le paramètre `?Page=`.

```php
<?php
// index.php (simplifié)
session_start();

$Page = isset($_GET['Page']) ? $_GET['Page'] : 'accueil';

// Whitelist de pages autorisées
$allowedPages = array(
    'accueil',
    'calendrier',
    'jour',
    'membres',
    'adminequipes',
    // ... etc
);

// Sécurité : vérifier que la page est autorisée
if (!in_array($Page, $allowedPages)) {
    $Page = 'Erreur404';
}

// Inclure la page
include "_entete.inc.php";  // Header + Auth
include "$Page.inc.php";     // Contenu page
?>
```

**Avantages :**
- ✅ Simple à comprendre
- ✅ Pas de routing complexe
- ✅ Facile à déboguer (pas de code généré)

**Inconvénients :**
- ❌ Pas de séparation MVC
- ❌ Code métier mélangé avec HTML
- ❌ Réutilisabilité limitée
- ❌ Tests difficiles

### 2.2 Structure Typique d'une Page

Chaque fichier `.inc.php` suit ce pattern :

```php
<?php
// 1. Vérification authentification (si nécessaire)
if (!isset($_SESSION['Pseudonyme'])) {
    header('Location: index.php?Page=accueil');
    exit;
}

// 2. Traitement POST (actions)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === 'inscription') {
        // Sanitize inputs
        $dateHeure = mysql_real_escape_string($_POST['dateHeure']);
        $presence = mysql_real_escape_string($_POST['presence']);

        // Query
        $sql = "INSERT INTO NPVB_Presence ...";
        $result = mysql_query($sql);

        // Redirect after POST
        header('Location: index.php?Page=jour&date=' . $dateHeure);
        exit;
    }
}

// 3. Récupération données GET
$dateHeure = isset($_GET['date']) ? mysql_real_escape_string($_GET['date']) : '';

// 4. Query database
$sql = "SELECT * FROM NPVB_Evenements WHERE DateHeure = '$dateHeure'";
$result = mysql_query($sql);
$event = mysql_fetch_assoc($result);

// 5. Affichage HTML
?>
<!DOCTYPE html>
<html>
<head>
    <title>Événement</title>
</head>
<body>
    <h1><?php echo htmlspecialchars($event['Titre']); ?></h1>
    <p>Date : <?php echo formatDate($event['DateHeure']); ?></p>
    <!-- ... -->
</body>
</html>
```

**Pattern typique :**
1. Vérification auth/permissions
2. Traitement POST (Create/Update/Delete)
3. Récupération GET parameters
4. Requêtes SQL
5. Affichage HTML avec PHP inline

### 2.3 Gestion d'État (Sessions)

**Authentification via Sessions PHP :**

```php
// _entete.inc.php
session_start();

// Login
if (isset($_POST['login'])) {
    $pseudo = mysql_real_escape_string($_POST['pseudo']);
    $password = mysql_real_escape_string($_POST['password']);

    $sql = "SELECT * FROM NPVB_Joueurs
            WHERE Pseudonyme = '$pseudo'
            AND Password = OLD_PASSWORD('$password')";
    $result = mysql_query($sql);

    if (mysql_num_rows($result) > 0) {
        $user = mysql_fetch_assoc($result);
        $_SESSION['Pseudonyme'] = $user['Pseudonyme'];
        $_SESSION['DieuToutPuissant'] = $user['DieuToutPuissant'];
        // ... autres champs
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
```

**Variables de session utilisées :**
- `$_SESSION['Pseudonyme']` : Username connecté
- `$_SESSION['DieuToutPuissant']` : Flag admin ('o' ou null)
- `$_SESSION['Nom']`, `$_SESSION['Prenom']` : Infos user

---

## 3. Structure des fichiers

### 3.1 Arborescence Complète

```
npvb-web/
├── index.php                       # Router principal
│
├── Core Files:
│   ├── _entete.inc.php            # Auth + Header HTML
│   ├── _connectDB.inc.php         # Connexion MySQL
│   ├── variables.inc.php          # Config globale
│   ├── fonctions.inc.php          # Fonctions utilitaires
│   └── classes.inc.php            # Classes Equipe & Evenement
│
├── User Pages:
│   ├── accueil.inc.php            # Home + Messages (13,428 lignes)
│   ├── calendrier.inc.php         # Calendrier mensuel (7,830 lignes)
│   ├── jour.inc.php               # Détails événement (37,686 lignes)
│   ├── membres.inc.php            # Annuaire membres (12,187 lignes)
│   ├── resetmotdepasse.inc.php    # Reset password (6,031 lignes)
│   └── Erreur404.inc.php          # Page 404
│
├── Admin Pages (DieuToutPuissant='o'):
│   ├── adminequipes.inc.php       # Gestion équipes (5,552 lignes)
│   ├── adminfichejour.inc.php     # CRUD événement (14,545 lignes)
│   ├── adminevenements.inc.php    # Liste événements (7,453 lignes)
│   ├── adminmembres.inc.php       # Liste membres (5,653 lignes)
│   ├── adminfichemembre.inc.php   # CRUD membre (23,777 lignes)
│   ├── adminmessages.inc.php      # Gestion messages (16,285 lignes)
│   ├── adminnewmessage.inc.php    # Créer message (11,292 lignes)
│   └── adminstats.inc.php         # Statistiques stub (445 lignes)
│
├── API Mobile:
│   └── mobile-api/v1/
│       ├── index.php              # API REST complète
│       └── README.md
│
├── Legacy API:
│   └── app/
│       ├── flux.php               # Deprecated
│       ├── flux_limite.php        # Deprecated
│       └── flux_v3.php            # Superseded by mobile-api/v1
│
├── Assets:
│   ├── Feuilles de style/         # CSS files
│   │   └── style.css
│   ├── libGene.js                 # JavaScript utils
│   ├── favicon.ico                # Site icon
│   └── PhotoJoueur/               # Photos membres
│
└── Config (NON versionné):
    └── PASSWD/
        └── _passwrds.inc.php      # DB credentials
```

**Total lignes PHP** : ~102,000 lignes (tous fichiers)

### 3.2 Fichiers Critiques

#### index.php (Router)
```php
<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

$Page = isset($_GET['Page']) ? $_GET['Page'] : 'accueil';

// Whitelist pages
$pagesUtilisateur = array('accueil', 'calendrier', 'jour', 'membres', 'resetmotdepasse');
$pagesAdmin = array('adminequipes', 'adminfichejour', ...);

// Vérifier accès admin
if (in_array($Page, $pagesAdmin)) {
    if (!isset($_SESSION['DieuToutPuissant']) || $_SESSION['DieuToutPuissant'] != 'o') {
        $Page = 'accueil';  // Redirection si pas admin
    }
}

// Sécurité : vérifier whitelist
if (!in_array($Page, array_merge($pagesUtilisateur, $pagesAdmin))) {
    $Page = 'Erreur404';
}

include "_entete.inc.php";
include "$Page.inc.php";
?>
```

#### _entete.inc.php (Auth + Header)
```php
<?php
session_start();

// Connexion DB
include "_connectDB.inc.php";

// Traiter login
if (isset($_POST['submitLogin'])) {
    $pseudo = mysql_real_escape_string($_POST['pseudo']);
    $password = mysql_real_escape_string($_POST['password']);

    $sql = "SELECT * FROM NPVB_Joueurs
            WHERE Pseudonyme = '$pseudo'
            AND Password = OLD_PASSWORD('$password')
            AND Etat = 'V'";  // Seulement membres valides

    $result = mysql_query($sql);

    if (mysql_num_rows($result) == 1) {
        $user = mysql_fetch_assoc($result);
        $_SESSION['Pseudonyme'] = $user['Pseudonyme'];
        $_SESSION['Nom'] = $user['Nom'];
        $_SESSION['Prenom'] = $user['Prenom'];
        $_SESSION['DieuToutPuissant'] = $user['DieuToutPuissant'];
        // ...
    } else {
        $errorLogin = "Identifiants incorrects";
    }
}

// Traiter logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>NPVB - Nantes Plage Volley-Ball</title>
    <link rel="stylesheet" href="Feuilles de style/style.css">
</head>
<body>
    <!-- Header HTML commun -->
    <div id="header">
        <h1>Nantes Plage Volley-Ball</h1>
        <!-- Menu navigation -->
        <ul id="menu">
            <li><a href="index.php?Page=accueil">Accueil</a></li>
            <li><a href="index.php?Page=calendrier">Calendrier</a></li>
            <li><a href="index.php?Page=membres">Membres</a></li>
            <?php if (isset($_SESSION['DieuToutPuissant']) && $_SESSION['DieuToutPuissant'] == 'o'): ?>
                <li><a href="index.php?Page=adminequipes">Admin</a></li>
            <?php endif; ?>
            <?php if (isset($_SESSION['Pseudonyme'])): ?>
                <li><a href="index.php?logout=1">Déconnexion</a></li>
            <?php endif; ?>
        </ul>
    </div>
    <!-- Contenu page sera inséré ici par include -->
```

#### _connectDB.inc.php (Database Connection)
```php
<?php
// Charger credentials (non versionné)
include "PASSWD/_passwrds.inc.php";

// Connexion MySQL
$db = mysql_connect("ftpperso.free.fr", $NOM_UTILISATEUR, $MOT_DE_PASSE);
if (!$db) {
    die("Connexion impossible : " . mysql_error());
}

// Sélectionner base
mysql_select_db("nantespvb", $db);

// Encoding UTF-8
mysql_query("SET NAMES 'utf8'");
?>
```

#### fonctions.inc.php (Utilities)
```php
<?php
/**
 * Formater date API → Affichage
 * @param string $apiDate Format YYYYMMDDHHmmss
 * @return string Format dd/MM/yyyy HH:mm
 */
function formatDate($apiDate) {
    if (strlen($apiDate) != 14) return $apiDate;

    $year = substr($apiDate, 0, 4);
    $month = substr($apiDate, 4, 2);
    $day = substr($apiDate, 6, 2);
    $hour = substr($apiDate, 8, 2);
    $minute = substr($apiDate, 10, 2);

    return "$day/$month/$year $hour:$minute";
}

/**
 * Vérifier si événement est passé
 */
function isEventPast($apiDate) {
    $eventDate = strtotime(substr($apiDate, 0, 8));  // YYYYMMDD
    return $eventDate < strtotime(date('Ymd'));
}

/**
 * Sanitize input
 */
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Vérifier si utilisateur est admin
 */
function isAdmin() {
    return isset($_SESSION['DieuToutPuissant']) && $_SESSION['DieuToutPuissant'] == 'o';
}
?>
```

#### classes.inc.php (Classes)
```php
<?php
/**
 * Classe Equipe
 */
class Equipe {
    var $Libelle;
    var $IdRespEquipe;
    var $IdRespEquipeSub;
    var $TousLesJoueurs;
    var $PresenceParDefaut;

    function Equipe($libelle) {
        $this->Libelle = $libelle;
        $this->load();
    }

    function load() {
        $sql = "SELECT * FROM NPVB_Equipes WHERE Libelle = '" . mysql_real_escape_string($this->Libelle) . "'";
        $result = mysql_query($sql);
        if ($row = mysql_fetch_assoc($result)) {
            $this->IdRespEquipe = $row['IdRespEquipe'];
            $this->IdRespEquipeSub = $row['IdRespEquipeSub'];
            $this->TousLesJoueurs = $row['TousLesJoueurs'];
            $this->PresenceParDefaut = $row['PresenceParDefaut'];
        }
    }
}

/**
 * Classe Evenement
 */
class Evenement {
    var $DateHeure;
    var $Libelle;
    var $Titre;
    var $Lieu;
    var $Adversaire;
    var $Resultat;
    var $Etat;
    var $InscritsMax;

    function Evenement($dateHeure, $libelle) {
        $this->DateHeure = $dateHeure;
        $this->Libelle = $libelle;
        $this->load();
    }

    function load() {
        $sql = "SELECT * FROM NPVB_Evenements
                WHERE DateHeure = '" . mysql_real_escape_string($this->DateHeure) . "'
                AND Libelle = '" . mysql_real_escape_string($this->Libelle) . "'";
        $result = mysql_query($sql);
        if ($row = mysql_fetch_assoc($result)) {
            $this->Titre = $row['Titre'];
            $this->Lieu = $row['Lieu'];
            $this->Adversaire = $row['Adversaire'];
            $this->Resultat = $row['Resultat'];
            $this->Etat = $row['Etat'];
            $this->InscritsMax = $row['InscritsMax'];
        }
    }

    function save() {
        $sql = "UPDATE NPVB_Evenements SET
                Titre = '" . mysql_real_escape_string($this->Titre) . "',
                Lieu = '" . mysql_real_escape_string($this->Lieu) . "',
                Adversaire = '" . mysql_real_escape_string($this->Adversaire) . "',
                Resultat = '" . mysql_real_escape_string($this->Resultat) . "',
                Etat = '" . mysql_real_escape_string($this->Etat) . "',
                InscritsMax = " . intval($this->InscritsMax) . "
                WHERE DateHeure = '" . mysql_real_escape_string($this->DateHeure) . "'
                AND Libelle = '" . mysql_real_escape_string($this->Libelle) . "'";
        return mysql_query($sql);
    }
}
?>
```

---

## 4. Base de données

### 4.1 Schéma ER (simplifié)

```
┌─────────────────┐
│  NPVB_Joueurs   │
│  (Membres)      │
├─────────────────┤
│ Pseudonyme PK   │
│ Password        │
│ Nom             │
│ Prenom          │
│ Email           │
│ DieuToutPuissant│ (Admin flag)
│ Etat            │ (V/I/E)
│ ...             │
└────────┬────────┘
         │
         │ 1:N
         │
┌────────▼────────────────┐
│  NPVB_Appartenance      │
│  (Team Membership)      │
├─────────────────────────┤
│ Joueur PK, FK           │
│ Equipe PK, FK           │
└────────┬────────────────┘
         │
         │ N:1
         │
┌────────▼────────┐
│  NPVB_Equipes   │
│  (Teams)        │
├─────────────────┤
│ Libelle PK      │
│ IdRespEquipe    │
│ TousLesJoueurs  │
│ ...             │
└─────────────────┘

┌─────────────────────┐
│  NPVB_Evenements    │
│  (Events)           │
├─────────────────────┤
│ DateHeure PK        │
│ Libelle PK          │ (Team name)
│ Titre               │
│ Lieu                │
│ Adversaire          │
│ Resultat            │
│ Etat                │ (I/O/F/T/A)
│ InscritsMax         │
│ ...                 │
└──────────┬──────────┘
           │
           │ 1:N
           │
┌──────────▼──────────────┐
│  NPVB_Presence          │
│  (Attendance)           │
├─────────────────────────┤
│ Joueur PK, FK           │
│ DateHeure PK, FK        │
│ Libelle PK, FK          │
│ Prevue                  │ (o/!/n)
│ Effective               │ (o/!/n)
│ Journee                 │
└─────────────────────────┘

┌─────────────────────┐
│  NPVB_Messages      │
│  (Home Messages)    │
├─────────────────────┤
│ Id PK               │
│ Titre               │
│ Texte               │
│ DateCreation        │
│ Actif               │
└─────────────────────┘

┌─────────────────────┐
│  NPVB_PasswordReset │
│  (Reset Tokens)     │
├─────────────────────┤
│ Pseudonyme PK, FK   │
│ Token               │
│ DateExpiration      │
│ DateCreation        │
└─────────────────────┘
```

### 4.2 Requêtes Typiques

#### SELECT Événements du mois
```sql
SELECT *
FROM NPVB_Evenements
WHERE DateHeure >= '20260201000000'
  AND DateHeure < '20260301000000'
ORDER BY DateHeure ASC
```

#### INSERT Présence
```sql
INSERT INTO NPVB_Presence (Joueur, DateHeure, Libelle, Prevue, Journee)
VALUES ('JohnDoe', '20260215200000', 'NPVB_L', 'o', NOW())
ON DUPLICATE KEY UPDATE
    Prevue = 'o',
    Journee = NOW()
```

#### SELECT Présences Événement
```sql
SELECT p.*, j.Nom, j.Prenom
FROM NPVB_Presence p
INNER JOIN NPVB_Joueurs j ON p.Joueur = j.Pseudonyme
WHERE p.DateHeure = '20260215200000'
  AND p.Libelle = 'NPVB_L'
ORDER BY p.Prevue DESC, j.Nom ASC
```

#### UPDATE Événement État
```sql
UPDATE NPVB_Evenements
SET Etat = 'F'  -- Fermé
WHERE DateHeure = '20260215200000'
  AND Libelle = 'NPVB_L'
```

### 4.3 Indexes

**Indexes existants (optimisation) :**
```sql
-- NPVB_Joueurs
PRIMARY KEY (Pseudonyme)
INDEX idx_email (Email)
INDEX idx_etat (Etat)

-- NPVB_Evenements
PRIMARY KEY (DateHeure, Libelle)
INDEX idx_date (DateHeure)
INDEX idx_etat (Etat)

-- NPVB_Presence
PRIMARY KEY (Joueur, DateHeure, Libelle)
INDEX idx_event (DateHeure, Libelle)
INDEX idx_joueur (Joueur)

-- NPVB_Appartenance
PRIMARY KEY (Joueur, Equipe)
INDEX idx_equipe (Equipe)
```

---

## 5. Sécurité

### 5.1 Protection SQL Injection

**Méthode utilisée : `mysql_real_escape_string()`**

```php
// ❌ VULNÉRABLE
$sql = "SELECT * FROM NPVB_Joueurs WHERE Pseudonyme = '$_POST[pseudo]'";

// ✅ SÉCURISÉ
$pseudo = mysql_real_escape_string($_POST['pseudo']);
$sql = "SELECT * FROM NPVB_Joueurs WHERE Pseudonyme = '$pseudo'";
```

**Limitations :**
- ⚠️ Protection basique uniquement
- ⚠️ Pas de prepared statements (pas supporté en PHP 4 mysql_*)
- ⚠️ Nécessite vigilance du développeur (oubli = vulnérabilité)

### 5.2 Protection XSS

**Méthode utilisée : `htmlspecialchars()`**

```php
// ❌ VULNÉRABLE
echo "<p>Bonjour " . $_SESSION['Nom'] . "</p>";

// ✅ SÉCURISÉ
echo "<p>Bonjour " . htmlspecialchars($_SESSION['Nom']) . "</p>";
```

### 5.3 Authentification

**Password Hashing : `OLD_PASSWORD()`**

```sql
-- Login
SELECT * FROM NPVB_Joueurs
WHERE Pseudonyme = 'JohnDoe'
  AND Password = OLD_PASSWORD('motdepasse')
```

**Limitations :**
- ⚠️ OLD_PASSWORD() est un hash MD5 très faible
- ⚠️ Pas de salt
- ⚠️ Vulnérable aux rainbow tables
- ⚠️ Deprecated depuis MySQL 5.7

**Migration recommandée :**
```php
// PHP moderne (7.4+)
$hash = password_hash($password, PASSWORD_BCRYPT);
$valid = password_verify($password, $hash);
```

### 5.4 CSRF Protection

⚠️ **NON IMPLÉMENTÉ**

**Recommandation :**
```php
// Générer token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Vérifier token
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("CSRF token invalid");
}
```

### 5.5 Rate Limiting

✅ **Implémenté pour reset password uniquement**

```php
// resetmotdepasse.inc.php
$sql = "SELECT COUNT(*) as count FROM NPVB_PasswordReset
        WHERE Pseudonyme = '$pseudo'
        AND DateCreation > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
$result = mysql_query($sql);
$row = mysql_fetch_assoc($result);

if ($row['count'] >= 3) {
    die("Trop de tentatives. Réessayez dans 1 heure.");
}
```

---

## 6. API Mobile

### 6.1 Architecture API v1

**Fichier unique :** `/mobile-api/v1/index.php`

**Pattern :**
```php
<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');  // CORS

$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';

switch($endpoint) {
    case 'auth/login':
        handleLogin();
        break;

    case 'events':
        handleGetEvents();
        break;

    case 'presences':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            handleUpdatePresence();
        } else {
            handleGetPresences();
        }
        break;

    default:
        sendError(404, "Endpoint not found");
}

function handleLogin() {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $username = mysql_real_escape_string($data['username']);
    $password = mysql_real_escape_string($data['password']);

    $sql = "SELECT * FROM NPVB_Joueurs
            WHERE Pseudonyme = '$username'
            AND Password = OLD_PASSWORD('$password')
            AND Etat = 'V'";

    $result = mysql_query($sql);

    if (mysql_num_rows($result) == 1) {
        $user = mysql_fetch_assoc($result);
        $token = md5($username . time());

        sendSuccess(array(
            'token' => $token,
            'user' => array(
                'Pseudonyme' => $user['Pseudonyme'],
                'Nom' => $user['Nom'],
                'Prenom' => $user['Prenom'],
                'isAdmin' => $user['DieuToutPuissant'] == 'o'
            )
        ));
    } else {
        sendError(401, "Invalid credentials");
    }
}

function sendSuccess($data) {
    echo json_encode(array(
        'success' => true,
        'data' => $data
    ));
    exit;
}

function sendError($code, $message) {
    http_response_code($code);
    echo json_encode(array(
        'success' => false,
        'error' => array(
            'code' => $code,
            'message' => $message
        )
    ));
    exit;
}
?>
```

**Format Réponse Standardisé :**
```json
{
    "success": true,
    "data": { ... }
}
```

ou

```json
{
    "success": false,
    "error": {
        "code": 404,
        "message": "Resource not found"
    }
}
```

### 6.2 Endpoints Détaillés

Voir [mobile-api/README.md](mobile-api/README.md) pour liste complète.

---

## 7. Flux de données

### 7.1 Inscription à un Événement

```
User (Browser)
    │
    │ POST jour.inc.php
    │ {action: "inscription", dateHeure: "...", presence: "o"}
    ▼
jour.inc.php
    │
    │ 1. Sanitize inputs
    │ 2. Vérifier délai (3j avant)
    │ 3. Vérifier capacité (SEANCE)
    │ 4. Vérifier appartenance équipe
    ▼
MySQL Query
    │ INSERT INTO NPVB_Presence ...
    │ ON DUPLICATE KEY UPDATE Prevue = 'o'
    ▼
Redirect
    │ header('Location: index.php?Page=jour&date=...')
    ▼
jour.inc.php (refresh)
    │
    │ SELECT présences
    ▼
Affichage HTML
    │ Liste présences mise à jour
```

### 7.2 Création Événement (Admin)

```
Admin (Browser)
    │
    │ POST adminfichejour.inc.php
    │ {action: "create", dateHeure: "...", titre: "..."}
    ▼
adminfichejour.inc.php
    │
    │ 1. Vérifier DieuToutPuissant='o'
    │ 2. Sanitize inputs
    │ 3. Valider format date
    ▼
MySQL Query
    │ INSERT INTO NPVB_Evenements (DateHeure, Libelle, Titre, ...)
    │ VALUES ('20260215200000', 'NPVB_L', 'Match vs Team', ...)
    ▼
Redirect
    │ header('Location: index.php?Page=adminevenements')
    ▼
adminevenements.inc.php
    │ Affiche liste événements (incluant nouveau)
```

---

## 8. Patterns utilisés

### 8.1 Page Controller Pattern

Chaque page `.inc.php` agit comme un contrôleur autonome :

- Gère ses propres POST/GET
- Effectue ses requêtes SQL
- Génère son propre HTML

**Avantage :** Simplicité, compréhensible
**Inconvénient :** Code dupliqué, pas de réutilisation

### 8.2 Include Pattern

**Avantage :** Header/Footer communs
**Inconvénient :** Variables globales partagées (risque de conflits)

### 8.3 Whitelist Router

**Avantage :** Sécurisé contre path traversal
**Inconvénient :** Ajout manuel de chaque nouvelle page

### 8.4 POST-Redirect-GET (PRG)

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Traiter action
    // ...
    header('Location: index.php?Page=...');
    exit;
}
```

**Avantage :** Évite double-submit lors de F5

---

## 9. Limitations & Améliorations

### 9.1 Limitations Actuelles

#### Technique
- ⚠️ PHP 4.4.3 (EOL depuis 2008)
- ⚠️ mysql_* functions (deprecated PHP 5.5)
- ⚠️ Pas de POO avancée
- ⚠️ Pas de namespaces
- ⚠️ Pas de Composer
- ⚠️ Pas d'autoload

#### Sécurité
- ⚠️ OLD_PASSWORD() faible
- ⚠️ Pas de CSRF protection
- ⚠️ HTTP seulement (pas HTTPS)
- ⚠️ Sessions fichiers (pas sécurisées)

#### Architecture
- ⚠️ Pas de séparation MVC
- ⚠️ Code dupliqué entre pages
- ⚠️ Pas de tests automatisés
- ⚠️ HTML mélangé avec logique

### 9.2 Améliorations Proposées

#### Court Terme (Quick Wins)
1. ✅ **Migration PDO** : Remplacer mysql_* par PDO
2. ✅ **Prepared Statements** : Sécurité SQL
3. ✅ **CSRF Tokens** : Protection formulaires
4. ✅ **Password Hashing** : password_hash() PHP

#### Moyen Terme (Refactoring)
1. ⚠️ **Extraction logique** : Séparer HTML et PHP
2. ⚠️ **Classes Repository** : Abstraire accès DB
3. ⚠️ **Template Engine** : Twig ou Plates
4. ⚠️ **Validation centralisée** : Classe Validator

#### Long Terme (Modernisation)
1. ❌ **Migration PHP 8** : Changement hébergeur
2. ❌ **Framework Laravel/Symfony** : Refonte complète
3. ❌ **HTTPS** : Certificat SSL
4. ❌ **Tests PHPUnit** : Tests automatisés
5. ❌ **CI/CD** : GitHub Actions

---

## Conclusion

Le site web NPVB est une application **legacy** fonctionnelle mais techniquement datée. Elle remplit son rôle mais nécessiterait une modernisation pour :

- ✅ Améliorer la sécurité (HTTPS, password_hash, CSRF)
- ✅ Faciliter la maintenance (MVC, tests)
- ✅ Améliorer l'UX (responsive, AJAX, dark mode)

**Priorités :**
1. Court terme : Sécurité (PDO, CSRF, password_hash)
2. Moyen terme : UX (recherche, responsive)
3. Long terme : Migration framework moderne (si budget)

---

**Document maintenu par** : Équipe Dev NPVB
**Dernière mise à jour** : 2026-02-11
