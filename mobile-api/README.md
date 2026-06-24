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
Body: {"dateHeure":"20250125200000","joueur":"pseudo","libelle":"MATCH","statut":"inscrit"}
```
Le client envoie un **statut cible** ; le serveur en déduit l'opération et renvoie
toujours le **statut résultant**.

Statuts demandés : `inscrit` | `indisponible` | `absent_reponse` | `liste_attente`
```
Réponse : {"success":true,"data":{"statut":"...","positionAttente":N|null},"message":"..."}
```
Statut résultant : `inscrit` | `indisponible` | `absent_reponse` | `liste_attente` | `complet`

- `inscrit` → écrit Prevue='o'. Si l'événement est **complet** (et pas déjà inscrit),
  renvoie `statut:"complet"` sans effet : l'app propose alors `liste_attente`.
- `indisponible` → écrit Prevue='n' (ligne conservée), libère une place → promotion auto.
- `absent_reponse` → efface la réponse (supprime la ligne) + sort de la liste d'attente
  + promotion auto.
- `liste_attente` → rejoint la file (idempotent), renvoie `positionAttente`.

Le GET présences renvoie aussi un champ dérivé `statut` (`inscrit`/`indisponible`)
en plus de `Prevue`.

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
