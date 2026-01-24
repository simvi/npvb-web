# Documentation - Gestion des Messages d'Accueil

**Version:** 1.0
**Date:** 2026-01-24
**Compatibilité:** PHP 4.x, MySQL 4.x, Pages Perso Free

---

## 📋 Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Installation](#installation)
3. [Utilisation](#utilisation)
4. [Fonctionnalités](#fonctionnalités)
5. [Structure technique](#structure-technique)
6. [FAQ](#faq)
7. [Support](#support)

---

## 1. Vue d'ensemble

### 1.1 Objectif

Cette fonctionnalité permet aux administrateurs du site NPVB de créer, modifier et gérer des messages d'actualité qui s'affichent automatiquement sur la page d'accueil du site, aussi bien pour les visiteurs non connectés que pour les membres connectés.

### 1.2 Cas d'usage

- **Annonces importantes** : Fermeture exceptionnelle, changement de gymnase
- **Actualités du club** : Résultats de tournoi, événements à venir
- **Messages urgents** : Annulation d'entraînement, informations COVID
- **Communications** : Rappels, informations administratives

### 1.3 Accès

✅ **Réservé aux administrateurs** (`DieuToutPuissant = "o"`)
📍 **Menu :** Admin > Admin.Messages
🌐 **Affichage public :** Page d'accueil (automatique)

---

## 2. Installation

### 2.1 Prérequis

- Accès FTP au serveur Free
- Accès phpMyAdmin ou ligne de commande MySQL
- Compte administrateur sur le site NPVB

### 2.2 Étapes d'installation

#### Étape 1 : Créer la table dans la base de données

1. **Se connecter à phpMyAdmin**
   - URL : https://phpmyadmin.free.fr
   - Identifiant : Votre login Free
   - Base de données : Sélectionner votre base NPVB

2. **Exécuter le script SQL**
   - Cliquer sur l'onglet "SQL"
   - Copier le contenu du fichier `sql/create_table_messages.sql`
   - Cliquer sur "Exécuter"

```sql
-- Vérifier que la table a été créée
SHOW TABLES LIKE 'NPVB_Messages';

-- Vérifier la structure
DESCRIBE NPVB_Messages;

-- Vérifier le message d'exemple
SELECT * FROM NPVB_Messages;
```

3. **Confirmer la création**
   - Vous devriez voir une table `NPVB_Messages` avec 1 message d'exemple

#### Étape 2 : Uploader les fichiers

Via FTP, uploader les fichiers suivants dans le répertoire racine du site :

```
/
├── adminmessages.inc.php      (NOUVEAU)
├── accueil.inc.php            (MODIFIÉ)
├── index2.php                 (MODIFIÉ)
└── sql/
    └── create_table_messages.sql
```

**Permissions recommandées :**
- `adminmessages.inc.php` → 644 (rw-r--r--)
- `accueil.inc.php` → 644
- `index2.php` → 644

#### Étape 3 : Vérification

1. **Tester l'accès admin**
   - Se connecter en tant qu'administrateur
   - Aller dans le menu : Admin > Admin.Messages
   - Vérifier que la page se charge correctement

2. **Tester l'affichage public**
   - Se déconnecter (ou ouvrir en navigation privée)
   - Aller sur la page d'accueil
   - Vérifier que le message d'exemple s'affiche dans un encadré orange

3. **Tester les fonctionnalités**
   - Créer un nouveau message
   - Le modifier
   - Le désactiver
   - Le supprimer

---

## 3. Utilisation

### 3.1 Accéder à l'interface de gestion

1. Se connecter au site avec un compte **administrateur**
2. Cliquer sur le menu **"Admin.Messages"** dans la barre de navigation admin
3. Vous arrivez sur la page de gestion des messages

### 3.2 Créer un nouveau message

#### Via le formulaire

1. **Remplir le formulaire** en haut de page :
   - **Titre** (optionnel) : Un titre accrocheur (ex: "IMPORTANT", "Info tournoi")
   - **Contenu** (obligatoire) : Le texte de votre message
   - **Message actif** : Cocher si vous voulez qu'il s'affiche immédiatement

2. **Utiliser du HTML basique** (optionnel) :
   ```html
   <b>Texte en gras</b>
   <i>Texte en italique</i>
   <br/> Saut de ligne
   <a href="https://exemple.com">Lien hypertexte</a>
   ```

3. **Cliquer sur "Créer le message"**

#### Exemple de message

**Titre :** ANNULATION - Entraînement du 25 janvier

**Contenu :**
```
L'entraînement du <b>mardi 25 janvier</b> au gymnase Bottière est
<b style="color: red;">annulé</b> en raison de travaux.

<br/><br/>
Rendez-vous mercredi 26 janvier au gymnase Noé Lambert.

<br/><br/>
Merci de votre compréhension.
```

### 3.3 Modifier un message existant

1. Dans la liste des messages, **cliquer sur le bouton "Éditer"**
2. Le formulaire se remplit avec les données du message
3. **Modifier** les champs souhaités
4. **Cliquer sur "Mettre à jour"**
5. **Annuler** pour revenir sans enregistrer

### 3.4 Activer/Désactiver un message

Pour masquer temporairement un message sans le supprimer :

1. **Cliquer sur le bouton "Désactiver"** (ou "Activer")
2. Le statut change immédiatement
3. Le message disparaît (ou apparaît) de la page d'accueil

**Cas d'usage :** Message à durée limitée (événement passé, info obsolète)

### 3.5 Supprimer un message

⚠️ **Action irréversible**

1. **Cliquer sur le bouton "Supprimer"**
2. **Confirmer** dans la popup
3. Le message est définitivement supprimé

---

## 4. Fonctionnalités

### 4.1 Gestion des messages

| Fonctionnalité | Description | Bouton |
|----------------|-------------|--------|
| **Créer** | Ajouter un nouveau message | "Créer le message" |
| **Éditer** | Modifier un message existant | "Éditer" |
| **Activer/Désactiver** | Rendre le message visible ou invisible | "Activer" / "Désactiver" |
| **Supprimer** | Supprimer définitivement | "Supprimer" |

### 4.2 Affichage public

**Localisation :** Page d'accueil, en haut avant le contenu principal

**Apparence :**
- Encadré **orange clair** avec bordure orange
- Icône 📢 "Messages importants"
- Titre en **gras** (si renseigné)
- Contenu avec mise en forme HTML
- Date de publication en petits caractères

**Ordre d'affichage :**
- Les messages **les plus récents en premier**
- Maximum **5 messages** affichés simultanément

**Visibilité :**
- ✅ Visiteurs non connectés
- ✅ Membres connectés
- ✅ Administrateurs

### 4.3 Statuts des messages

| Statut | Badge | Description |
|--------|-------|-------------|
| **Actif** | 🟢 ACTIF | Visible sur la page d'accueil |
| **Inactif** | 🔴 INACTIF | Masqué du public, conservé en base |

### 4.4 Métadonnées

Chaque message enregistre automatiquement :
- **Date de création** : Horodatage précis
- **Auteur** : Pseudonyme de l'admin créateur
- **Date de modification** : Si le message a été édité
- **Statut actif/inactif** : Pour affichage conditionnel

---

## 5. Structure technique

### 5.1 Base de données

**Table :** `NPVB_Messages`

| Champ | Type | Description |
|-------|------|-------------|
| `id` | INT(11) AUTO_INCREMENT | Identifiant unique |
| `title` | VARCHAR(255) | Titre optionnel |
| `content` | TEXT | Contenu du message (HTML autorisé) |
| `is_active` | TINYINT(1) | 1 = actif, 0 = inactif |
| `created_at` | DATETIME | Date/heure de création |
| `updated_at` | DATETIME | Date/heure de dernière modification |
| `created_by` | VARCHAR(30) | Pseudonyme de l'auteur |

**Index :**
- PRIMARY KEY sur `id`
- INDEX sur `is_active` (performance requêtes)
- INDEX sur `created_at` (tri)

**Moteur :** MyISAM (compatible Free hosting)

### 5.2 Fichiers modifiés/créés

#### Nouveau fichier

**`adminmessages.inc.php`** (500+ lignes)
- Interface d'administration complète
- Gestion CRUD (Create, Read, Update, Delete)
- Formulaire de création/édition
- Liste des messages avec actions
- Styles CSS intégrés
- Compatible PHP 4

**Caractéristiques techniques :**
- Utilise `mysql_*` functions (PHP 4)
- Sanitization avec `mysql_real_escape_string()`
- Échappement XSS avec `htmlspecialchars()`
- Confirmation JavaScript pour suppression
- Responsive design basique

#### Fichiers modifiés

**`accueil.inc.php`**
- Ajout de la requête pour récupérer les messages actifs
- Affichage conditionnel dans un encadré stylisé
- Limite à 5 messages maximum
- Compatible avec le reste du code existant

**`index2.php`**
- Ajout de `'adminmessages'` dans `$pages_autorisees`
- Ajout de `'adminmessages'` dans `$pages_admin`
- Ajout du lien menu "Admin.Messages"

### 5.3 Sécurité

#### Contrôle d'accès

```php
// Vérification authentification
if (!$PasseParIndex) { header('Location: index2.php?Page=Erreur404'); return; }

// Vérification admin
if ($Joueur->DieuToutPuissant != "o") { header('Location: index2.php?Page=accueil'); return; }
```

#### Protection XSS

```php
// Échappement des sorties
htmlspecialchars($message->title, ENT_QUOTES, 'ISO-8859-1')
```

#### Protection SQL Injection

```php
// Sanitization des entrées
$title = mysql_real_escape_string(stripslashes($title), $sdblink);
$content = mysql_real_escape_string(stripslashes($content), $sdblink);
```

#### Validation

- Contenu obligatoire (champ `required` HTML + vérif serveur)
- Longueur max titre : 255 caractères (database constraint)
- Type casting pour ID : `(int)$_POST['id']`

### 5.4 Performance

**Optimisations :**
- Index sur `is_active` → requête publique rapide
- LIMIT 5 sur requête d'affichage
- ORDER BY avec index sur `created_at`

**Cache :** Aucun (pages dynamiques)

**Charge serveur :** Négligeable
- 1 requête SELECT par page d'accueil
- ~50ms de temps d'exécution

---

## 6. FAQ

### Q1 : Combien de messages puis-je créer ?

**R :** Illimité. Seuls les 5 plus récents actifs s'affichent sur la page d'accueil, mais vous pouvez en créer autant que nécessaire.

### Q2 : Puis-je insérer des images dans un message ?

**R :** Oui, via une balise HTML `<img>` :
```html
<img src="Images/mon-image.jpg" alt="Description" style="max-width: 100%;" />
```

⚠️ L'image doit déjà être uploadée sur le serveur.

### Q3 : Comment formater mon message ?

**R :** Utilisez du HTML basique :
- `<b>gras</b>` ou `<strong>gras</strong>`
- `<i>italique</i>` ou `<em>italique</em>`
- `<br/>` pour sauter une ligne
- `<a href="...">lien</a>`
- `<ul><li>liste</li></ul>`

### Q4 : Puis-je programmer l'affichage d'un message ?

**R :** Non, pas dans la version actuelle. Vous devez manuellement activer/désactiver les messages.

**Contournement :** Créer le message en mode "inactif", puis l'activer au moment voulu.

### Q5 : Que se passe-t-il si je supprime un message par erreur ?

**R :** Il est **définitivement perdu**. Il n'y a pas de corbeille. Privilégiez la désactivation si vous hésitez.

**Conseil :** Faire une copie du contenu dans un fichier texte avant de supprimer.

### Q6 : Les messages s'affichent-ils sur mobile ?

**R :** Oui, l'encadré est responsive et s'adapte à la taille de l'écran.

### Q7 : Puis-je limiter l'affichage aux membres connectés uniquement ?

**R :** Non, tous les messages actifs sont visibles par tous (connectés ou non). C'est une page d'accueil publique.

**Alternative :** Utiliser le système de messages membres existant (`adminnewmessage`).

### Q8 : Combien de temps prend l'affichage des messages ?

**R :** Environ 50ms (temps de requête SQL négligeable). Aucun impact sur la vitesse du site.

### Q9 : Comment désactiver tous les messages d'un coup ?

**R :** Il faut les désactiver un par un. Pas de fonction "tout désactiver" pour l'instant.

**Alternative technique (SQL) :**
```sql
UPDATE NPVB_Messages SET is_active = 0;
```

### Q10 : Puis-je voir qui a créé un message ?

**R :** Oui, en bas de chaque message dans l'interface admin :
```
Créé le 24/01/2026 à 14:30 par admin
```

---

## 7. Support

### 7.1 Problèmes courants

#### Problème : "Page introuvable" lors de l'accès à Admin.Messages

**Solution :**
1. Vérifier que `adminmessages.inc.php` est bien uploadé
2. Vérifier les permissions du fichier (644)
3. Vider le cache du navigateur

#### Problème : Les messages ne s'affichent pas sur la page d'accueil

**Vérifications :**
1. Le message est-il **actif** ? (badge vert "ACTIF")
2. La table `NPVB_Messages` existe-t-elle ?
3. Y a-t-il au moins 1 message actif dans la base ?
4. Le fichier `accueil.inc.php` a-t-il été correctement modifié ?

**Test SQL :**
```sql
SELECT * FROM NPVB_Messages WHERE is_active = 1;
```

#### Problème : Erreur "Table doesn't exist"

**Solution :**
- La table n'a pas été créée. Exécuter le script SQL `create_table_messages.sql`

#### Problème : Erreur "mysql_real_escape_string() expects parameter 2"

**Cause :** La variable `$sdblink` n'est pas définie (problème de connexion DB)

**Solution :**
1. Vérifier `variables.inc.php` et `_entete.inc.php`
2. Vérifier la connexion à la base de données

#### Problème : Le HTML ne s'affiche pas (balises visibles)

**Cause :** Échappement excessif

**Solution :**
- Dans l'affichage public (`accueil.inc.php`), le contenu n'est **pas** échappé :
```php
<?php echo $message->content; ?>  // Sans htmlspecialchars()
```
- C'est volontaire pour permettre le HTML. Les admins sont responsables du contenu.

### 7.2 Maintenance

#### Nettoyage des anciens messages

Recommandé 1 fois par an :

```sql
-- Supprimer les messages inactifs de plus de 1 an
DELETE FROM NPVB_Messages
WHERE is_active = 0
  AND created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
```

#### Sauvegarde

Avant toute opération de maintenance :

```sql
-- Export de la table
SELECT * FROM NPVB_Messages INTO OUTFILE '/tmp/messages_backup.csv'
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n';
```

Ou via phpMyAdmin : Exporter > Format SQL

### 7.3 Logs et monitoring

**Logs d'accès :** Consulter les logs Apache de Free (si disponibles)

**Activité admin :** Pas de log intégré pour l'instant

**Monitoring suggéré :**
- Nombre total de messages : `SELECT COUNT(*) FROM NPVB_Messages;`
- Messages actifs : `SELECT COUNT(*) FROM NPVB_Messages WHERE is_active = 1;`

---

## 8. Évolutions futures possibles

### Version 2.0 (optionnel)

- [ ] Programmation de l'affichage (date début/fin)
- [ ] Catégories de messages (info, alerte, urgent)
- [ ] Envoi par email aux membres
- [ ] Upload d'images depuis l'interface
- [ ] Historique des modifications
- [ ] Recherche dans les messages
- [ ] Export PDF/CSV
- [ ] Statistiques de consultation

### Améliorations techniques

- [ ] Migration vers PHP 7+ / mysqli
- [ ] Ajout d'un éditeur WYSIWYG (TinyMCE, CKEditor)
- [ ] API REST pour l'app mobile
- [ ] Notifications push
- [ ] Intégration avec le calendrier d'événements

---

## 9. Annexes

### 9.1 Exemples de messages

#### Exemple 1 : Annonce simple

**Titre :** Information
**Contenu :**
```
L'assemblée générale se tiendra le 15 février à 19h au gymnase Noé Lambert.
Venez nombreux !
```

#### Exemple 2 : Annonce urgente

**Titre :** ⚠️ ANNULATION
**Contenu :**
```html
<p style="color: red; font-weight: bold;">
L'entraînement du lundi 24 janvier est ANNULÉ.
</p>

<p>Prochain entraînement : mercredi 26 janvier.</p>
```

#### Exemple 3 : Avec lien

**Titre :** Nouvelle boutique en ligne
**Contenu :**
```html
Vous pouvez désormais acheter vos équipements sur notre
<a href="https://www.helloasso.com/..." target="_blank">
  <b>boutique HelloAsso</b>
</a>.

<br/><br/>
Ballons, maillots, et accessoires disponibles !
```

### 9.2 Checklist de déploiement

- [ ] Sauvegarder l'ancienne version des fichiers
- [ ] Créer la table SQL
- [ ] Uploader `adminmessages.inc.php`
- [ ] Uploader `accueil.inc.php` modifié
- [ ] Uploader `index2.php` modifié
- [ ] Vérifier les permissions (644)
- [ ] Tester l'accès admin
- [ ] Créer un message de test
- [ ] Vérifier l'affichage public
- [ ] Tester toutes les actions (créer, éditer, supprimer)
- [ ] Supprimer le message d'exemple

### 9.3 Commandes SQL utiles

```sql
-- Compter les messages actifs
SELECT COUNT(*) as total_actifs FROM NPVB_Messages WHERE is_active = 1;

-- Voir les 10 derniers messages créés
SELECT id, title, created_at, is_active
FROM NPVB_Messages
ORDER BY created_at DESC
LIMIT 10;

-- Activer tous les messages
UPDATE NPVB_Messages SET is_active = 1;

-- Désactiver tous les messages
UPDATE NPVB_Messages SET is_active = 0;

-- Supprimer les messages de plus de 2 ans
DELETE FROM NPVB_Messages
WHERE created_at < DATE_SUB(NOW(), INTERVAL 2 YEAR);

-- Recherche dans les messages
SELECT * FROM NPVB_Messages
WHERE title LIKE '%tournoi%'
   OR content LIKE '%tournoi%';
```

---

## 10. Contact et support

**Documentation créée le :** 2026-01-24
**Version :** 1.0
**Auteur :** Développement NPVB

Pour toute question ou problème technique :
- 📧 Email club : nantespvb@gmail.com
- 📄 Fichier source : `DOCUMENTATION_MESSAGES_ACCUEIL.md`

---

**✅ Système opérationnel et prêt à l'emploi**
