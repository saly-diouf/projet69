<?php
require_once __DIR__ . '/../includes/header.php';

$db = getDB();
$etudes = $db->query("SELECT id, titre FROM etudes ORDER BY date_creation DESC")->fetchAll();
?>

<div class="page-header">
    <div>
        <h1>Rapports</h1>
        <p>Générez et consultez vos rapports d'étude</p>
    </div>
</div>

<div class="grid grid-2">
    <?php foreach ($etudes as $e): 
        $nb_rep = $db->query("SELECT COUNT(*) FROM respondents WHERE etude_id = {$e['id']} AND statut = 'termine'")->fetchColumn();
    ?>
    <div class="card">
        <div class="card-body">
            <h3 class="font-bold text-lg mb-2"><?= e($e['titre']) ?></h3>
            <p class="text-sm text-muted mb-4"><?= $nb_rep ?> répondants</p>
            <a href="<?= APP_URL ?>/rapports/generate.php?etude_id=<?= $e['id'] ?>" class="btn btn-primary">
                <i class="fas fa-file-pdf"></i> Générer le rapport
            </a>
        </div>
    </div>
    <?php endforeach; ?>
    
    <?php if (empty($etudes)): ?>
    <div class="card">
        <div class="empty-state">
            <i class="fas fa-file-pdf"></i>
            <h3>Aucune étude disponible</h3>
            <p>Créez d'abord une étude pour générer un rapport.</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
