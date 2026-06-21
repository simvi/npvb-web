-- Schéma de la base de données du site club volley
-- Généré depuis nantespvb_dev — structure uniquement, sans données
-- Utiliser avec : mysql -u <user> -p <database> < schema.sql

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS `NPVB_Joueurs` (
  `Pseudonyme`        varchar(30)                  NOT NULL DEFAULT '',
  `Password`          varchar(16)                  NOT NULL,
  `DieuToutPuissant`  enum('o','n')                NOT NULL DEFAULT 'n',
  `Titre`             varchar(30)                  DEFAULT NULL,
  `Etat`              enum('V','I','E')             NOT NULL DEFAULT 'I',
  `Adhesion`          date                         DEFAULT NULL,
  `Nom`               varchar(40)                  NOT NULL DEFAULT '',
  `Prenom`            varchar(30)                  NOT NULL DEFAULT '',
  `Sexe`              enum('m','f')                NOT NULL DEFAULT 'm',
  `DateNaissance`     date                         DEFAULT NULL,
  `Profession`        varchar(50)                  DEFAULT NULL,
  `Adresse`           varchar(100)                 DEFAULT NULL,
  `CPVille`           varchar(40)                  DEFAULT NULL,
  `Telephones`        varchar(55)                  DEFAULT NULL,
  `Email`             varchar(80)                  DEFAULT NULL,
  `Accord`            enum('n','o')                NOT NULL DEFAULT 'n',
  `Internet`          enum('o','n','s','w','r')    DEFAULT NULL,
  `PremiereAdhesion`  date                         DEFAULT NULL,
  `License`           date                         DEFAULT NULL,
  `Message`           varchar(200)                 DEFAULT NULL,
  `Description`       varchar(200)                 DEFAULT NULL,
  `NumeroLicence`     varchar(256)                 NOT NULL DEFAULT '',
  PRIMARY KEY (`Pseudonyme`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `NPVB_Equipes` (
  `Nom`             varchar(10)        NOT NULL,
  `Responsable`     varchar(30)        DEFAULT NULL,
  `Supleant`        varchar(30)        DEFAULT NULL,
  `TousJoueurs`     enum('o','n')      NOT NULL DEFAULT 'n',
  `PresenceDefaut`  enum('o','n')      NOT NULL DEFAULT 'n',
  PRIMARY KEY (`Nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `NPVB_Appartenance` (
  `Joueur`  varchar(30)  NOT NULL DEFAULT '',
  `Equipe`  varchar(10)  NOT NULL,
  PRIMARY KEY (`Joueur`, `Equipe`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `NPVB_Evenements` (
  `DateHeure`     varchar(14)   NOT NULL DEFAULT '00000000000000',
  `DateHeureOLD`  timestamp     NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `Libelle`       varchar(10)   NOT NULL,
  `Etat`          varchar(21)   NOT NULL DEFAULT 'I',
  `Titre`         varchar(10)   NOT NULL DEFAULT '',
  `Intitule`      varchar(30)   DEFAULT NULL,
  `Lieu`          varchar(30)   DEFAULT NULL,
  `Adresse`       varchar(50)   DEFAULT NULL,
  `Adversaire`    varchar(30)   DEFAULT NULL,
  `Domicile`      enum('o','n') DEFAULT NULL,
  `Resultat`      varchar(36)   DEFAULT NULL,
  `Analyse`       text          DEFAULT NULL,
  `InscritsMax`   int(11)       NOT NULL DEFAULT 0,
  PRIMARY KEY (`DateHeure`, `Libelle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `NPVB_Presence` (
  `Joueur`        varchar(30)    NOT NULL DEFAULT '',
  `DateHeure`     varchar(14)    NOT NULL DEFAULT '00000000000000',
  `DateHeureOLD`  timestamp      NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `Libelle`       varchar(10)    NOT NULL,
  `Prevue`        enum('o','n')  DEFAULT NULL,
  `Effective`     enum('o','n')  DEFAULT NULL,
  PRIMARY KEY (`Joueur`, `DateHeure`, `Libelle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `NPVB_News` (
  `id`       int(11)   NOT NULL,
  `title`    text      NOT NULL,
  `message`  text      NOT NULL,
  `date`     date      NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `NPVB_Messages` (
  `id`          int(11)       NOT NULL AUTO_INCREMENT,
  `title`       varchar(255)  DEFAULT NULL,
  `content`     mediumtext    NOT NULL,
  `is_active`   tinyint(1)    NOT NULL DEFAULT 1,
  `created_at`  datetime      NOT NULL,
  `updated_at`  datetime      DEFAULT NULL,
  `created_by`  varchar(30)   DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `NPVB_Contenu` (
  `cle`         varchar(50)   NOT NULL,
  `contenu`     mediumtext    NOT NULL,
  `updated_at`  datetime      DEFAULT NULL,
  `updated_by`  varchar(30)   DEFAULT NULL,
  PRIMARY KEY (`cle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `NPVB_PasswordReset` (
  `Id`              int(11)        NOT NULL AUTO_INCREMENT,
  `Token`           char(40)       NOT NULL,
  `Pseudonyme`      varchar(30)    NOT NULL,
  `DateCreation`    datetime       NOT NULL,
  `DateExpiration`  datetime       NOT NULL,
  `Utilise`         enum('n','o')  NOT NULL DEFAULT 'n',
  `IpDemande`       varchar(45)    DEFAULT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Token` (`Token`),
  KEY `idx_token` (`Token`),
  KEY `idx_pseudonyme` (`Pseudonyme`),
  KEY `idx_expiration` (`DateExpiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `NPVB_JoueurRoles` (
  `Pseudonyme`  varchar(30)  NOT NULL,
  `Role`        varchar(20)  NOT NULL,
  PRIMARY KEY (`Pseudonyme`, `Role`),
  KEY `idx_pseudo` (`Pseudonyme`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `NPVB_Navigateurs` (
  `Navigateur`  varchar(255)   NOT NULL DEFAULT '',
  `Vision`      enum('o','n')  NOT NULL DEFAULT 'n',
  `Lu`          enum('o','n')  NOT NULL DEFAULT 'n',
  PRIMARY KEY (`Navigateur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
