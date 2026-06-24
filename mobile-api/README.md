# API Mobile NPVB v1

API REST pour applications mobiles iOS et Android.

## 📁 Structure

```
mobile-api/
├── v1/
│   └── index.php    # API complète (PHP 4.4.3 compatible)
└── README.md
```

## 🚀 Déploiement

1. **Uploader** le dossier `mobile-api/` à la racine de Free
2. **Tester** : `http://nantespvb.free.fr/mobile-api/v1/index.php`

## 📡 Endpoints

### Authentification
```
POST /mobile-api/v1/index.php?endpoint=auth/login
Body: {"username":"pseudo","password":"motdepasse"}
Retour: {"success":true,"data":{"token":"...", "user":{...}}}
```

### Membres
```
GET /mobile-api/v1/index.php?endpoint=members
GET /mobile-api/v1/index.php?endpoint=members/{username}
GET /mobile-api/v1/index.php?endpoint=members/{username}/presences?status=o
GET /mobile-api/v1/index.php?endpoint=memberships
```

### Événements
```
GET /mobile-api/v1/index.php?endpoint=events
GET /mobile-api/v1/index.php?endpoint=events/{dateHeure}/{libelle}
GET /mobile-api/v1/index.php?endpoint=events/{dateHeure}/presences
```
Note : `endpoint=events` est filtré (DateHeure > 2019) pour rester léger
(calendrier/accueil). Pour l'historique complet des résultats, voir ci-dessous.

### Résultats
```
GET /mobile-api/v1/index.php?endpoint=results
```
Renvoie tous les matchs ayant un résultat (`Resultat <> ''`, hors ASSO/SEANCE),
sans filtre de date, triés du plus récent au plus ancien. Mêmes colonnes que
`events` (le champ `Resultat` est la chaîne encodée sur 22 caractères).

### Présences
```
POST /mobile-api/v1/index.php?endpoint=presences
Body: {"dateHeure":"20250125200000","joueur":"pseudo","libelle":"MATCH","presence":"o"}
Valeurs presence: "o" (présent), "n" (désinscription), "!" (absent), "w" (liste d'attente)
```
Comportement liste d'attente :
- `o` sur un événement **complet** → `success:false, error.code:"EVENT_FULL"` (aucun
  effet). L'app rafraîchit l'événement puis propose de rejoindre la liste d'attente.
- `w` → rejoint la liste d'attente (action explicite) →
  `data:{status:"waitlisted","position":N}`. Idempotent.
- `n` → désinscription : retire de la présence **et** de la liste d'attente, et
  promeut automatiquement le premier en attente s'il reste une place.

### Liste d'attente (statut)
```
GET /mobile-api/v1/index.php?endpoint=events/{dateHeure}/{libelle}/waitlist?username=XXX
→ {"success":true,"data":{"count":N,"onWaitlist":bool,"position":P}}
```
Permet de restaurer l'état « en liste d'attente (position P) » à l'ouverture d'un
événement. `position` vaut 0 si l'utilisateur n'est pas dans la file.

### Ressources
```
GET /mobile-api/v1/index.php?endpoint=resources/rules
GET /mobile-api/v1/index.php?endpoint=resources/competlib
GET /mobile-api/v1/index.php?endpoint=resources/ufolep
```

## 🔧 Configuration

Éditer `/mobile-api/v1/index.php` ligne 70 :
```php
$TOKEN_SECRET = 'CHANGEZ_MOI_EN_PRODUCTION';
```

## ✅ Compatibilité

- ✅ PHP 4.4.3 (Free.fr)
- ✅ mysql_* functions (pas MySQLi)
- ✅ json_encode personnalisé
- ✅ Token MD5 simple
- ✅ OLD_PASSWORD MySQL

## 📱 Migration apps mobiles

Remplacer les URLs :
- Ancien : `http://nantespvb.free.fr/app/flux_v3.php?type=...`
- Nouveau : `http://nantespvb.free.fr/mobile-api/v1/index.php?endpoint=...`

Adapter le format des réponses :
- Toutes les réponses sont wrappées dans `{"success": true, "data": ...}`
- Login retourne un token au lieu de juste le pseudonyme
