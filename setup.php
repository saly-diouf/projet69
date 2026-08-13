<?php
// Script d'initialisation de la base de données
// À exécuter une seule fois pour créer la base et les tables

require_once __DIR__ . '/config/config.php';

try {
    // Connexion sans base de données pour créer la base
    $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Lire et exécuter le schéma SQL
    $sql = file_get_contents(__DIR__ . '/database/schema.sql');
    // Supprimer les commentaires SQL (lignes commençant par --)
    $sql = preg_replace('/--.*$/m', '', $sql);
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            $pdo->exec($stmt);
        }
    }

    // Créer les 3 comptes par défaut avec password_hash
    $db = getDB();
    $accounts = [
        ['nom' => 'Admin', 'prenom' => 'Super', 'email' => 'admin@marketstudy.com', 'pass' => 'admin123', 'role' => 'admin', 'org' => 'MarketStudy Pro'],
        ['nom' => 'Diop', 'prenom' => 'Aminata', 'email' => 'chercheur@marketstudy.com', 'pass' => 'chercheur123', 'role' => 'chercheur', 'org' => 'Université ESP'],
        ['nom' => 'Ndiaye', 'prenom' => 'Moussa', 'email' => 'repondant@marketstudy.com', 'pass' => 'repondant123', 'role' => 'repondant', 'org' => null],
    ];
    foreach ($accounts as $acc) {
        $hash = password_hash($acc['pass'], PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT IGNORE INTO users (nom, prenom, email, mot_de_passe, role, organisation) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$acc['nom'], $acc['prenom'], $acc['email'], $hash, $acc['role'], $acc['org']]);
    }

    echo "<h2 style='font-family:Inter,sans-serif;color:#10b981;'>✓ Base de données créée avec succès !</h2>";
    echo "<p style='font-family:Inter,sans-serif;'>La base de données 'etude_marche' a été créée avec toutes les tables et 3 comptes utilisateurs par défaut.</p>";
    echo "<div style='font-family:Inter,sans-serif;background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:16px;margin:16px 0;max-width:500px;'>";
    echo "<h3 style='margin-bottom:10px;font-size:15px;'>Comptes de démonstration :</h3>";
    echo "<ul style='list-style:none;padding:0;'>";
    echo "<li style='padding:6px 0;'><strong style='color:#ef4444;'>Admin</strong> : admin@marketstudy.com / admin123</li>";
    echo "<li style='padding:6px 0;'><strong style='color:#4f46e5;'>Chercheur</strong> : chercheur@marketstudy.com / chercheur123</li>";
    echo "<li style='padding:6px 0;'><strong style='color:#0ea5e9;'>Répondant</strong> : repondant@marketstudy.com / repondant123</li>";
    echo "</ul></div>";
    echo "<p style='font-family:Inter,sans-serif;'><a href='auth/login.php' style='color:#4f46e5;font-weight:600;font-size:16px;'>→ Se connecter</a></p>";

} catch (PDOException $e) {
    echo "<h2 style='font-family:Inter,sans-serif;color:#ef4444;'>Erreur</h2>";
    echo "<p style='font-family:Inter,sans-serif;'>" . $e->getMessage() . "</p>";
    echo "<p style='font-family:Inter,sans-serif;'>Assurez-vous que MySQL est démarré (XAMPP > MySQL > Start).</p>";
}
?>
