<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/stats.php';
if (!isLoggedIn()) {
    redirect(APP_URL . '/landing.php');
}
$page = 'dashboard';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Statistiques globales (adaptées selon le rôle)
if (hasRole('admin')) {
    $nb_etudes = $db->query("SELECT COUNT(*) FROM etudes")->fetchColumn();
    $nb_actives = $db->query("SELECT COUNT(*) FROM etudes WHERE statut = 'active'")->fetchColumn();
} else if (hasRole('chercheur')) {
    $nb_etudes = $db->query("SELECT COUNT(*) FROM etudes WHERE user_id = " . (int)$current_user['id'])->fetchColumn();
    $nb_actives = $db->query("SELECT COUNT(*) FROM etudes WHERE user_id = " . (int)$current_user['id'] . " AND statut = 'active'")->fetchColumn();
} else {
    // Répondant : voir les études auxquelles il a participé
    $nb_etudes = $db->query("SELECT COUNT(DISTINCT etude_id) FROM respondents WHERE statut = 'termine'")->fetchColumn();
    $nb_actives = $db->query("SELECT COUNT(*) FROM etudes WHERE statut = 'active'")->fetchColumn();
}
$nb_respondents = $db->query("SELECT COUNT(*) FROM respondents WHERE statut = 'termine'")->fetchColumn();
$nb_questions = $db->query("SELECT COUNT(*) FROM questions")->fetchColumn();

// Études récentes
if (hasRole('admin')) {
    $stmt = $db->query("SELECT * FROM etudes ORDER BY date_creation DESC LIMIT 5");
} else if (hasRole('chercheur')) {
    $stmt = $db->prepare("SELECT * FROM etudes WHERE user_id = ? ORDER BY date_creation DESC LIMIT 5");
    $stmt->execute([$current_user['id']]);
} else {
    $stmt = $db->query("SELECT e.* FROM etudes e JOIN respondents r ON r.etude_id = e.id WHERE r.statut = 'termine' GROUP BY e.id ORDER BY e.date_creation DESC LIMIT 5");
}
$etudes_recentes = $stmt->fetchAll();

// Études par statut
$stmt = $db->query("SELECT statut, COUNT(*) as nb FROM etudes GROUP BY statut");
$statuts = $stmt->fetchAll();

// Réponses par jour (7 derniers jours)
$stmt = $db->query("SELECT DATE(date_reponse) as jour, COUNT(*) as nb 
    FROM reponses 
    WHERE date_reponse >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
    GROUP BY DATE(date_reponse) 
    ORDER BY jour");
$responses_jour = $stmt->fetchAll();
?>

<div class="page-header">
    <div>
        <h1>Tableau de bord</h1>
        <p>Vue d'ensemble de vos études de marché</p>
    </div>
</div>

<!-- Stat cards -->
<div class="grid grid-4 mb-6">
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fas fa-folder-open"></i></div>
        <div class="stat-info">
            <h3><?= $nb_etudes ?></h3>
            <p>Études totales</p>
        </div>
    </div>
    <div class="stat-card success">
        <div class="stat-icon success"><i class="fas fa-play-circle"></i></div>
        <div class="stat-info">
            <h3><?= $nb_actives ?></h3>
            <p>Études actives</p>
        </div>
    </div>
    <div class="stat-card info">
        <div class="stat-icon info"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3><?= $nb_respondents ?></h3>
            <p>Répondants</p>
        </div>
    </div>
    <div class="stat-card accent">
        <div class="stat-icon accent"><i class="fas fa-question-circle"></i></div>
        <div class="stat-info">
            <h3><?= $nb_questions ?></h3>
            <p>Questions créées</p>
        </div>
    </div>
</div>

<!-- Charts row -->
<div class="grid grid-2 mb-6">
    <div class="card">
        <div class="card-header">
            <h3>Réponses (7 derniers jours)</h3>
        </div>
        <div class="card-body">
            <div class="chart-container" style="height: 280px;">
                <canvas id="chart-responses"></canvas>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h3>Répartition par statut</h3>
        </div>
        <div class="card-body">
            <div class="chart-container" style="height: 280px;">
                <canvas id="chart-statuts"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Études récentes -->
<div class="card">
    <div class="card-header">
        <h3>Études récentes</h3>
        <a href="<?= APP_URL ?>/etudes/list.php" class="btn btn-outline btn-sm">Voir tout <i class="fas fa-arrow-right"></i></a>
    </div>
    <?php if (empty($etudes_recentes)): ?>
        <div class="empty-state">
            <i class="fas fa-folder-open"></i>
            <h3>Aucune étude pour le moment</h3>
            <p>Commencez par créer votre première étude de marché</p>
            <a href="<?= APP_URL ?>/etudes/create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Créer une étude
            </a>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Domaine</th>
                        <th>Statut</th>
                        <th>Créée le</th>
                        <th>Répondants</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($etudes_recentes as $etude): 
                        $nb_rep = $db->query("SELECT COUNT(*) FROM respondents WHERE etude_id = {$etude['id']} AND statut = 'termine'")->fetchColumn();
                    ?>
                    <tr>
                        <td class="font-semibold"><?= e($etude['titre']) ?></td>
                        <td><?= e($etude['domaine'] ?: '-') ?></td>
                        <td><span class="badge badge-<?= statutColor($etude['statut']) ?>"><?= statutLabel($etude['statut']) ?></span></td>
                        <td><?= formatDate($etude['date_creation']) ?></td>
                        <td><?= $nb_rep ?></td>
                        <td>
                            <a href="<?= APP_URL ?>/etudes/view.php?id=<?= $etude['id'] ?>" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i></a>
                            <a href="<?= APP_URL ?>/questionnaire/builder.php?etude_id=<?= $etude['id'] ?>" class="btn btn-outline btn-sm"><i class="fas fa-edit"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
// Chart: responses per day
const responsesData = <?= json_encode($responses_jour) ?>;
const jours = responsesData.map(d => {
    const date = new Date(d.jour);
    return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' });
});
const counts = responsesData.map(d => d.nb);

// Chart: statuts
const statutsData = <?= json_encode($statuts) ?>;
const statutLabels = statutsData.map(s => {
    const labels = { brouillon: 'Brouillon', active: 'Active', terminee: 'Terminée' };
    return labels[s.statut] || s.statut;
});
const statutCounts = statutsData.map(s => s.nb);

document.addEventListener('DOMContentLoaded', function() {
    createLineChart('chart-responses', jours.length > 0 ? jours : ['Aucune donnée'], counts.length > 0 ? counts : [0], 'Réponses');
    createPieChart('chart-statuts', statutLabels.length > 0 ? statutLabels : ['Aucune étude'], statutCounts.length > 0 ? statutCounts : [0]);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
