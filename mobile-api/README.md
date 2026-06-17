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

### Présences
```
POST /mobile-api/v1/index.php?endpoint=presences
Body: {"dateHeure":"20250125200000","joueur":"pseudo","libelle":"MATCH","presence":"o"}
Valeurs presence: "o" (présent), "n" (désinscription), "!" (absent)
```

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
