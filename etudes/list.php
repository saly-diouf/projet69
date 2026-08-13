<?php
$page = 'etudes';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin', 'chercheur']);

$db = getDB();
if (hasRole('admin')) {
    $stmt = $db->query("SELECT * FROM etudes ORDER BY date_creation DESC");
} else {
    $stmt = $db->prepare("SELECT * FROM etudes WHERE user_id = ? ORDER BY date_creation DESC");
    $stmt->execute([$current_user['id']]);
}
$etudes = $stmt->fetchAll();
?>

<div class="page-header">
    <div>
        <h1>Mes études de marché</h1>
        <p>Gérez toutes vos études en un seul endroit</p>
    </div>
    <a href="<?= APP_URL ?>/etudes/create.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Nouvelle étude
    </a>
</div>

<?php if (empty($etudes)): ?>
    <div class="card">
        <div class="empty-state">
            <i class="fas fa-folder-open"></i>
            <h3>Aucune étude pour le moment</h3>
            <p>Créez votre première étude de marché pour commencer</p>
            <a href="<?= APP_URL ?>/etudes/create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Créer une étude
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="grid grid-3">
        <?php foreach ($etudes as $etude): 
            $nb_rep = $db->query("SELECT COUNT(*) FROM respondents WHERE etude_id = {$etude['id']} AND statut = 'termine'")->fetchColumn();
            $nb_q = $db->query("SELECT COUNT(*) FROM questions WHERE etude_id = {$etude['id']}")->fetchColumn();
            $pct = $etude['taille_cible'] > 0 ? min(100, ($nb_rep / $etude['taille_cible']) * 100) : 0;
        ?>
        <div class="card fade-in">
            <div class="card-body">
                <div class="flex items-center justify-between mb-2">
                    <span class="badge badge-<?= statutColor($etude['statut']) ?>">
                        <?= statutLabel($etude['statut']) ?>
                    </span>
                    <span class="text-sm text-muted"><?= formatDate($etude['date_creation']) ?></span>
                </div>
                <h3 class="font-bold text-lg mb-2"><?= e($etude['titre']) ?></h3>
                <p class="text-sm text-muted mb-4" style="min-height: 40px;"><?= e($etude['description'] ?: 'Aucune description') ?></p>
                
                <div class="flex gap-4 mb-4">
                    <div>
                        <div class="text-sm text-muted">Questions</div>
                        <div class="font-bold text-lg"><?= $nb_q ?></div>
                    </div>
                    <div>
                        <div class="text-sm text-muted">Répondants</div>
                        <div class="font-bold text-lg"><?= $nb_rep ?> / <?= $etude['taille_cible'] ?></div>
                    </div>
                </div>

                <div class="progress mb-4">
                    <div class="progress-bar" style="width: <?= $pct ?>%"></div>
                </div>
                <div class="text-sm text-muted mb-4"><?= formatNumber($pct, 1) ?>% de l'objectif</div>

                <div class="flex gap-2">
                    <a href="<?= APP_URL ?>/etudes/view.php?id=<?= $etude['id'] ?>" class="btn btn-primary btn-sm flex-1" style="justify-content:center;">
                        <i class="fas fa-eye"></i> Ouvrir
                    </a>
                    <a href="<?= APP_URL ?>/questionnaire/builder.php?etude_id=<?= $etude['id'] ?>" class="btn btn-outline btn-sm" title="Questionnaire">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="<?= APP_URL ?>/distribution/index.php?etude_id=<?= $etude['id'] ?>" class="btn btn-outline btn-sm" title="Distribuer">
                        <i class="fas fa-share-alt"></i>
                    </a>
                    <a href="<?= APP_URL ?>/etudes/delete.php?id=<?= $etude['id'] ?>" class="btn btn-danger btn-sm confirm-delete" title="Supprimer">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
