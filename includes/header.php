<?php
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/stats.php';
}

$page = $page ?? '';
$pageTitle = $pageTitle ?? 'Tableau de bord';

// Exiger une authentification pour toutes les pages protégées
requireLogin();
$current_user = currentUser();
$current_role = currentRole();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="<?= APP_URL ?>/assets/js/main.js"></script>
</head>
<body>
<div class="app-layout">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="logo"><i class="fas fa-chart-pie" style="color:white;"></i></div>
            <div class="brand-text">
                MarketStudy Pro
                <small>Études de marché</small>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-section">
                <div class="sidebar-section-title">Principal</div>
                <a href="<?= APP_URL ?>/index.php" class="sidebar-link <?= $page == 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-gauge-high"></i> Tableau de bord
                </a>
                <?php if (hasRole(['admin', 'chercheur'])): ?>
                <a href="<?= APP_URL ?>/etudes/list.php" class="sidebar-link <?= $page == 'etudes' ? 'active' : '' ?>">
                    <i class="fas fa-folder-open"></i> Mes études
                </a>
                <a href="<?= APP_URL ?>/etudes/create.php" class="sidebar-link <?= $page == 'create' ? 'active' : '' ?>">
                    <i class="fas fa-plus-circle"></i> Nouvelle étude
                </a>
                <?php endif; ?>
            </div>
            <?php if (hasRole(['admin', 'chercheur'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Analyses</div>
                <a href="<?= APP_URL ?>/analyses/tris_a_plat.php" class="sidebar-link <?= $page == 'tris_a_plat' ? 'active' : '' ?>">
                    <i class="fas fa-list-ol"></i> Tris à plat
                </a>
                <a href="<?= APP_URL ?>/analyses/tris_croises.php" class="sidebar-link <?= $page == 'tris_croises' ? 'active' : '' ?>">
                    <i class="fas fa-table-cells"></i> Tris croisés
                </a>
                <a href="<?= APP_URL ?>/analyses/khi2.php" class="sidebar-link <?= $page == 'khi2' ? 'active' : '' ?>">
                    <i class="fas fa-calculator"></i> Test du Khi²
                </a>
                <a href="<?= APP_URL ?>/analyses/anova.php" class="sidebar-link <?= $page == 'anova' ? 'active' : '' ?>">
                    <i class="fas fa-square-root-variable"></i> ANOVA & t-Student
                </a>
                <a href="<?= APP_URL ?>/analyses/correlation.php" class="sidebar-link <?= $page == 'correlation' ? 'active' : '' ?>">
                    <i class="fas fa-link"></i> Corrélations
                </a>
                <a href="<?= APP_URL ?>/analyses/acp.php" class="sidebar-link <?= $page == 'acp' ? 'active' : '' ?>">
                    <i class="fas fa-diagram-project"></i> ACP
                </a>
                <a href="<?= APP_URL ?>/analyses/classification.php" class="sidebar-link <?= $page == 'classification' ? 'active' : '' ?>">
                    <i class="fas fa-object-group"></i> Classification
                </a>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Outils</div>
                <a href="<?= APP_URL ?>/echantillonnage.php" class="sidebar-link <?= $page == 'echantillonnage' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> Échantillonnage
                </a>
                <a href="<?= APP_URL ?>/rapports/list.php" class="sidebar-link <?= $page == 'rapports' ? 'active' : '' ?>">
                    <i class="fas fa-file-pdf"></i> Rapports
                </a>
            </div>
            <?php endif; ?>
            <?php if (hasRole('admin')): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Administration</div>
                <a href="<?= APP_URL ?>/admin/users.php" class="sidebar-link <?= $page == 'users' ? 'active' : '' ?>">
                    <i class="fas fa-users-gear"></i> Utilisateurs
                </a>
            </div>
            <?php endif; ?>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Documentation</div>
                <a href="<?= APP_URL ?>/generate_docs.php" class="sidebar-link <?= $page == 'docs' ? 'active' : '' ?>">
                    <i class="fas fa-file-word"></i> Documents Word
                </a>
            </div>
        </nav>
    </aside>

    <!-- Main content -->
    <div class="main-content">
        <header class="top-header">
            <h1 class="page-title"><?= e($pageTitle) ?></h1>
            <div class="header-actions">
                <?php if (hasRole(['admin', 'chercheur'])): ?>
                <a href="<?= APP_URL ?>/etudes/create.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Nouvelle étude
                </a>
                <?php endif; ?>
                <div style="display: flex; align-items: center; gap: 8px; padding-left: 16px; border-left: 1px solid var(--gray-200);">
                    <div style="text-align: right;">
                        <div style="font-size: 13px; font-weight: 600; color: var(--gray-700);"><?= e($current_user['prenom'] . ' ' . $current_user['nom']) ?></div>
                        <div style="font-size: 11px; color: var(--gray-400);"><?= roleLabel($current_role) ?></div>
                    </div>
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--accent)); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 14px;">
                        <?= strtoupper(substr($current_user['prenom'], 0, 1) . substr($current_user['nom'], 0, 1)) ?>
                    </div>
                    <a href="<?= APP_URL ?>/auth/logout.php" class="btn btn-outline btn-sm" title="Déconnexion">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </header>
        <main class="content-area">
