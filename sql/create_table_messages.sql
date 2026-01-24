-- ============================================================
-- Script SQL : Création de la table NPVB_Messages
-- Compatible MySQL 4.x (Pages Perso Free)
-- Date: 2026-01-24
-- ============================================================

-- Suppression de la table si elle existe déjà (pour réinstallation)
DROP TABLE IF EXISTS NPVB_Messages;

-- Création de la table des messages d'accueil
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

-- Insertion d'un message d'exemple
INSERT INTO NPVB_Messages (title, content, is_active, created_at, created_by)
VALUES (
  'Bienvenue sur le nouveau système de messages',
  'Vous pouvez désormais créer et gérer des messages d\'actualité qui seront affichés sur la page d\'accueil du site.',
  1,
  NOW(),
  'admin'
);

-- Vérification de l'insertion
SELECT * FROM NPVB_Messages;
