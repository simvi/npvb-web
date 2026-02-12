# NPVB Site Web - Documentation

Site web PHP pour le club de volley-ball Nantes PVB.

## 📋 Informations

- **Version** : 3.0
- **Langage** : PHP 4.4.3
- **Hébergement** : Free.fr
- **Base de données** : MySQL 4.x (OLD_PASSWORD)
- **Architecture** : Monolithique Include-based

## 🏗️ Structure

### Architecture du site

```
npvb-web/
├── index.php                    # Point d'entrée principal (router)
├── _entete.inc.php             # Authentification & sessions
├── _connectDB.inc.php          # Connexion base de données
├── variables.inc.php           # Configuration & variables globales
├── fonctions.inc.php           # Fonctions utilitaires
├── classes.inc.php             # Classes Equipe & Evenement
│
├── Pages Utilisateur:
│   ├── accueil.inc.php         # Page d'accueil + messages
│   ├── calendrier.inc.php      # Vue calendrier événements
│   ├── jour.inc.php            # Détails jour + inscriptions
│   ├── membres.inc.php         # Annuaire membres + profil
│   └── resetmotdepasse.inc.php # Réinitialisation mot de passe
│
├── Pages Admin (DieuToutPuissant='o'):
│   ├── adminequipes.inc.php    # Gestion équipes
│   ├── adminfichejour.inc.php  # Gestion événement unique
│   ├── adminevenements.inc.php # Liste événements
│   ├── adminmembres.inc.php    # Liste membres
│   ├── adminfichemembre.inc.php # Gestion membre unique
│   ├── adminmessages.inc.php   # Liste messages accueil
│   ├── adminnewmessage.inc.php # Créer message
│   └── adminstats.inc.php      # Statistiques (stub)
│
├── mobile-api/                  # API REST pour apps mobiles
│   └── v1/
│       ├── index.php           # API v1 complète
│       └── README.md           # Documentation API
│
├── app/                         # Legacy API (deprecated)
│   ├── flux.php
│   ├── flux_limite.php
│   └── flux_v3.php
│
├── Feuilles de style/          # CSS
├── libGene.js                  # JavaScript utilitaires
└── PASSWD/                     # Credentials (non versionné)
    └── _passwrds.inc.php
```

## 🎯 Fonctionnalités

### 👤 Espace Utilisateur

#### 1. Authentification
- **Login/Logout** : Session PHP avec cookies
- **Auto-login** : Option "Se souvenir de moi"
- **Réinitialisation mot de passe** :
  - Token sécurisé (24h validité)
  - Envoi par email
  - Anti-spam (3 requêtes/heure max)

#### 2. Page d'Accueil
- **Messages importants** :
  - Affichage paginé (2 messages/page)
  - Créés par admin
  - Champs : Titre, Texte, Date création
- **Anniversaires** : Liste membres dont c'est l'anniversaire
- **Présentation club** : Pour visiteurs non connectés
- **Liens externes** : Règlement, Résultats, Contact

#### 3. Calendrier
- **Vue mensuelle** : Tous les événements du mois
- **Navigation** : Mois précédent/suivant
- **Couleurs par type** :
  - 🟢 Vert : Matchs NPVB
  - 🔵 Bleu : Entraînements (SEANCE)
  - 🟡 Jaune : Tournois
  - 🔴 Rouge : Annulés (Etat='A')
- **Filtrage** : Par équipe (NPVB_L, NPVB_U, NPVB_F, SEANCE)

#### 4. Jour / Événement
- **Détails événement** :
  - Date, Heure, Lieu, Adversaire
  - Adresse complète (Rue, CP, Ville)
  - Commentaire
  - Capacité max (InscritsMax)
  - État : Initialisé, Ouvert, Fermé, Terminé, Annulé
- **Inscription présence** :
  - Boutons : Présent / Absent
  - Délai : 3 jours avant événement
  - Contrôle capacité pour SEANCE
  - Vérification appartenance équipe
- **Liste présences** :
  - Prévues (o/!/n)
  - Effectives (après événement)
  - Tri par statut
- **Résultat match** :
  - Saisie par admin
  - Affichage public après fermeture

#### 5. Annuaire Membres
- **Liste membres** :
  - Trombinoscope avec photos
  - Nom, Prénom, Équipes
  - Filtre par équipe
  - Exclusion membres invités
- **Profil personnel** :
  - Modification informations perso
  - Upload photo
  - Changement mot de passe
  - Gestion consentement contact (Email, Tél visible)
- **Profil public** :
  - Photo, Nom, Prénom, Pseudo
  - Équipes
  - Contact (si consentement)
  - Date naissance (si consentement)
  - Profession (si consentement)

### 🔐 Espace Administration

**Accès** : Champ `DieuToutPuissant='o'` dans table `NPVB_Joueurs`

#### 1. Gestion Équipes (adminequipes.inc.php)
- **CRUD équipes** :
  - Créer/modifier/supprimer équipe
  - Nom équipe (NPVB_L, NPVB_U, NPVB_F, SEANCE, etc.)
- **Composition équipe** :
  - Ajouter/retirer joueurs
  - Désigner responsable (IdRespEquipe)
  - Désigner remplaçant responsable (IdRespEquipeSub)
- **Options** :
  - "Tous les joueurs" : Tous membres peuvent s'inscrire
  - "Présence par défaut" : Inscription automatique

#### 2. Gestion Événements (adminfichejour.inc.php)
- **Créer événement** :
  - Date & Heure
  - Type (MATCH, SEANCE, TOURNOI)
  - Titre
  - Lieu (court) + Adresse complète
  - Adversaire (pour matchs)
  - Capacité max (pour SEANCE)
  - État initial : Initialisé (I)
  - Commentaire
- **Modifier événement** :
  - Tous les champs modifiables
  - Changement d'état : I → O → F → T
  - O (Ouvert) : Inscriptions possibles
  - F (Fermé) : Inscriptions closes
  - T (Terminé) : Événement passé
  - A (Annulé) : Événement annulé
- **Supprimer événement** :
  - Suppression événement + présences associées
  - Confirmation obligatoire

#### 3. Gestion Membres (adminfichemembre.inc.php)
- **Créer membre** :
  - Tous champs identité
  - Génération mot de passe initial
  - Attribution équipes
- **Modifier membre** :
  - Informations personnelles
  - État compte (V/I/E) : Valide, Invalide, Expiré
  - Droits admin (DieuToutPuissant)
  - Équipes (appartenances)
  - Date adhésion, numéro licence
- **Actions spéciales** :
  - Générer token reset mot de passe
  - Supprimer présences futures
  - Désactiver compte

#### 4. Gestion Messages (adminmessages.inc.php)
- **Créer message** :
  - Titre
  - Texte (HTML simple supporté)
  - État : Actif/Inactif
- **Modifier/Supprimer** : Messages existants
- **Ordre** : Tri par date création DESC

#### 5. Statistiques (adminstats.inc.php)
- **Stub** : Fonctionnalité à développer
- **Idées** :
  - Taux présence par membre
  - Présence par événement
  - Statistiques équipes

## 🗄️ Base de Données

### Configuration

- **Host** : `ftpperso.free.fr`
- **Database** : `nantespvb`
- **User** : `nantespvb`
- **Password** : Stocké dans `/PASSWD/_passwrds.inc.php`
- **Connexion** : `mysql_*` functions (PHP 4 compatible)
- **Encoding** : UTF-8

### Tables Principales

#### NPVB_Joueurs (Membres)
```sql
Pseudonyme          VARCHAR(50)  PRIMARY KEY
Password            VARCHAR(50)  -- OLD_PASSWORD MySQL
Nom                 VARCHAR(50)
Prenom              VARCHAR(50)
Email               VARCHAR(100)
DateNaissance       DATE
TelMobile           VARCHAR(20)
TelFixe             VARCHAR(20)
Adresse             VARCHAR(255)
CP                  VARCHAR(10)
Ville               VARCHAR(50)
Profession          VARCHAR(100)
Etat                CHAR(1)      -- V/I/E (Valide, Invalide, Expiré)
DieuToutPuissant    CHAR(1)      -- 'o' = Admin
Accord              CHAR(1)      -- 'o' = Consentement contact
Adhesion            DATE         -- Date adhésion
License             VARCHAR(50)  -- Numéro licence
PhotoJoueur         VARCHAR(255) -- Nom fichier photo
```

#### NPVB_Equipes (Équipes)
```sql
Libelle             VARCHAR(50)  PRIMARY KEY
IdRespEquipe        VARCHAR(50)  FOREIGN KEY → NPVB_Joueurs
IdRespEquipeSub     VARCHAR(50)  FOREIGN KEY → NPVB_Joueurs
TousLesJoueurs      CHAR(1)      -- 'o' = Tous peuvent s'inscrire
PresenceParDefaut   CHAR(1)      -- 'o' = Inscription auto
```

#### NPVB_Appartenance (Membership)
```sql
Joueur              VARCHAR(50)  FOREIGN KEY → NPVB_Joueurs
Equipe              VARCHAR(50)  FOREIGN KEY → NPVB_Equipes
PRIMARY KEY (Joueur, Equipe)
```

#### NPVB_Evenements (Événements)
```sql
DateHeure           CHAR(14)     PRIMARY KEY (Format: YYYYMMDDHHmmss)
Libelle             VARCHAR(50)  PRIMARY KEY (Équipe: NPVB_L, SEANCE, etc.)
Titre               VARCHAR(255) -- Description événement
Lieu                VARCHAR(100) -- Lieu court
Adresse             VARCHAR(255) -- Adresse complète
CP                  VARCHAR(10)
Ville               VARCHAR(50)
Adversaire          VARCHAR(100) -- Pour matchs
Resultat            VARCHAR(50)  -- Score final
Etat                CHAR(1)      -- I/O/F/T/A
InscritsMax         INT          -- Capacité max (SEANCE)
Commentaire         TEXT
PRIMARY KEY (DateHeure, Libelle)
```

#### NPVB_Presence (Présences)
```sql
Joueur              VARCHAR(50)  FOREIGN KEY → NPVB_Joueurs
DateHeure           CHAR(14)     FOREIGN KEY → NPVB_Evenements
Libelle             VARCHAR(50)  FOREIGN KEY → NPVB_Evenements
Prevue              CHAR(1)      -- 'o' = Présent, '!' = Absent, 'n' = Inconnu
Effective           CHAR(1)      -- Présence réelle (après événement)
Journee             DATE         -- Date inscription
PRIMARY KEY (Joueur, DateHeure, Libelle)
```

#### NPVB_Messages (Messages Accueil)
```sql
Id                  INT          PRIMARY KEY AUTO_INCREMENT
Titre               VARCHAR(255)
Texte               TEXT
DateCreation        DATETIME
Actif               CHAR(1)      -- 'o' = Affiché
```

#### NPVB_PasswordReset (Reset Tokens)
```sql
Pseudonyme          VARCHAR(50)  FOREIGN KEY → NPVB_Joueurs
Token               VARCHAR(64)  -- Token unique
DateExpiration      DATETIME     -- Validité 24h
DateCreation        DATETIME
PRIMARY KEY (Pseudonyme)
```

### États & Codes

#### États Événement (Etat)
- **I** : Initialisé (créé, pas encore ouvert)
- **O** : Ouvert (inscriptions possibles)
- **F** : Fermé (inscriptions closes)
- **T** : Terminé (événement passé + résultat saisi)
- **A** : Annulé (événement annulé)

#### États Membre (Etat)
- **V** : Valide (compte actif)
- **I** : Invalide (compte désactivé)
- **E** : Expiré (adhésion expirée)

#### Statuts Présence (Prevue/Effective)
- **o** : Présent
- **!** : Absent
- **n** : Inconnu / Non défini

#### Flags Booléens
- **o** : Oui (true)
- **NULL** ou autre : Non (false)

## 🌐 API Mobile

### Documentation complète
Voir [mobile-api/README.md](mobile-api/README.md)

### Endpoints principaux

#### Authentification
```
POST /mobile-api/v1/index.php?endpoint=auth/login
Body: {"username":"pseudo","password":"motdepasse"}
Response: {"success":true,"data":{"token":"...","user":{...}}}
```

#### Membres
```
GET /mobile-api/v1/index.php?endpoint=members
GET /mobile-api/v1/index.php?endpoint=members/{username}
GET /mobile-api/v1/index.php?endpoint=memberships
```

#### Événements
```
GET /mobile-api/v1/index.php?endpoint=events
GET /mobile-api/v1/index.php?endpoint=events/{dateHeure}/{libelle}
GET /mobile-api/v1/index.php?endpoint=events/{dateHeure}/presences?libelle=TEAM
```

#### Présences
```
POST /mobile-api/v1/index.php?endpoint=presences
Body: {"dateHeure":"20250125200000","joueur":"pseudo","libelle":"TEAM","presence":"o"}
```

#### Ressources
```
GET /mobile-api/v1/index.php?endpoint=resources/rules
GET /mobile-api/v1/index.php?endpoint=resources/competlib
GET /mobile-api/v1/index.php?endpoint=resources/ufolep
```

### Format Réponses
```json
{
    "success": true,
    "data": { ... },
    "error": {
        "code": "ERROR_CODE",
        "message": "Description erreur"
    }
}
```

## 🔒 Sécurité

### Authentification
- **Sessions PHP** : `session_*` functions
- **Cookies** : Pour auto-login (Pseudonyme chiffré)
- **Password Hashing** : `OLD_PASSWORD()` MySQL (legacy, à migrer)
- **Token Reset** : Généré avec `md5(uniqid(rand(), true))`

### Protection
- **SQL Injection** : `mysql_real_escape_string()` sur tous inputs
- **XSS** : `htmlspecialchars()` sur affichage
- **CSRF** : À implémenter (TODO)
- **Rate Limiting** : 3 reset password/heure
- **Whitelist Pages** : Router vérifie page valide

### Limitations
⚠️ **PHP 4.4.3** : Pas de PDO, pas de `password_hash()`
⚠️ **HTTP** : Pas de HTTPS (limitation Free.fr)
⚠️ **OLD_PASSWORD** : Hashing faible (à migrer vers bcrypt)

## 📐 Design

### CSS
- **Feuilles de style/** : Styles principaux
- **Layout** : Table-based (legacy)
- **Responsive** : Limité (à améliorer)

### JavaScript
- **libGene.js** : Fonctions utilitaires
- **Validation formulaires** : Client-side basique
- **AJAX** : Minimal

## 🚀 Déploiement

### Prérequis Free.fr
- PHP 4.4.3
- MySQL 4.x
- Pas d'accès SSH
- Upload via FTP uniquement

### Structure FTP
```
/
├── index.php
├── *.inc.php
├── mobile-api/
├── app/
├── Feuilles de style/
├── PhotoJoueur/          # Photos membres
└── PASSWD/
    └── _passwrds.inc.php # À créer manuellement
```

### Configuration

**1. Créer /PASSWD/_passwrds.inc.php :**
```php
<?php
$NOM_UTILISATEUR = "nantespvb";
$MOT_DE_PASSE = "VOTRE_MOT_DE_PASSE";
?>
```

**2. Définir permissions :**
```
chmod 644 *.php
chmod 644 *.inc.php
chmod 755 PhotoJoueur/
```

**3. Tester :**
- Page d'accueil : `http://nantespvb.free.fr/`
- API : `http://nantespvb.free.fr/mobile-api/v1/`

## 🧪 Tests

### Tests Manuels
- [ ] Login/Logout
- [ ] Inscription événement
- [ ] Modification profil
- [ ] Upload photo
- [ ] Reset mot de passe
- [ ] Admin : Création événement
- [ ] Admin : Modification membre
- [ ] API : Tous endpoints

### Tests Unitaires
⚠️ **Non implémentés** : PHP 4 n'a pas de framework de test moderne

## 🔧 Maintenance

### Logs
- **PHP Errors** : `/logs/error.log` (si configuré)
- **Apache Logs** : Accès limité sur Free.fr
- **Debug** : `echo` statements (legacy)

### Backup
- **Base de données** : Export MySQL régulier
- **Fichiers** : Backup FTP complet
- **Photos** : `/PhotoJoueur/` à sauvegarder

### Monitoring
⚠️ **Pas d'outils** : Free.fr ne fournit pas de monitoring avancé

## 📝 TODO & Améliorations

### Sécurité
- [ ] Migrer vers `password_hash()` PHP moderne
- [ ] Implémenter CSRF tokens
- [ ] Migration vers HTTPS (changement hébergeur)
- [ ] Rate limiting global (pas que reset password)

### Features
- [ ] AJAX pour inscriptions (pas de refresh page)
- [ ] Statistiques complètes (adminstats.inc.php)
- [ ] Export CSV membres/événements
- [ ] Import CSV événements en masse
- [ ] Upload fichiers attachés (relevés FNP)
- [ ] Notifications email (nouvel événement, rappel)
- [ ] Historique modifications (audit log)

### UX/UI
- [ ] Refonte design moderne
- [ ] Responsive mobile-friendly
- [ ] Dark mode
- [ ] Pagination événements
- [ ] Recherche avancée

### Technique
- [ ] Migration PHP 8
- [ ] Migration vers PDO/MySQLi
- [ ] Framework moderne (Laravel, Symfony)
- [ ] Tests automatisés
- [ ] CI/CD pipeline
- [ ] Docker pour dev local

## 📚 Documentation Complémentaire

- [API Mobile v1](mobile-api/README.md)
- [Schéma Tables](schemaTables.md.rtf) (si existe)

## 🤝 Contributeurs

Site legacy maintenu depuis 2015+. Architecture monolithique PHP 4.

## 📜 Licence

© 2026 Nantes Plage Volley-Ball
