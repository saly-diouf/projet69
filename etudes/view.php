<?php
require_once __DIR__ . '/../includes/header.php';

$db = getDB();
$etude_id = (int) getParam('id', 0);

$stmt = $db->prepare("SELECT * FROM etudes WHERE id = ?");
$stmt->execute([$etude_id]);
$etude = $stmt->fetch();

if (!$etude) {
    redirect(APP_URL . "/etudes/list.php");
}

// Récupérer les sections et questions
$sections = $db->prepare("SELECT * FROM sections WHERE etude_id = ? ORDER BY ordre");
$sections->execute([$etude_id]);
$sections = $sections->fetchAll();

// Statistiques
$nb_respondents = $db->query("SELECT COUNT(*) FROM respondents WHERE etude_id = {$etude_id} AND statut = 'termine'")->fetchColumn();
$nb_invites = $db->query("SELECT COUNT(*) FROM respondents WHERE etude_id = {$etude_id}")->fetchColumn();
$nb_questions = $db->query("SELECT COUNT(*) FROM questions WHERE etude_id = {$etude_id}")->fetchColumn();
$nb_sections = count($sections);
$pct = $etude['taille_cible'] > 0 ? min(100, ($nb_respondents / $etude['taille_cible']) * 100) : 0;

// Changer le statut
if (isset($_GET['action']) && isset($_GET['statut'])) {
    $new_statut = $_GET['statut'];
    if (in_array($new_statut, ['brouillon', 'active', 'terminee'])) {
        $stmt = $db->prepare("UPDATE etudes SET statut = ? WHERE id = ?");
        $stmt->execute([$new_statut, $etude_id]);
        redirect(APP_URL . "/etudes/view.php?id=" . $etude_id);
    }
}

$created = isset($_GET['created']);
?>

<?php if ($created): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i>
    <div>L'étude a été créée avec succès. Vous pouvez maintenant construire votre questionnaire.</div>
</div>
<?php endif; ?>

<div class="breadcrumb">
    <a href="<?= APP_URL ?>/index.php">Tableau de bord</a>
    <span class="separator"><i class="fas fa-chevron-right"></i></span>
    <a href="<?= APP_URL ?>/etudes/list.php">Études</a>
    <span class="separator"><i class="fas fa-chevron-right"></i></span>
    <span><?= e($etude['titre']) ?></span>
</div>

<div class="page-header">
    <div>
        <h1><?= e($etude['titre']) ?></h1>
        <p><?= e($etude['description'] ?: 'Aucune description') ?></p>
    </div>
    <div class="flex gap-2">
        <?php if ($etude['statut'] == 'brouillon'): ?>
            <a href="<?= APP_URL ?>/etudes/view.php?id=<?= $etude_id ?>&action=status&statut=active" class="btn btn-success">
                <i class="fas fa-play"></i> Activer
            </a>
        <?php elseif ($etude['statut'] == 'active'): ?>
            <a href="<?= APP_URL ?>/etudes/view.php?id=<?= $etude_id ?>&action=status&statut=terminee" class="btn btn-secondary">
                <i class="fas fa-stop"></i> Clôturer
            </a>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/questionnaire/builder.php?etude_id=<?= $etude_id ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Questionnaire
        </a>
        <a href="<?= APP_URL ?>/distribution/index.php?etude_id=<?= $etude_id ?>" class="btn btn-outline">
            <i class="fas fa-share-alt"></i> Distribuer
        </a>
    </div>
</div>

<div class="grid grid-4 mb-6">
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fas fa-question-circle"></i></div>
        <div class="stat-info">
            <h3><?= $nb_questions ?></h3>
            <p>Questions</p>
        </div>
    </div>
    <div class="stat-card success">
        <div class="stat-icon success"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3><?= $nb_respondents ?> / <?= $etude['taille_cible'] ?></h3>
            <p>Répondants</p>
        </div>
    </div>
    <div class="stat-card info">
        <div class="stat-icon info"><i class="fas fa-percentage"></i></div>
        <div class="stat-info">
            <h3><?= formatNumber($pct, 1) ?>%</h3>
            <p>Objectif atteint</p>
        </div>
    </div>
    <div class="stat-card accent">
        <div class="stat-icon accent"><i class="fas fa-layer-group"></i></div>
        <div class="stat-info">
            <h3><?= $nb_sections ?></h3>
            <p>Sections</p>
        </div>
    </div>
</div>

<div class="progress mb-6" style="height: 12px;">
    <div class="progress-bar" style="width: <?= $pct ?>%"></div>
</div>

<!-- Tabs -->
<div class="tabs">
    <a href="<?= APP_URL ?>/etudes/view.php?id=<?= $etude_id ?>" class="tab active"><i class="fas fa-info-circle"></i> Aperçu</a>
    <a href="<?= APP_URL ?>/questionnaire/builder.php?etude_id=<?= $etude_id ?>" class="tab"><i class="fas fa-list-check"></i> Questionnaire</a>
    <a href="<?= APP_URL ?>/distribution/index.php?etude_id=<?= $etude_id ?>" class="tab"><i class="fas fa-share-alt"></i> Distribution</a>
    <a href="<?= APP_URL ?>/analyses/tris_a_plat.php?etude_id=<?= $etude_id ?>" class="tab"><i class="fas fa-chart-bar"></i> Analyses</a>
    <a href="<?= APP_URL ?>/rapports/generate.php?etude_id=<?= $etude_id ?>" class="tab"><i class="fas fa-file-pdf"></i> Rapport</a>
</div>

<!-- Détails de l'étude -->
<div class="grid grid-2">
    <div class="card">
        <div class="card-header">
            <h3>Informations générales</h3>
        </div>
        <div class="card-body">
            <table class="table" style="margin: 0;">
                <tbody>
                    <tr>
                        <td class="font-semibold" style="width: 40%;">Domaine</td>
                        <td><?= e($etude['domaine'] ?: '-') ?></td>
                    </tr>
                    <tr>
                        <td class="font-semibold">Statut</td>
                        <td><span class="badge badge-<?= statutColor($etude['statut']) ?>"><?= statutLabel($etude['statut']) ?></span></td>
                    </tr>
                    <tr>
                        <td class="font-semibold">Date de création</td>
                        <td><?= formatDate($etude['date_creation'], 'd/m/Y H:i') ?></td>
                    </tr>
                    <tr>
                        <td class="font-semibold">Date d'ouverture</td>
                        <td><?= formatDate($etude['date_ouverture']) ?></td>
                    </tr>
                    <tr>
                        <td class="font-semibold">Date de clôture</td>
                        <td><?= formatDate($etude['date_cloture']) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Paramètres d'échantillonnage</h3>
        </div>
        <div class="card-body">
            <table class="table" style="margin: 0;">
                <tbody>
                    <tr>
                        <td class="font-semibold" style="width: 40%;">Taille cible</td>
                        <td><?= $etude['taille_cible'] ?> répondants</td>
                    </tr>
                    <tr>
                        <td class="font-semibold">Méthode</td>
                        <td><?= methodeEchantillonnageLabel($etude['methode_echantillonnage']) ?></td>
                    </tr>
                    <tr>
                        <td class="font-semibold">Marge d'erreur</td>
                        <td><?= formatNumber($etude['marge_erreur']) ?>%</td>
                    </tr>
                    <tr>
                        <td class="font-semibold">Niveau de confiance</td>
                        <td><?= formatNumber($etude['niveau_confiance']) ?>%</td>
                    </tr>
                    <tr>
                        <td class="font-semibold">Taille calculée</td>
                        <td>
                            <?php 
                            $n_calc = calculerTailleEchantillon($etude['taille_cible'], $etude['marge_erreur'], $etude['niveau_confiance']);
                            ?>
                            <?= $n_calc ?> répondants (recommandé)
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
