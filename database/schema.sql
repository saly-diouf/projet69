-- ============================================================
-- Projet 69 : Plateforme web d'études de marché
-- Base de données : etude_marche
-- ============================================================

CREATE DATABASE IF NOT EXISTS etude_marche CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE etude_marche;

-- ============================================================
-- Table : users (trois acteurs : admin, chercheur, repondant)
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('admin','chercheur','repondant') NOT NULL DEFAULT 'repondant',
    telephone VARCHAR(20) NULL,
    organisation VARCHAR(200) NULL,
    actif TINYINT(1) DEFAULT 1,
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
    derniere_connexion DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table : etudes (études de marché)
CREATE TABLE IF NOT EXISTS etudes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    domaine VARCHAR(100),
    statut ENUM('brouillon', 'active', 'terminee') DEFAULT 'brouillon',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    date_ouverture DATE NULL,
    date_cloture DATE NULL,
    taille_cible INT DEFAULT 100,
    marge_erreur DECIMAL(5,2) DEFAULT 5.00,
    niveau_confiance DECIMAL(5,2) DEFAULT 95.00,
    methode_echantillonnage ENUM('aleatoire_simple','aleatoire_stratifie','quotas','convenance') DEFAULT 'aleatoire_simple',
    -- L'utilisateur (chercheur) qui a créé l'étude
    user_id INT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table : sections (sections du questionnaire)
CREATE TABLE IF NOT EXISTS sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etude_id INT NOT NULL,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    ordre INT DEFAULT 0,
    FOREIGN KEY (etude_id) REFERENCES etudes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table : questions
CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_id INT NOT NULL,
    etude_id INT NOT NULL,
    libelle TEXT NOT NULL,
    type ENUM('fermee_une','fermee_multiple','likert','echelle','ouverte','numerique','classement') NOT NULL,
    obligatoire TINYINT(1) DEFAULT 1,
    ordre INT DEFAULT 0,
    -- Logique de saut : question_id de destination si une certaine réponse est choisie
    saut_conditionnel TEXT NULL COMMENT 'JSON: {"reponse_valeur": question_id_destination}',
    -- Pour les échelles
    echelle_min INT DEFAULT 1,
    echelle_max INT DEFAULT 5,
    echelle_libelle_min VARCHAR(100),
    echelle_libelle_max VARCHAR(100),
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    FOREIGN KEY (etude_id) REFERENCES etudes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table : reponses_possibles (choix pour questions fermées, Likert, etc.)
CREATE TABLE IF NOT EXISTS reponses_possibles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    libelle VARCHAR(255) NOT NULL,
    valeur INT DEFAULT 0,
    ordre INT DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table : respondents (personnes ayant répondu)
CREATE TABLE IF NOT EXISTS respondents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etude_id INT NOT NULL,
    token VARCHAR(64) UNIQUE,
    statut ENUM('invite','en_cours','termine','abandon') DEFAULT 'invite',
    date_invitation DATETIME NULL,
    date_debut DATETIME NULL,
    date_fin DATETIME NULL,
    -- Variables de segmentation
    age INT NULL,
    genre ENUM('M','F','Autre') NULL,
    ville VARCHAR(100) NULL,
    profession VARCHAR(100) NULL,
    -- Métadonnées
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    FOREIGN KEY (etude_id) REFERENCES etudes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table : reponses (réponses individuelles aux questions)
CREATE TABLE IF NOT EXISTS reponses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    respondent_id INT NOT NULL,
    question_id INT NOT NULL,
    -- Pour les questions fermées/likert : id de reponses_possibles
    reponse_possibles_id INT NULL,
    -- Pour les questions ouvertes : texte libre
    valeur_texte TEXT NULL,
    -- Pour les questions numériques/échelles : valeur numérique
    valeur_numerique DECIMAL(10,4) NULL,
    -- Pour les questions à choix multiples : JSON des ids
    valeur_multiple TEXT NULL COMMENT 'JSON array of reponses_possibles_id',
    -- Pour le classement : JSON ordonné
    valeur_classement TEXT NULL COMMENT 'JSON ordered array',
    date_reponse DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (respondent_id) REFERENCES respondents(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    FOREIGN KEY (reponse_possibles_id) REFERENCES reponses_possibles(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table : invitations (suivi des invitations par email)
CREATE TABLE IF NOT EXISTS invitations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etude_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(64) UNIQUE,
    statut ENUM('envoye','ouvert','repondu','rebond') DEFAULT 'envoye',
    date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_ouverture DATETIME NULL,
    FOREIGN KEY (etude_id) REFERENCES etudes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table : rapports (rapports d'étude générés)
CREATE TABLE IF NOT EXISTS rapports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etude_id INT NOT NULL,
    titre VARCHAR(255) NOT NULL,
    contenu TEXT COMMENT 'JSON du rapport',
    date_generation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (etude_id) REFERENCES etudes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Index pour optimiser les requêtes
CREATE INDEX idx_questions_etude ON questions(etude_id);
CREATE INDEX idx_reponses_respondent ON reponses(respondent_id);
CREATE INDEX idx_reponses_question ON reponses(question_id);
CREATE INDEX idx_respondents_etude ON respondents(etude_id);
CREATE INDEX idx_invitations_etude ON invitations(etude_id);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_etudes_user ON etudes(user_id);

-- ============================================================
-- Les comptes utilisateurs par défaut sont créés par setup.php
-- avec password_hash() de PHP pour un hashage sécurisé.
-- Trois acteurs :
--   1. Admin    : admin@marketstudy.com    / admin123
--   2. Chercheur : chercheur@marketstudy.com / chercheur123
--   3. Répondant : repondant@marketstudy.com / repondant123
-- ============================================================
