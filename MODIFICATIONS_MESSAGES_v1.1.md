# Modifications - Messages d'Accueil v1.1

**Version :** 1.1
**Date :** 2026-01-24
**Type :** Modifications fonctionnelles

---

## 📝 Résumé des changements

Deux modifications importantes ont été apportées à l'affichage des messages d'accueil :

1. ✅ **Visibilité restreinte** : Les messages ne s'affichent plus qu'aux **membres connectés**
2. ✅ **Pagination** : Affichage de **2 messages par page** au lieu de 5 en une seule fois

---

## 🔄 Changement 1 : Affichage réservé aux membres connectés

### Avant

Les messages s'affichaient pour **tous les visiteurs** (connectés ou non).

### Après

Les messages s'affichent **uniquement pour les membres connectés**.

**Raison :** Les messages importants sont réservés aux membres du club.

### Code modifié

**Fichier :** `accueil.inc.php`

**Avant :**
```php
// Affichage pour tout le monde
$query_messages = "SELECT * FROM NPVB_Messages WHERE is_active = 1 ...";
```

**Après :**
```php
// Affichage uniquement si membre connecté
if (isset($Joueur) && is_object($Joueur)) {
    // ... affichage des messages
}
```

---

## 📄 Changement 2 : Pagination (2 messages par page)

### Avant

- Affichage de **5 messages** maximum en une seule fois
- Pas de navigation entre les messages
- Tous les messages visibles d'un coup

### Après

- Affichage de **2 messages par page**
- Navigation avec boutons **"Précédent"** et **"Suivant"**
- Indicateur **"Page X sur Y"**

### Fonctionnement

**Exemple avec 7 messages actifs :**

```
Page 1 : Messages 1-2  [Suivant »]
Page 2 : Messages 3-4  [« Précédent] [Suivant »]
Page 3 : Messages 5-6  [« Précédent] [Suivant »]
Page 4 : Message 7     [« Précédent]
```

### Interface de pagination

```
┌─────────────────────────────────────────────┐
│ 📢 Messages importants                      │
├─────────────────────────────────────────────┤
│                                             │
│ Message 1                                   │
│ ...                                         │
│                                             │
│ Message 2                                   │
│ ...                                         │
│                                             │
│ ─────────────────────────────────────────── │
│   [« Précédent]  Page 2 sur 4  [Suivant »] │
└─────────────────────────────────────────────┘
```

### Code de pagination

**Fichier :** `accueil.inc.php`

**Logique :**
```php
// 1. Récupérer le numéro de page (paramètre GET)
$page_msg = isset($_GET['PageMsg']) ? (int)$_GET['PageMsg'] : 1;

// 2. Calculer l'offset SQL
$messages_par_page = 2;
$offset = ($page_msg - 1) * $messages_par_page;

// 3. Compter le total de messages
SELECT COUNT(*) as total FROM NPVB_Messages WHERE is_active = 1

// 4. Calculer le nombre total de pages
$total_pages = ceil($total_messages / $messages_par_page);

// 5. Requête avec LIMIT et OFFSET
SELECT * FROM NPVB_Messages
WHERE is_active = 1
ORDER BY created_at DESC
LIMIT $offset, $messages_par_page
```

**Liens de navigation :**
```php
// Page précédente
?Page=accueil&PageMsg=<?php echo ($page_msg - 1); ?>

// Page suivante
?Page=accueil&PageMsg=<?php echo ($page_msg + 1); ?>
```

---

## 🔧 Fichiers modifiés

### 1. `accueil.inc.php`

**Lignes modifiées :** 12-49 (remplacement complet de la section)

**Changements :**
- ✅ Ajout condition `if ($Joueur)` pour restreindre l'affichage
- ✅ Ajout pagination avec paramètre `PageMsg`
- ✅ Requête COUNT pour calculer le total
- ✅ Requête avec LIMIT/OFFSET pour la page courante
- ✅ Affichage des liens de navigation
- ✅ Indicateur "Page X sur Y"

### 2. `index2.php`

**Ligne modifiée :** 28

**Changement :**
- ✅ Ajout de `'PageMsg'` dans `$allowed_vars`

**Avant :**
```php
$allowed_vars = array('Page', 'Pseudonyme', 'Password', ...);
```

**Après :**
```php
$allowed_vars = array('Page', 'Pseudonyme', 'Password', ..., 'PageMsg');
```

**Raison :** Autoriser le paramètre GET `?PageMsg=2` pour la navigation

---

## 🎯 Impact utilisateur

### Pour les visiteurs non connectés

**Avant :**
- Voyaient les messages sur la page d'accueil

**Après :**
- ❌ Ne voient **plus** les messages
- Doivent se connecter pour voir les actualités

### Pour les membres connectés

**Avant :**
- Voyaient 5 messages d'un coup (parfois trop chargé)

**Après :**
- ✅ Voient 2 messages à la fois (plus lisible)
- ✅ Peuvent naviguer avec "Précédent" / "Suivant"
- ✅ Savent combien de pages il y a au total

---

## 📊 Exemples de scénarios

### Scénario 1 : Aucun message actif

**Affichage :**
- Rien ne s'affiche (pas d'encadré orange)
- Page d'accueil normale

### Scénario 2 : 1 message actif

**Affichage :**
- 1 message affiché
- Pas de pagination (1 seule page)

### Scénario 3 : 2 messages actifs

**Affichage :**
- 2 messages affichés
- Pas de pagination (1 seule page)

### Scénario 4 : 3 messages actifs

**Affichage :**
- Page 1 : 2 messages + bouton "Suivant"
- Page 2 : 1 message + bouton "Précédent"

### Scénario 5 : 10 messages actifs

**Affichage :**
- Page 1 : Messages 1-2 + "Suivant"
- Page 2 : Messages 3-4 + "Précédent" + "Suivant"
- Page 3 : Messages 5-6 + "Précédent" + "Suivant"
- Page 4 : Messages 7-8 + "Précédent" + "Suivant"
- Page 5 : Messages 9-10 + "Précédent"

---

## 🔒 Sécurité

### Protection du paramètre PageMsg

```php
// Type casting pour éviter injection
$page_msg = isset($_GET['PageMsg']) ? (int)$_GET['PageMsg'] : 1;

// Vérification >= 1
if ($page_msg < 1) $page_msg = 1;

// Utilisation dans requête SQL (sécurisé car casté en int)
LIMIT $offset, $messages_par_page
```

### Whitelist mise à jour

Le paramètre `PageMsg` a été ajouté à la whitelist de `index2.php` pour être autorisé.

---

## 🧪 Tests à effectuer

### Test 1 : Affichage pour non-connecté

1. Se déconnecter du site
2. Aller sur la page d'accueil
3. ✅ Vérifier que **aucun message** ne s'affiche

### Test 2 : Affichage pour membre connecté

1. Se connecter avec un compte membre
2. Aller sur la page d'accueil
3. ✅ Vérifier que les messages s'affichent dans l'encadré orange

### Test 3 : Pagination avec 1-2 messages

1. S'assurer qu'il y a 1 ou 2 messages actifs
2. Vérifier que **pas de pagination** s'affiche (normal)

### Test 4 : Pagination avec 3+ messages

1. Créer au moins 3 messages actifs
2. Page d'accueil → Vérifier 2 messages affichés
3. Cliquer "Suivant" → Voir message 3
4. Cliquer "Précédent" → Retour aux messages 1-2

### Test 5 : Navigation directe

1. Aller sur `?Page=accueil&PageMsg=2`
2. ✅ Vérifier que la page 2 s'affiche correctement
3. Essayer `?Page=accueil&PageMsg=999` (page inexistante)
4. ✅ Vérifier qu'aucune erreur ne se produit (affichage vide OK)

### Test 6 : Création d'un nouveau message

1. Admin.Messages → Créer un nouveau message
2. Activer le message
3. Page d'accueil → Vérifier qu'il apparaît en **page 1** (le plus récent)

---

## 📝 Notes de déploiement

### Fichiers à uploader via FTP

```
npvb-web/
├── accueil.inc.php      [REMPLACER]
└── index2.php           [REMPLACER]
```

### Étapes

1. **Backup** : Télécharger les versions actuelles avant de remplacer
2. **Upload** : Remplacer par les nouvelles versions
3. **Test** : Effectuer les tests ci-dessus
4. **Vider cache** : Ctrl+F5 dans le navigateur

### Compatibilité

- ✅ PHP 4.x / 5.x
- ✅ MySQL 4.x / 5.x
- ✅ Tous navigateurs
- ✅ Mobile responsive

### Rollback si nécessaire

En cas de problème, restaurer les backups :
```
cp accueil.inc.php.backup accueil.inc.php
cp index2.php.backup index2.php
```

---

## 💡 Évolutions possibles (futures)

### Version 1.2

- [ ] Nombre de messages par page configurable
- [ ] Navigation "Aller à la page X"
- [ ] Raccourcis "Première page" / "Dernière page"
- [ ] Affichage "X-Y sur Z messages"

### Version 1.3

- [ ] AJAX pour pagination sans rechargement
- [ ] Animation de transition entre pages
- [ ] Préchargement page suivante

---

## 🆘 Problèmes potentiels

### Problème : Pagination ne fonctionne pas

**Symptôme :** Clic sur "Suivant" ne change rien

**Cause :** Paramètre `PageMsg` non autorisé

**Solution :**
1. Vérifier que `index2.php` contient bien `'PageMsg'` dans `$allowed_vars`
2. Vider le cache navigateur

### Problème : Messages visibles pour non-connectés

**Symptôme :** Les messages s'affichent sans connexion

**Cause :** Condition `if ($Joueur)` manquante ou mal placée

**Solution :**
1. Vérifier que la condition englobe tout le code d'affichage
2. Re-uploader `accueil.inc.php`

### Problème : Erreur SQL

**Symptôme :** "Table doesn't exist"

**Cause :** Table `NPVB_Messages` non créée

**Solution :**
1. Exécuter le script SQL `create_table_messages.sql`
2. Vérifier que la table existe dans phpMyAdmin

---

## ✅ Checklist de déploiement

- [ ] Backup de `accueil.inc.php` effectué
- [ ] Backup de `index2.php` effectué
- [ ] Upload via FTP de `accueil.inc.php`
- [ ] Upload via FTP de `index2.php`
- [ ] Test : Non-connecté ne voit pas les messages
- [ ] Test : Connecté voit les messages
- [ ] Test : Pagination fonctionne (si 3+ messages)
- [ ] Test : Navigation Précédent/Suivant
- [ ] Cache navigateur vidé
- [ ] Vérification mobile (responsive)

---

## 📞 Support

**En cas de problème :**
- 📧 Email : nantespvb@gmail.com
- 📄 Documentation : `DOCUMENTATION_MESSAGES_ACCUEIL.md`

---

**Version :** 1.1
**Date :** 2026-01-24
**Auteur :** Développement NPVB
**Statut :** ✅ Prêt pour déploiement
