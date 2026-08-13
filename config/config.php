<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'etude_marche');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuration de l'application
define('APP_NAME', 'MarketStudy Pro');
define('APP_URL', 'http://localhost/projet69');
define('APP_VERSION', '1.0.0');

// Connexion à la base de données
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            die('Erreur de connexion : ' . $e->getMessage());
        }
    }
    return $pdo;
}

// Générer un token unique
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Sécuriser les sorties HTML
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Redirection
function redirect($url) {
    if (!headers_sent()) {
        header("Location: $url");
    } else {
        echo '<script>window.location.href="' . $url . '";</script>';
    }
    exit;
}

// Récupérer un paramètre GET
function getParam($key, $default = null) {
    return isset($_GET[$key]) ? $_GET[$key] : $default;
}

// Récupérer un paramètre POST
function postParam($key, $default = null) {
    return isset($_POST[$key]) ? $_POST[$key] : $default;
}

// Formater une date
function formatDate($date, $format = 'd/m/Y') {
    if (!$date) return '-';
    $d = new DateTime($date);
    return $d->format($format);
}

// Formater un nombre
function formatNumber($n, $decimals = 2) {
    return number_format($n, $decimals, ',', ' ');
}

// Formater un pourcentage
function formatPercent($n, $decimals = 1) {
    return number_format($n, $decimals, ',', ' ') . ' %';
}

// Obtenir la couleur d'un statut
function statutColor($statut) {
    switch ($statut) {
        case 'brouillon': return 'warning';
        case 'active': return 'success';
        case 'terminee': return 'info';
        default: return 'secondary';
    }
}

// Obtenir le libellé d'un statut
function statutLabel($statut) {
    switch ($statut) {
        case 'brouillon': return 'Brouillon';
        case 'active': return 'Active';
        case 'terminee': return 'Terminée';
        default: return $statut;
    }
}

// Obtenir le libellé d'un type de question
function typeQuestionLabel($type) {
    $labels = [
        'fermee_une' => 'Fermée (choix unique)',
        'fermee_multiple' => 'Fermée (choix multiple)',
        'likert' => 'Échelle de Likert',
        'echelle' => 'Échelle numérique',
        'ouverte' => 'Question ouverte',
        'numerique' => 'Question numérique',
        'classement' => 'Classement',
    ];
    return $labels[$type] ?? $type;
}

// Obtenir l'icône d'un type de question
function typeQuestionIcon($type) {
    $icons = [
        'fermee_une' => 'fa-circle-dot',
        'fermee_multiple' => 'fa-square-check',
        'likert' => 'fa-face-smile',
        'echelle' => 'fa-gauge-high',
        'ouverte' => 'fa-keyboard',
        'numerique' => 'fa-hashtag',
        'classement' => 'fa-list-ol',
    ];
    return $icons[$type] ?? 'fa-question';
}

// Obtenir le libellé d'une méthode d'échantillonnage
function methodeEchantillonnageLabel($methode) {
    $labels = [
        'aleatoire_simple' => 'Aléatoire simple',
        'aleatoire_stratifie' => 'Aléatoire stratifié',
        'quotas' => 'Méthode des quotas',
        'convenance' => 'De convenance',
    ];
    return $labels[$methode] ?? $methode;
}

// Calculer la taille d'échantillon
function calculerTailleEchantillon($population, $marge_erreur, $niveau_confiance) {
    $z = 1.96; // pour 95%
    if ($niveau_confiance == 99) $z = 2.576;
    if ($niveau_confiance == 90) $z = 1.645;
    $p = 0.5;
    $e = $marge_erreur / 100;
    if ($population > 0 && $population < 1000000) {
        $n0 = ($z * $z * $p * (1 - $p)) / ($e * $e);
        $n = $n0 / (1 + ($n0 - 1) / $population);
        return ceil($n);
    }
    return ceil(($z * $z * $p * (1 - $p)) / ($e * $e));
}

// ============================================================
// Système d'authentification — Trois acteurs
// ============================================================

// Démarrer la session
function startSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Vérifier si l'utilisateur est connecté
function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']);
}

// Obtenir l'utilisateur courant
function currentUser() {
    if (!isLoggedIn()) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND actif = 1");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Obtenir le rôle de l'utilisateur courant
function currentRole() {
    $user = currentUser();
    return $user ? $user['role'] : null;
}

// Vérifier le rôle
function hasRole($roles) {
    if (!is_array($roles)) $roles = [$roles];
    $role = currentRole();
    return $role && in_array($role, $roles);
}

// Exiger une authentification
function requireLogin() {
    if (!isLoggedIn()) {
        redirect(APP_URL . '/auth/login.php');
    }
}

// Exiger un rôle spécifique
function requireRole($roles) {
    requireLogin();
    if (!hasRole($roles)) {
        redirect(APP_URL . '/index.php?error=forbidden');
    }
}

// Connecter un utilisateur
function loginUser($email, $password) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND actif = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['mot_de_passe'])) {
        startSession();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];

        // Mettre à jour la dernière connexion
        $stmt = $db->prepare("UPDATE users SET derniere_connexion = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);

        return true;
    }
    return false;
}

// Déconnecter
function logoutUser() {
    startSession();
    session_destroy();
}

// Enregistrer un nouvel utilisateur
function registerUser($data) {
    $db = getDB();

    // Empêcher l'auto-inscription en tant qu'admin
    if (isset($data['role']) && $data['role'] === 'admin') {
        $data['role'] = 'repondant';
    }

    // Vérifier si l'email existe déjà
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Cet email est déjà utilisé'];
    }

    $hash = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (nom, prenom, email, mot_de_passe, role, telephone, organisation) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['nom'],
        $data['prenom'],
        $data['email'],
        $hash,
        $data['role'] ?? 'repondant',
        $data['telephone'] ?? null,
        $data['organisation'] ?? null,
    ]);

    return ['success' => true, 'id' => $db->lastInsertId()];
}

// Libellé d'un rôle
function roleLabel($role) {
    $labels = [
        'admin' => 'Administrateur',
        'chercheur' => 'Chercheur',
        'repondant' => 'Répondant',
    ];
    return $labels[$role] ?? $role;
}

// Couleur du badge pour un rôle
function roleColor($role) {
    $colors = [
        'admin' => 'danger',
        'chercheur' => 'primary',
        'repondant' => 'info',
    ];
    return $colors[$role] ?? 'secondary';
}

// Initialisation de la session au chargement
startSession();
