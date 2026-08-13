<?php
require_once __DIR__ . '/../includes/header.php';

$db = getDB();
$etude_id = (int) getParam('etude_id', 0);

$etudes = $db->query("SELECT id, titre FROM etudes ORDER BY date_creation DESC")->fetchAll();
if (!$etude_id && !empty($etudes)) {
    $etude_id = $etudes[0]['id'];
}

$questions = [];
if ($etude_id) {
    $stmt = $db->prepare("SELECT * FROM questions WHERE etude_id = ? AND type IN ('echelle','numerique','likert') ORDER BY ordre, id");
    $stmt->execute([$etude_id]);
    $questions = $stmt->fetchAll();
}

$method = getParam('method', 'kmeans');
$k = (int) getParam('k', 3);
$result = null;
$interpretation = '';

if (count($questions) >= 2) {
    // Récupérer les données
    $all_values = [];
    $question_labels = [];
    foreach ($questions as $q) {
        $question_labels[] = substr($q['libelle'], 0, 30);
        $vals = [];
        if (in_array($q['type'], ['echelle', 'numerique'])) {
            $stmt = $db->prepare("SELECT respondent_id, valeur_numerique FROM reponses WHERE question_id = ? AND valeur_numerique IS NOT NULL");
            $stmt->execute([$q['id']]);
            foreach ($stmt->fetchAll() as $row) {
                $vals[$row['respondent_id']] = (float) $row['valeur_numerique'];
            }
        } elseif ($q['type'] == 'likert') {
            $stmt = $db->prepare("SELECT r.respondent_id, rp.valeur 
                FROM reponses r 
                JOIN reponses_possibles rp ON r.reponse_possibles_id = rp.id 
                WHERE r.question_id = ? AND r.reponse_possibles_id IS NOT NULL");
            $stmt->execute([$q['id']]);
            foreach ($stmt->fetchAll() as $row) {
                $vals[$row['respondent_id']] = (float) $row['valeur'];
            }
        }
        $all_values[$q['id']] = $vals;
    }

    $common_ids = null;
    foreach ($all_values as $vals) {
        $ids = array_keys($vals);
        if ($common_ids === null) {
            $common_ids = $ids;
        } else {
            $common_ids = array_intersect($common_ids, $ids);
        }
    }

    if ($common_ids && count($common_ids) >= 3) {
        $data = [];
        foreach ($common_ids as $rid) {
            $row = [];
            foreach ($all_values as $q_id => $vals) {
                $row[] = $vals[$rid];
            }
            $data[] = $row;
        }

        if ($method == 'kmeans') {
            $result = kmeans($data, min($k, count($data)));
            if ($result) {
                $result['question_labels'] = $question_labels;
                $interpretation = interpreterKmeans($result);
            }
        } else {
            $result = cah($data, min($k, count($data)));
            if ($result) {
                $result['question_labels'] = $question_labels;
                $interpretation = "La classification ascendante hiérarchique a identifié {$result['nb_clusters']} cluster(s). ";
                $tailles_str = implode(', ', array_map(fn($i, $t) => "Cluster " . ($i + 1) . " : {$t}", array_keys($result['tailles']), $result['tailles']));
                $interpretation .= "Répartition : {$tailles_str}.";
            }
        }
    }
}
?>

<div class="page-header">
    <div>
        <h1>Classification (K-means & CAH)</h1>
        <p>Segmentation des répondants en groupes homogènes</p>
    </div>
</div>

<div class="card mb-6">
    <div class="card-body">
        <div class="form-row-3">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Étude</label>
                <select class="form-control" onchange="window.location.href='?etude_id='+this.value">
                    <?php foreach ($etudes as $e): ?>
                    <option value="<?= $e['id'] ?>" <?= $e['id'] == $etude_id ? 'selected' : '' ?>><?= e($e['titre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Méthode</label>
                <select class="form-control" onchange="window.location.href='?etude_id=<?= $etude_id ?>&method='+this.value+'&k=<?= $k ?>'">
                    <option value="kmeans" <?= $method == 'kmeans' ? 'selected' : '' ?>>K-means</option>
                    <option value="cah" <?= $method == 'cah' ? 'selected' : '' ?>>CAH (Classification Ascendante Hiérarchique)</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Nombre de clusters (k)</label>
                <select class="form-control" onchange="window.location.href='?etude_id=<?= $etude_id ?>&method=<?= $method ?>&k='+this.value">
                    <?php for ($i = 2; $i <= 6; $i++): ?>
                    <option value="<?= $i ?>" <?= $i == $k ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
    </div>
</div>

<?php if ($result): ?>
<div class="grid grid-2 mb-6">
    <!-- Graphique des clusters -->
    <div class="card">
        <div class="card-header">
            <h3>Visualisation des clusters</h3>
        </div>
        <div class="card-body">
            <div class="chart-container" style="height: 350px;">
                <canvas id="chart-clusters"></canvas>
            </div>
        </div>
    </div>

    <!-- Profils des clusters -->
    <div class="card">
        <div class="card-header">
            <h3>Profils des clusters</h3>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Cluster</th>
                        <th>Taille</th>
                        <?php foreach ($result['question_labels'] as $ql): ?>
                        <th><?= e($ql) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $nb_clusters = $method == 'kmeans' ? $result['k'] : $result['nb_clusters'];
                    for ($c = 0; $c < $nb_clusters; $c++): 
                    ?>
                    <tr>
                        <td class="font-semibold">Cluster <?= $c + 1 ?></td>
                        <td><?= $result['tailles'][$c] ?? 0 ?></td>
                        <?php 
                        // Calculer la moyenne de chaque variable pour ce cluster
                        $membres = [];
                        for ($i = 0; $i < count($result['affectations']); $i++) {
                            if ($result['affectations'][$i] == $c) $membres[] = $i;
                        }
                        foreach ($result['question_labels'] as $j => $ql): 
                            $vals = [];
                            foreach ($membres as $m) {
                                // Pour K-means, on n'a pas les données brutes ici directement
                                // Utiliser les centroides si K-means
                            }
                            $val = '-';
                            if ($method == 'kmeans' && isset($result['centroids'][$c][$j])) {
                                $val = formatNumber($result['centroids'][$c][$j]);
                            }
                        ?>
                        <td><?= $val ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($method == 'kmeans'): ?>
<div class="card mb-6">
    <div class="card-header">
        <h3>Statistiques de classification</h3>
    </div>
    <div class="card-body">
        <div class="grid grid-3">
            <div class="stat-result">
                <div>
                    <div class="stat-value"><?= $result['nb_iter'] ?></div>
                    <div class="stat-label">Itérations</div>
                </div>
            </div>
            <div class="stat-result">
                <div>
                    <div class="stat-value"><?= formatNumber($result['inertie'], 2) ?></div>
                    <div class="stat-label">Inertie intra-classe</div>
                </div>
            </div>
            <div class="stat-result">
                <div>
                    <div class="stat-value"><?= $result['k'] ?></div>
                    <div class="stat-label">Nombre de clusters</div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="interpretation">
    <div class="interp-label">
        <i class="fas fa-lightbulb"></i> Interprétation automatique
    </div>
    <p><?= e($interpretation) ?></p>
</div>

<script>
const clusterAffectations = <?= json_encode($result['affectations']) ?>;
const clusterData = <?= json_encode(
    $method == 'kmeans' 
        ? array_map(fn($c) => [$c[0] ?? 0, $c[1] ?? 0], $result['centroids'] ?? [])
        : []
) ?>;

// Récupérer les coordonnées (utiliser ACP si possible, sinon les 2 premières variables)
const clusterGroups = [];
const nbClusters = <?= $method == 'kmeans' ? $result['k'] : $result['nb_clusters'] ?>;
const clusterColors = ['#4f46e5', '#ec4899', '#10b981', '#f59e0b', '#0ea5e9', '#8b5cf6'];

// Construire les groupes de points
// Nous utiliserons les données brutes pour les 2 premières dimensions
<?php
// Récupérer les données brutes pour le graphique
if (isset($common_ids) && $common_ids):
?>
const rawData = [
    <?php 
    $data_for_js = [];
    foreach ($common_ids as $rid) {
        $row = [];
        foreach ($all_values as $q_id => $vals) {
            $row[] = $vals[$rid];
        }
        $data_for_js[] = $row;
    }
    echo json_encode($data_for_js);
    ?>
];

for (let c = 0; c < nbClusters; c++) {
    const points = [];
    for (let i = 0; i < clusterAffectations.length; i++) {
        if (clusterAffectations[i] === c && rawData[i]) {
            points.push({ x: rawData[i][0], y: rawData[i][1] || 0 });
        }
    }
    clusterGroups.push(points);
}

createScatterChart('chart-clusters', {
    groups: clusterGroups,
    xLabel: <?= json_encode($result['question_labels'][0] ?? 'Variable 1') ?>,
    yLabel: <?= json_encode($result['question_labels'][1] ?? 'Variable 2') ?>,
});
<?php endif; ?>
</script>

<?php elseif (count($questions) < 2): ?>
<div class="card">
    <div class="empty-state">
        <i class="fas fa-clipboard-list"></i>
        <h3>Variables insuffisantes</h3>
        <p>La classification nécessite au moins 2 variables numériques.</p>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="empty-state">
        <i class="fas fa-object-group"></i>
        <h3>Pas assez de données</h3>
        <p>Au moins 3 répondants ayant répondu à toutes les questions sont nécessaires.</p>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
