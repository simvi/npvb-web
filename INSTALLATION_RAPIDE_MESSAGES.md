# Guide d'installation rapide - Messages d'accueil

**⏱️ Temps estimé :** 10 minutes
**🔧 Niveau :** Débutant

---

## ✅ Ce qui sera installé

Une interface complète pour gérer des messages d'actualité sur la page d'accueil du site NPVB.

**Fonctionnalités :**
- ✅ Création de messages
- ✅ Modification/suppression
- ✅ Activation/désactivation
- ✅ Affichage automatique page d'accueil

---

## 📦 Fichiers à installer

```
npvb-web/
├── sql/
│   └── create_table_messages.sql          [NOUVEAU]
├── adminmessages.inc.php                  [NOUVEAU]
├── accueil.inc.php                        [MODIFIÉ]
├── index2.php                             [MODIFIÉ]
└── DOCUMENTATION_MESSAGES_ACCUEIL.md      [NOUVEAU]
```

---

## 🚀 Installation en 4 étapes

### Étape 1 : Créer la table SQL (5 min)

1. **Aller sur phpMyAdmin**
   - URL : https://phpmyadmin.free.fr
   - Login : Votre identifiant Free

2. **Sélectionner votre base de données**

3. **Ouvrir l'onglet "SQL"**

4. **Copier-coller ce code SQL :**

```sql
DROP TABLE IF EXISTS NPVB_Messages;

CREATE TABLE NPVB_Messages (
  id INT(11) NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) DEFAULT NULL,
  content TEXT NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME DEFAULT NULL,
  created_by VARCHAR(30) DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_is_active (is_active),
  KEY idx_created_at (created_at)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO NPVB_Messages (title, content, is_active, created_at, created_by)
VALUES (
  'Bienvenue sur le nouveau système de messages',
  'Vous pouvez désormais créer et gérer des messages d\'actualité qui seront affichés sur la page d\'accueil du site.',
  1,
  NOW(),
  'admin'
);
```

5. **Cliquer sur "Exécuter"**

6. **Vérifier** : Une nouvelle table `NPVB_Messages` doit apparaître dans la liste des tables

---

### Étape 2 : Uploader les fichiers via FTP (3 min)

**Logiciel FTP recommandé :**
- FileZilla (Windows/Mac/Linux)
- Cyberduck (Mac)
- WinSCP (Windows)

**Connexion FTP Free :**
- Hôte : `ftpperso.free.fr`
- Port : 21
- Utilisateur : Votre login Free
- Mot de passe : Votre mot de passe Free

**Fichiers à uploader :**

1. **Créer le dossier `sql/` (s'il n'existe pas)**
   - Uploader `create_table_messages.sql` dedans

2. **Uploader à la racine :**
   - `adminmessages.inc.php` (nouveau fichier)
   - `accueil.inc.php` (écrase l'ancien)
   - `index2.php` (écrase l'ancien)
   - `DOCUMENTATION_MESSAGES_ACCUEIL.md` (optionnel)

**⚠️ IMPORTANT :**
- Faire une **sauvegarde** des fichiers originaux avant d'écraser
- Vérifier les permissions : 644 (rw-r--r--)

---

### Étape 3 : Vérification (2 min)

#### Test 1 : Accès admin

1. Se connecter au site avec un compte **administrateur**
2. Dans le menu, cliquer sur **"Admin.Messages"**
3. Vous devriez voir :
   - Un formulaire de création
   - 1 message d'exemple dans la liste

✅ **Si ça fonctionne** → Continuez
❌ **Erreur "Page introuvable"** → Vérifier que `adminmessages.inc.php` est bien uploadé

#### Test 2 : Affichage public

1. Se déconnecter (ou ouvrir en navigation privée)
2. Aller sur la **page d'accueil**
3. Vous devriez voir un **encadré orange** en haut avec le message d'exemple

✅ **Si ça fonctionne** → Installation réussie !
❌ **Pas d'encadré** → Vérifier que `accueil.inc.php` a bien été uploadé

---

### Étape 4 : Premier message (2 min)

1. Aller dans **Admin.Messages**
2. Supprimer le message d'exemple (bouton "Supprimer")
3. Créer votre premier message :
   - **Titre :** Information importante
   - **Contenu :** Ceci est un test du système de messages.
   - **Cocher** "Message actif"
   - **Cliquer** "Créer le message"

4. Rafraîchir la page d'accueil → Votre message s'affiche !

---

## 🎯 C'est terminé !

Vous pouvez maintenant :
- ✅ Créer des messages d'actualité
- ✅ Les modifier/supprimer
- ✅ Les activer/désactiver
- ✅ Affichage automatique sur la page d'accueil

---

## 🆘 Problèmes ?

### Erreur "Table doesn't exist"

**Cause :** La table SQL n'a pas été créée

**Solution :**
1. Retourner sur phpMyAdmin
2. Réexécuter le script SQL de l'Étape 1

### Les messages ne s'affichent pas sur l'accueil

**Vérifications :**
1. Le message est-il **actif** ? (badge vert dans la liste)
2. Y a-t-il au moins 1 message dans la base ?
3. `accueil.inc.php` a-t-il été correctement uploadé ?

**Test SQL :**
```sql
SELECT * FROM NPVB_Messages WHERE is_active = 1;
```

Si 0 résultat → Créer un message actif

### Erreur "Access denied"

**Cause :** Vous n'êtes pas administrateur

**Solution :**
- Se connecter avec un compte ayant `DieuToutPuissant = "o"` dans la base

---

## 📚 Documentation complète

Consultez `DOCUMENTATION_MESSAGES_ACCUEIL.md` pour :
- Guide d'utilisation détaillé
- FAQ
- Exemples de messages
- Troubleshooting avancé
- Structure technique

---

## ✅ Checklist finale

- [ ] Table SQL créée (`NPVB_Messages`)
- [ ] Fichiers uploadés via FTP
- [ ] Permissions vérifiées (644)
- [ ] Accès admin fonctionnel
- [ ] Message de test créé
- [ ] Affichage public vérifié
- [ ] Message d'exemple supprimé
- [ ] Premier vrai message publié

---

**🎉 Félicitations, l'installation est terminée !**

Pour toute question : nantespvb@gmail.com
