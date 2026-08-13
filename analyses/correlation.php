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

$var1_id = (int) getParam('var1', 0);
$var2_id = (int) getParam('var2', 0);
$method = getParam('method', 'pearson');
$result = null;
$interpretation = '';

if ($var1_id && $var2_id && $var1_id != $var2_id) {
    function getNumericValues($db, $question_id, $etude_id) {
        $stmt = $db->prepare("SELECT * FROM questions WHERE id = ? AND etude_id = ?");
        $stmt->execute([$question_id, $etude_id]);
        $q = $stmt->fetch();
        if (!$q) return null;

        $values = [];
        if (in_array($q['type'], ['echelle', 'numerique'])) {
            $stmt = $db->prepare("SELECT respondent_id, valeur_numerique FROM reponses WHERE question_id = ? AND valeur_numerique IS NOT NULL");
            $stmt->execute([$question_id]);
            foreach ($stmt->fetchAll() as $row) {
                $values[$row['respondent_id']] = (float) $row['valeur_numerique'];
            }
        } elseif ($q['type'] == 'likert') {
            $stmt = $db->prepare("SELECT r.respondent_id, rp.valeur 
                FROM reponses r 
                JOIN reponses_possibles rp ON r.reponse_possibles_id = rp.id 
                WHERE r.question_id = ? AND r.reponse_possibles_id IS NOT NULL");
            $stmt->execute([$question_id]);
            foreach ($stmt->fetchAll() as $row) {
                $values[$row['respondent_id']] = (float) $row['valeur'];
            }
        }
        return ['values' => $values, 'question' => $q];
    }

    $data1 = getNumericValues($db, $var1_id, $etude_id);
    $data2 = getNumericValues($db, $var2_id, $etude_id);

    if ($data1 && $data2) {
        $common_ids = array_intersect_key($data1['values'], $data2['values']);
        $x = [];
        $y = [];
        foreach ($common_ids as $rid => $val) {
            $x[] = $data1['values'][$rid];
            $y[] = $data2['values'][$rid];
        }

        if (count($x) >= 3) {
            if ($method == 'pearson') {
                $result = correlationPearson($x, $y);
                $interpretation = interpreterCorrelation($result, 'Pearson');
            } else {
                $result = correlationSpearman($x, $y);
                $interpretation = interpreterCorrelation($result, 'Spearman');
            }
            $result['question1'] = $data1['question']['libelle'];
            $result['question2'] = $data2['question']['libelle'];
            $result['x'] = $x;
            $result['y'] = $y;
        }
    }
}
?>

<div class="page-header">
    <div>
        <h1>Corrélations (Pearson & Spearman)</h1>
        <p>Mesure de la relation entre deux variables numériques</p>
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
                <label class="form-label">Variable X</label>
                <select class="form-control" onchange="window.location.href='?etude_id=<?= $etude_id ?>&var1='+this.value+'&var2=<?= $var2_id ?>&method=<?= $method ?>'">
                    <option value="0">— Choisir —</option>
                    <?php foreach ($questions as $q): ?>
                    <option value="<?= $q['id'] ?>" <?= $q['id'] == $var1_id ? 'selected' : '' ?>><?= e(substr($q['libelle'], 0, 50)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Variable Y</label>
                <select class="form-control" onchange="window.location.href='?etude_id=<?= $etude_id ?>&var1=<?= $var1_id ?>&var2='+this.value+'&method=<?= $method ?>'">
                    <option value="0">— Choisir —</option>
                    <?php foreach ($questions as $q): ?>
                    <option value="<?= $q['id'] ?>" <?= $q['id'] == $var2_id ? 'selected' : '' ?>><?= e(substr($q['libelle'], 0, 50)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="flex gap-2 mt-4">
            <a href="?etude_id=<?= $etude_id ?>&var1=<?= $var1_id ?>&var2=<?= $var2_id ?>&method=pearson" class="btn <?= $method == 'pearson' ? 'btn-primary' : 'btn-outline' ?> btn-sm">Pearson</a>
            <a href="?etude_id=<?= $etude_id ?>&var1=<?= $var1_id ?>&var2=<?= $var2_id ?>&method=spearman" class="btn <?= $method == 'spearman' ? 'btn-primary' : 'btn-outline' ?> btn-sm">Spearman</a>
        </div>
    </div>
</div>

<?php if ($result): ?>
<div class="grid grid-2 mb-6">
    <div class="card">
        <div class="card-header">
            <h3>Nuage de points</h3>
        </div>
        <div class="card-body">
            <div class="chart-container" style="height: 320px;">
                <canvas id="chart-scatter"></canvas>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Résultats du test</h3>
        </div>
        <div class="card-body">
            <div class="stat-result">
                <div>
                    <div class="stat-value"><?= formatNumber($result['r'], 4) ?></div>
                    <div class="stat-label">Coefficient de corrélation (r)</div>
                </div>
            </div>
            <div class="stat-result">
                <div>
                    <div class="stat-value"><?= $result['n'] ?></div>
                    <div class="stat-label">Nombre d'observations</div>
                </div>
            </div>
            <div class="stat-result">
                <div>
                    <div class="stat-value"><?= formatNumber($result['t'], 3) ?></div>
                    <div class="stat-label">t (statistique)</div>
                </div>
            </div>
            <div class="stat-result">
                <div>
                    <div class="stat-value"><?= formatNumber($result['p_value'], 4) ?></div>
                    <div class="stat-label">p-value</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="interpretation">
    <div class="interp-label">
        <i class="fas fa-lightbulb"></i> Interprétation automatique
    </div>
    <p><?= e($interpretation) ?></p>
</div>

<script>
const scatterData = {
    points: [
        <?php for ($i = 0; $i < count($result['x']); $i++): ?>
        { x: <?= $result['x'][$i] ?>, y: <?= $result['y'][$i] ?> },
        <?php endfor; ?>
    ],
    xLabel: <?= json_encode(substr($result['question1'], 0, 40)) ?>,
    yLabel: <?= json_encode(substr($result['question2'], 0, 40)) ?>,
};
createScatterChart('chart-scatter', scatterData);
</script>

<?php elseif ($var1_id && $var2_id): ?>
<div class="card">
    <div class="empty-state">
        <i class="fas fa-exclamation-triangle"></i>
        <h3>Pas assez de données</h3>
        <p>Au moins 3 observations communes sont nécessaires.</p>
    </div>
</div>
<?php elseif (count($questions) < 2): ?>
<div class="card">
    <div class="empty-state">
        <i class="fas fa-clipboard-list"></i>
        <h3>Questions insuffisantes</h3>
        <p>Au moins 2 questions numériques ou d'échelle sont requises.</p>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="empty-state">
        <i class="fas fa-link"></i>
        <h3>Sélectionnez deux variables</h3>
        <p>Choisissez deux variables numériques pour calculer leur corrélation.</p>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
