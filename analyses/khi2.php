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
    $stmt = $db->prepare("SELECT * FROM questions WHERE etude_id = ? AND type IN ('fermee_une','likert','echelle','numerique') ORDER BY ordre, id");
    $stmt->execute([$etude_id]);
    $questions = $stmt->fetchAll();
}

$var1_id = (int) getParam('var1', 0);
$var2_id = (int) getParam('var2', 0);
$result = null;
$khi2_result = null;
$interpretation = '';

if ($var1_id && $var2_id && $var1_id != $var2_id) {
    function getQuestionValuesKhi2($db, $question_id, $etude_id) {
        $stmt = $db->prepare("SELECT * FROM questions WHERE id = ? AND etude_id = ?");
        $stmt->execute([$question_id, $etude_id]);
        $q = $stmt->fetch();
        if (!$q) return null;

        $values = [];
        if (in_array($q['type'], ['fermee_une', 'likert'])) {
            $stmt = $db->prepare("SELECT r.respondent_id, rp.libelle 
                FROM reponses r 
                JOIN reponses_possibles rp ON r.reponse_possibles_id = rp.id 
                WHERE r.question_id = ? AND r.reponse_possibles_id IS NOT NULL");
            $stmt->execute([$question_id]);
            foreach ($stmt->fetchAll() as $row) {
                $values[$row['respondent_id']] = $row['libelle'];
            }
        } elseif ($q['type'] == 'echelle' || $q['type'] == 'numerique') {
            $stmt = $db->prepare("SELECT respondent_id, valeur_numerique FROM reponses WHERE question_id = ? AND valeur_numerique IS NOT NULL");
            $stmt->execute([$question_id]);
            foreach ($stmt->fetchAll() as $row) {
                $values[$row['respondent_id']] = (string) $row['valeur_numerique'];
            }
        }
        return ['values' => $values, 'question' => $q];
    }

    $data1 = getQuestionValuesKhi2($db, $var1_id, $etude_id);
    $data2 = getQuestionValuesKhi2($db, $var2_id, $etude_id);

    if ($data1 && $data2) {
        $common_ids = array_intersect_key($data1['values'], $data2['values']);
        $var1_vals = [];
        $var2_vals = [];
        foreach ($common_ids as $rid => $val) {
            $var1_vals[] = $data1['values'][$rid];
            $var2_vals[] = $data2['values'][$rid];
        }

        if (count($var1_vals) > 0) {
            $result = triCroise($var1_vals, $var2_vals);
            $result['question1'] = $data1['question']['libelle'];
            $result['question2'] = $data2['question']['libelle'];

            $khi2_result = testKhi2($result['tableau'], $result['lignes'], $result['colonnes']);
            $interpretation = interpreterKhi2($khi2_result);
        }
    }
}
?>

<div class="page-header">
    <div>
        <h1>Test du Khi²</h1>
        <p>Test d'indépendance entre deux variables qualitatives — χ² = Σ (O − E)² / E</p>
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
                <label class="form-label">Variable 1 (lignes)</label>
                <select class="form-control" onchange="window.location.href='?etude_id=<?= $etude_id ?>&var1='+this.value+'&var2=<?= $var2_id ?>'">
                    <option value="0">— Choisir —</option>
                    <?php foreach ($questions as $q): ?>
                    <option value="<?= $q['id'] ?>" <?= $q['id'] == $var1_id ? 'selected' : '' ?>><?= e(substr($q['libelle'], 0, 50)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Variable 2 (colonnes)</label>
                <select class="form-control" onchange="window.location.href='?etude_id=<?= $etude_id ?>&var1=<?= $var1_id ?>&var2='+this.value">
                    <option value="0">— Choisir —</option>
                    <?php foreach ($questions as $q): ?>
                    <option value="<?= $q['id'] ?>" <?= $q['id'] == $var2_id ? 'selected' : '' ?>><?= e(substr($q['libelle'], 0, 50)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>

<?php if ($result): ?>
<div class="grid grid-2 mb-6">
    <div class="card">
        <div class="card-header">
            <h3>Tableau de contingence (observés)</h3>
        </div>
        <div class="card-body" style="overflow-x:auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th><?= e($result['question1']) ?> \ <?= e($result['question2']) ?></th>
                        <?php foreach ($result['colonnes'] as $col): ?>
                        <th class="text-center"><?= e($col) ?></th>
                        <?php endforeach; ?>
                        <th class="text-center">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result['lignes'] as $ligne): 
                        $total_ligne = array_sum($result['tableau'][$ligne]);
                    ?>
                    <tr>
                        <td class="font-semibold"><?= e($ligne) ?></td>
                        <?php foreach ($result['colonnes'] as $col): ?>
                        <td class="text-center"><?= $result['tableau'][$ligne][$col] ?></td>
                        <?php endforeach; ?>
                        <td class="text-center font-bold"><?= $total_ligne ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="border-top: 2px solid var(--gray-200);">
                        <td class="font-bold">Total</td>
                        <?php foreach ($result['colonnes'] as $col): 
                            $total_col = 0;
                            foreach ($result['lignes'] as $ligne) $total_col += $result['tableau'][$ligne][$col];
                        ?>
                        <td class="text-center font-bold"><?= $total_col ?></td>
                        <?php endforeach; ?>
                        <td class="text-center font-bold"><?= $khi2_result['total_general'] ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Tableau des effectifs attendus (E)</h3>
        </div>
        <div class="card-body" style="overflow-x:auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Attendu</th>
                        <?php foreach ($result['colonnes'] as $col): ?>
                        <th class="text-center"><?= e($col) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result['lignes'] as $ligne): ?>
                    <tr>
                        <td class="font-semibold"><?= e($ligne) ?></td>
                        <?php foreach ($result['colonnes'] as $col): ?>
                        <td class="text-center"><?= formatNumber($khi2_result['tableau_attendu'][$ligne][$col], 2) ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header">
        <h3>Résultats du test du Khi²</h3>
    </div>
    <div class="card-body">
        <div class="grid grid-4">
            <div class="stat-result">
                <div>
                    <div class="stat-value"><?= formatNumber($khi2_result['khi2'], 3) ?></div>
                    <div class="stat-label">χ² (Khi²)</div>
                </div>
            </div>
            <div class="stat-result">
                <div>
                    <div class="stat-value"><?= $khi2_result['ddl'] ?></div>
                    <div class="stat-label">Degrés de liberté</div>
                </div>
            </div>
            <div class="stat-result">
                <div>
                    <div class="stat-value"><?= formatNumber($khi2_result['p_value'], 4) ?></div>
                    <div class="stat-label">p-value</div>
                </div>
            </div>
            <div class="stat-result">
                <div>
                    <div class="stat-value"><?= formatNumber($khi2_result['v_cramer'], 3) ?></div>
                    <div class="stat-label">V de Cramer</div>
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

<?php elseif ($var1_id && $var2_id): ?>
<div class="card">
    <div class="empty-state">
        <i class="fas fa-exclamation-triangle"></i>
        <h3>Pas assez de données</h3>
        <p>Aucune réponse commune trouvée pour ces deux variables.</p>
    </div>
</div>
<?php elseif (empty($questions)): ?>
<div class="card">
    <div class="empty-state">
        <i class="fas fa-clipboard-list"></i>
        <h3>Aucune question exploitable</h3>
        <p>Cette étude ne contient pas de questions de type fermé ou échelle.</p>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="empty-state">
        <i class="fas fa-calculator"></i>
        <h3>Sélectionnez deux variables</h3>
        <p>Choisissez deux questions pour effectuer le test d'indépendance du Khi².</p>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
