# MarketStudy Pro — Plateforme Web d'Études de Marché

> **Projet N°69** — Master CCA, École Supérieure Polytechnique de Dakar
> Plateforme web d'études de marché avec enquêtes en ligne et analyses statistiques

## Description

MarketStudy Pro est une plateforme web complète permettant de conduire des études de marché en ligne : construction de questionnaires, échantillonnage, saisie des réponses, traitement statistique (tris, tests, ACP, classification) et génération de rapports.

La plateforme couvre l'intégralité du cycle d'une étude de marché, de la conception du questionnaire à l'analyse statistique avancée et la production de rapports.

## Stack technique

| Technologie | Rôle |
|-------------|------|
| PHP 8+ | Logique côté serveur |
| MySQL | Base de données (via PDO) |
| Apache | Serveur web (XAMPP) |
| HTML5 / CSS3 | Structure et style |
| JavaScript (ES6) | Interactions côté client |
| Chart.js 4.4 | Graphiques et visualisations |
| Font Awesome 6.5 | Icônes |
| Google Fonts (Inter) | Typographie |

## Prérequis

- [XAMPP](https://www.apachefriends.org/) (PHP 8.0+, MySQL 5.7+/MariaDB 10.4+)
- Navigateur web moderne (Chrome, Firefox, Safari, Edge)

## Installation

1. **Cloner le dépôt**
   ```bash
   git clone https://github.com/saly-diouf/projet69.git
   ```
   Ou copier le dossier `projet69/` dans `Applications/XAMPP/xamppfiles/htdocs/`

2. **Démarrer XAMPP**
   - Ouvrir le panneau de contrôle XAMPP
   - Démarrer **Apache** et **MySQL**

3. **Créer la base de données**
   - Ouvrir phpMyAdmin : `http://localhost/phpmyadmin`
   - Onglet **Importer** → sélectionner `database/schema.sql` → **Exécuter**
   - La base `etude_marche` est créée avec toutes les tables

4. **Lancer le script d'installation**
   - Accéder à `http://localhost/projet69/setup.php`
   - Ce script crée les comptes utilisateurs par défaut et les tables si nécessaire

5. **(Optionnel) Charger les données de démonstration**
   - Accéder à `http://localhost/projet69/seed.php`
   - Crée une étude d'exemple avec questions, répondants et réponses

## Configuration

La configuration se trouve dans `config/config.php` :

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'etude_marche');
define('DB_USER', 'root');
define('DB_PASS', '');
define('APP_URL', 'http://localhost/projet69');
```

Modifiez ces constantes si votre configuration XAMPP diffère (mot de passe MySQL, port, etc.).

## Utilisateurs de test

Le script `setup.php` crée trois comptes avec des rôles distincts :

| Rôle | Email | Mot de passe | Accès |
|------|-------|--------------|-------|
| **Administrateur** | `admin@marketstudy.com` | `admin123` | Gestion des utilisateurs, études, analyses |
| **Chercheur** | `chercheur@marketstudy.com` | `chercheur123` | Création d'études, questionnaires, analyses, rapports |
| **Répondant** | `repondant@marketstudy.com` | `repondant123` | Participation aux enquêtes |

> Les mots de passe sont hachés avec `password_hash()` (bcrypt) dans la base de données.

## Architecture du projet

```
projet69/
├── admin/              # Gestion des utilisateurs (admin)
├── analyses/           # Modules d'analyse statistique
│   ├── tris_a_plat.php     # Tris à plat (effectifs, %)
│   ├── tris_croises.php    # Tris croisés (contingence)
│   ├── khi2.php            # Test du Khi² + V de Cramer
│   ├── anova.php           # ANOVA + t de Student
│   ├── correlation.php     # Pearson + Spearman
│   ├── acp.php             # Analyse en Composantes Principales
│   └── classification.php  # CAH + K-means
├── assets/
│   ├── css/style.css       # Design system
│   └── js/main.js          # Charts, interactions
├── auth/               # Authentification (login, register, logout)
├── config/             # Configuration et fonctions helpers
├── database/           # Schéma SQL
├── distribution/       # Distribution (lien, QR code, email)
├── docs/               # Documentation technique et manuel utilisateur
├── etudes/             # CRUD des études de marché
├── includes/           # Header et footer (layout)
├── questionnaire/      # Constructeur de questionnaire
├── rapports/           # Génération et liste des rapports
├── survey/             # Saisie des réponses (côté répondant)
├── echantillonnage.php # Calculateur de taille d'échantillon
├── generate_docs.php   # Génération de la documentation Word
├── index.php           # Tableau de bord
├── landing.php         # Page d'accueil publique
├── seed.php            # Données de démonstration
└── setup.php           # Script d'installation
```

## Fonctionnalités principales

### Gestion des études
- Création, modification, suppression d'études
- Statuts : brouillon, active, terminée
- Paramètres : taille cible, marge d'erreur, niveau de confiance, méthode d'échantillonnage

### Construction du questionnaire
- Types de questions : fermées (unique/multiple), Likert, échelle numérique, ouvertes, numérique, classement
- Sections et ordre des questions
- Sauts conditionnels selon les réponses
- Options de réponse personnalisables

### Distribution
- Lien direct de participation
- QR code généré automatiquement
- Invitations par email avec tokens uniques

### Analyses statistiques
- **Tris à plat** : effectifs, pourcentages, mode, médiane, écart-type
- **Tris croisés** : tableaux de contingence
- **Test du Khi²** : χ² = Σ(O−E)²/E, p-value, V de Cramer
- **ANOVA & t de Student** : comparaison de moyennes
- **Corrélations** : Pearson et Spearman
- **ACP** : Analyse en Composantes Principales
- **Classification** : CAH (dendrogramme) et K-means
- **Interprétation textuelle automatique** des résultats

### Rapports
- Génération de rapports d'étude avec graphiques
- Export PDF du rapport

## Auteur

**Saly Diouf**
Master CCA — École Supérieure Polytechnique de Dakar
Année universitaire 2025–2026

## Licence

Ce projet est un travail académique réalisé dans le cadre du Master CCA à l'ESP Dakar.
