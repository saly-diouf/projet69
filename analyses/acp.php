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

$result = null;
$interpretation = '';

if (count($questions) >= 2) {
    // Récupérer les données pour toutes les questions numériques
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

    // Trouver les répondants qui ont répondu à toutes les questions
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
        // Construire la matrice de données
        $data = [];
        foreach ($common_ids as $rid) {
            $row = [];
            foreach ($all_values as $q_id => $vals) {
                $row[] = $vals[$rid];
            }
            $data[] = $row;
        }

        $result = acp($data, min(3, count($questions)));
        if ($result) {
            $result['question_labels'] = $question_labels;
            $interpretation = interpreterACP($result);
        }
    }
}
?>

<div class="page-header">
    <div>
        <h1>Analyse en Composantes Principales (ACP)</h1>
        <p>Réduction de dimension et visualisation des données</p>
    </div>
</div>

<div class="card mb-6">
    <div class="card-body">
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Étude</label>
            <select class="form-control" onchange="window.location.href='?etude_id='+this.value">
                <?php foreach ($etudes as $e): ?>
                <option value="<?= $e['id'] ?>" <?= $e['id'] == $etude_id ? 'selected' : '' ?>><?= e($e['titre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<?php if ($result): ?>
<div class="grid grid-2 mb-6">
    <!-- Graphique des individus -->
    <div class="card">
        <div class="card-header">
            <h3>Plan factoriel (CP1 × CP2)</h3>
        </div>
        <div class="card-body">
            <div class="chart-container" style="height: 350px;">
                <canvas id="chart-acp-individus"></canvas>
            </div>
        </div>
    </div>

    <!-- Valeurs propres -->
    <div class="card">
        <div class="card-header">
            <h3>Valeurs propres et variance expliquée</h3>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Composante</th>
                        <th>Valeur propre</th>
                        <th>Variance expliquée</th>
                        <th>Cumulé</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $cumul = 0;
                    foreach ($result['valeurs_propres'] as $i => $vp): 
                        $cumul += $result['variance_expliquee'][$i];
                    ?>
                    <tr>
                        <td class="font-semibold">CP<?= $i + 1 ?></td>
                        <td><?= formatNumber($vp, 4) ?></td>
                        <td><?= formatNumber($result['variance_expliquee'][$i], 1) ?>%</td>
                        <td><?= formatNumber($cumul, 1) ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="chart-container mt-4" style="height: 200px;">
                <canvas id="chart-valeurs-propres"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Cercle des corrélations -->
<div class="card mb-6">
    <div class="card-header">
        <h3>Cercle des corrélations</h3>
    </div>
    <div class="card-body">
        <div class="chart-container" style="height: 400px;">
            <canvas id="chart-cercle"></canvas>
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
// Graphique des individus
const acpCoords = <?= json_encode($result['coordonnees']) ?>;
const acpPoints = acpCoords.map(c => ({ x: c[0], y: c[1] }));
createScatterChart('chart-acp-individus', {
    points: acpPoints,
    xLabel: 'Composante 1 (<?= formatNumber($result['variance_expliquee'][0], 1) ?>%)',
    yLabel: 'Composante 2 (<?= formatNumber($result['variance_expliquee'][1] ?? 0, 1) ?>%)',
});

// Valeurs propres (scree plot)
const vpLabels = <?= json_encode(array_map(fn($i) => 'CP' . ($i + 1), range(0, count($result['valeurs_propres']) - 1))) ?>;
const vpData = <?= json_encode(array_map('floatval', $result['valeurs_propres'])) ?>;
createBarChart('chart-valeurs-propres', vpLabels, vpData, 'Valeur propre');

// Cercle des corrélations
const cercleCtx = document.getElementById('chart-cercle').getContext('2d');
const vecteurs = <?= json_encode($result['vecteurs_propres']) ?>;
const qLabels = <?= json_encode($result['question_labels']) ?>;

// Dessiner le cercle manuellement
function drawCercle() {
    const canvas = document.getElementById('chart-cercle');
    const ctx = canvas.getContext('2d');
    const w = canvas.width = canvas.offsetWidth;
    const h = canvas.height = canvas.offsetHeight;
    const cx = w / 2, cy = h / 2;
    const r = Math.min(w, h) / 2 - 60;

    ctx.clearRect(0, 0, w, h);

    // Axes
    ctx.strokeStyle = '#e5e7eb';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(0, cy); ctx.lineTo(w, cy);
    ctx.moveTo(cx, 0); ctx.lineTo(cx, h);
    ctx.stroke();

    // Cercle
    ctx.strokeStyle = '#d1d5db';
    ctx.beginPath();
    ctx.arc(cx, cy, r, 0, 2 * Math.PI);
    ctx.stroke();

    // Vecteurs
    const colors = ['#4f46e5', '#ec4899', '#10b981', '#f59e0b', '#0ea5e9', '#8b5cf6', '#ef4444', '#14b8a6'];
    for (let i = 0; i < vecteurs[0].length; i++) {
        const vx = vecteurs[0][i];
        const vy = vecteurs[1] ? vecteurs[1][i] : 0;
        const ex = cx + vx * r;
        const ey = cy - vy * r;

        ctx.strokeStyle = colors[i % colors.length];
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(cx, cy);
        ctx.lineTo(ex, ey);
        ctx.stroke();

        // Point
        ctx.fillStyle = colors[i % colors.length];
        ctx.beginPath();
        ctx.arc(ex, ey, 5, 0, 2 * Math.PI);
        ctx.fill();

        // Label
        ctx.fillStyle = '#374151';
        ctx.font = '12px Inter';
        ctx.fillText(qLabels[i], ex + 8, ey - 8);
    }

    // Labels des axes
    ctx.fillStyle = '#6b7280';
    ctx.font = '13px Inter';
    ctx.fillText('CP1 (→)', w - 50, cy - 5);
    ctx.fillText('CP2 (↑)', cx + 5, 15);
}
drawCercle();
window.addEventListener('resize', drawCercle);
</script>

<?php elseif (count($questions) < 2): ?>
<div class="card">
    <div class="empty-state">
        <i class="fas fa-clipboard-list"></i>
        <h3>Variables insuffisantes</h3>
        <p>L'ACP nécessite au moins 2 variables numériques ou d'échelle.</p>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="empty-state">
        <i class="fas fa-diagram-project"></i>
        <h3>Pas assez de données</h3>
        <p>Au moins 3 répondants ayant répondu à toutes les questions sont nécessaires.</p>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
