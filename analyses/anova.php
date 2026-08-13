<?php
require_once __DIR__ . '/../includes/header.php';

$db = getDB();
$etude_id = (int) getParam('etude_id', 0);

$etudes = $db->query("SELECT id, titre FROM etudes ORDER BY date_creation DESC")->fetchAll();
if (!$etude_id && !empty($etudes)) {
    $etude_id = $etudes[0]['id'];
}

// Questions fermées pour le grouping, numériques pour la variable testée
$questions_grouping = [];
$questions_numeric = [];
if ($etude_id) {
    $stmt = $db->prepare("SELECT * FROM questions WHERE etude_id = ? AND type IN ('fermee_une','likert') ORDER BY ordre, id");
    $stmt->execute([$etude_id]);
    $questions_grouping = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT * FROM questions WHERE etude_id = ? AND type IN ('echelle','numerique','likert') ORDER BY ordre, id");
    $stmt->execute([$etude_id]);
    $questions_numeric = $stmt->fetchAll();
}

$group_var = (int) getParam('group_var', 0);
$test_var = (int) getParam('test_var', 0);
$test_type = getParam('test_type', 'anova');
$result = null;
$interpretation = '';

if ($group_var && $test_var && $group_var != $test_var) {
    // Récupérer les groupes
    $stmt = $db->prepare("SELECT * FROM questions WHERE id = ? AND etude_id = ?");
    $stmt->execute([$group_var, $etude_id]);
    $group_q = $stmt->fetch();

    $stmt = $db->prepare("SELECT * FROM questions WHERE id = ? AND etude_id = ?");
    $stmt->execute([$test_var, $etude_id]);
    $test_q = $stmt->fetch();

    if ($group_q && $test_q) {
        // Récupérer les valeurs de grouping
        $group_values = [];
        if (in_array($group_q['type'], ['fermee_une', 'likert'])) {
            $stmt = $db->prepare("SELECT r.respondent_id, rp.libelle 
                FROM reponses r 
                JOIN reponses_possibles rp ON r.reponse_possibles_id = rp.id 
                WHERE r.question_id = ? AND r.reponse_possibles_id IS NOT NULL");
            $stmt->execute([$group_var]);
            foreach ($stmt->fetchAll() as $row) {
                $group_values[$row['respondent_id']] = $row['libelle'];
            }
        }

        // Récupérer les valeurs numériques
        $numeric_values = [];
        if (in_array($test_q['type'], ['echelle', 'numerique'])) {
            $stmt = $db->prepare("SELECT respondent_id, valeur_numerique FROM reponses WHERE question_id = ? AND valeur_numerique IS NOT NULL");
            $stmt->execute([$test_var]);
            foreach ($stmt->fetchAll() as $row) {
                $numeric_values[$row['respondent_id']] = (float) $row['valeur_numerique'];
            }
        } elseif ($test_q['type'] == 'likert') {
            $stmt = $db->prepare("SELECT r.respondent_id, rp.valeur 
                FROM reponses r 
                JOIN reponses_possibles rp ON r.reponse_possibles_id = rp.id 
                WHERE r.question_id = ? AND r.reponse_possibles_id IS NOT NULL");
            $stmt->execute([$test_var]);
            foreach ($stmt->fetchAll() as $row) {
                $numeric_values[$row['respondent_id']] = (float) $row['valeur'];
            }
        }

        // Aligner
        $groupes = [];
        foreach ($group_values as $rid => $group) {
            if (isset($numeric_values[$rid])) {
                if (!isset($groupes[$group])) $groupes[$group] = [];
                $groupes[$group][] = $numeric_values[$rid];
            }
        }

        if (count($groupes) >= 2) {
            if ($test_type == 'anova') {
                $result = anova(array_values($groupes));
                if ($result) {
                    $result['groupes'] = $groupes;
                    $result['group_q'] = $group_q['libelle'];
                    $result['test_q'] = $test_q['libelle'];
                    $interpretation = interpreterAnova($result);
                }
            } else {
                // t-Student : comparer les deux premiers groupes
                $gkeys = array_keys($groupes);
                if (count($gkeys) >= 2) {
                    $result = testTStudent($groupes[$gkeys[0]], $groupes[$gkeys[1]]);
                    if ($result) {
                        $result['groupe1_name'] = $gkeys[0];
                        $result['groupe2_name'] = $gkeys[1];
                        $result['group_q'] = $group_q['libelle'];
                        $result['test_q'] = $test_q['libelle'];
                        $interpretation = "Le test t de Student compare les moyennes de « " . $gkeys[0] . " » et « " . $gkeys[1] . " ». ";
                        $interpretation .= "La différence des moyennes est de " . formatNumber($result['difference']) . " (t = " . formatNumber($result['t'], 3) . ", p = " . formatNumber($result['p_value'], 4) . "). ";
                        if ($result['p_value'] < 0.05) {
                            $interpretation .= "Cette différence est statistiquement significative (p < 0.05).";
                        } else {
                            $interpretation .= "Cette différence n'est pas statistiquement significative (p ≥ 0.05).";
                        }
                    }
                }
            }
        }
    }
}
?>

<div class="page-header">
    <div>
        <h1>ANOVA & Test t de Student</h1>
        <p>Comparaison de moyennes entre groupes</p>
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
                <label class="form-label">Variable de grouping</label>
                <select class="form-control" onchange="window.location.href='?etude_id=<?= $etude_id ?>&group_var='+this.value+'&test_var=<?= $test_var ?>&test_type=<?= $test_type ?>'">
                    <option value="0">— Choisir —</option>
                    <?php foreach ($questions_grouping as $q): ?>
                    <option value="<?= $q['id'] ?>" <?= $q['id'] == $group_var ? 'selected' : '' ?>><?= e(substr($q['libelle'], 0, 50)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Variable à tester</label>
                <select class="form-control" onchange="window.location.href='?etude_id=<?= $etude_id ?>&group_var=<?= $group_var ?>&test_var='+this.value+'&test_type=<?= $test_type ?>'">
                    <option value="0">— Choisir —</option>
                    <?php foreach ($questions_numeric as $q): ?>
                    <option value="<?= $q['id'] ?>" <?= $q['id'] == $test_var ? 'selected' : '' ?>><?= e(substr($q['libelle'], 0, 50)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="flex gap-2 mt-4">
            <a href="?etude_id=<?= $etude_id ?>&group_var=<?= $group_var ?>&test_var=<?= $test_var ?>&test_type=anova" class="btn <?= $test_type == 'anova' ? 'btn-primary' : 'btn-outline' ?> btn-sm">ANOVA</a>
            <a href="?etude_id=<?= $etude_id ?>&group_var=<?= $group_var ?>&test_var=<?= $test_var ?>&test_type=tstudent" class="btn <?= $test_type == 'tstudent' ? 'btn-primary' : 'btn-outline' ?> btn-sm">t de Student</a>
        </div>
    </div>
</div>

<?php if ($result): ?>
<div class="grid grid-2 mb-6">
    <div class="card">
        <div class="card-header">
            <h3><?= $test_type == 'anova' ? 'Résultats ANOVA' : 'Résultats test t' ?></h3>
        </div>
        <div class="card-body">
            <?php if ($test_type == 'anova'): ?>
                <div class="stat-result">
                    <div>
                        <div class="stat-value"><?= formatNumber($result['F'], 3) ?></div>
                        <div class="stat-label">F (statistique)</div>
                    </div>
                </div>
                <div class="stat-result">
                    <div>
                        <div class="stat-value"><?= $result['ddl_inter'] ?> / <?= $result['ddl_intra'] ?></div>
                        <div class="stat-label">DDL (inter / intra)</div>
                    </div>
                </div>
                <div class="stat-result">
                    <div>
                        <div class="stat-value"><?= formatNumber($result['p_value'], 4) ?></div>
                        <div class="stat-label">p-value</div>
                    </div>
                </div>
                <div class="stat-result">
                    <div>
                        <div class="stat-value"><?= formatNumber($result['ssb'], 2) ?> / <?= formatNumber($result['ssw'], 2) ?></div>
                        <div class="stat-label">SSB / SSW</div>
                    </div>
                </div>
            <?php else: ?>
                <div class="stat-result">
                    <div>
                        <div class="stat-value"><?= formatNumber($result['t'], 3) ?></div>
                        <div class="stat-label">t (statistique)</div>
                    </div>
                </div>
                <div class="stat-result">
                    <div>
                        <div class="stat-value"><?= $result['ddl'] ?></div>
                        <div class="stat-label">Degrés de liberté</div>
                    </div>
                </div>
                <div class="stat-result">
                    <div>
                        <div class="stat-value"><?= formatNumber($result['p_value'], 4) ?></div>
                        <div class="stat-label">p-value</div>
                    </div>
                </div>
                <div class="stat-result">
                    <div>
                        <div class="stat-value"><?= formatNumber($result['difference'], 3) ?></div>
                        <div class="stat-label">Différence des moyennes</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Statistiques par groupe</h3>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Groupe</th>
                        <th>n</th>
                        <th>Moyenne</th>
                        <th>Écart-type</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $groupes = $test_type == 'anova' ? $result['groupes'] : [$result['groupe1_name'] => [], $result['groupe2_name'] => []];
                    if ($test_type == 'tstudent') {
                        $groupes = [
                            $result['groupe1_name'] => array_fill(0, $result['n1'], 0),
                            $result['groupe2_name'] => array_fill(0, $result['n2'], 0),
                        ];
                    }
                    foreach ($groupes as $name => $vals): 
                        $moy = $test_type == 'anova' ? moyenne($vals) : ($name == $result['groupe1_name'] ? $result['moyenne1'] : $result['moyenne2']);
                        $std = $test_type == 'anova' ? ecartType($vals) : ($name == $result['groupe1_name'] ? $result['ecart_type1'] : $result['ecart_type2']);
                        $n = count($vals);
                    ?>
                    <tr>
                        <td class="font-semibold"><?= e($name) ?></td>
                        <td><?= $n ?></td>
                        <td><?= formatNumber($moy) ?></td>
                        <td><?= formatNumber($std) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="interpretation">
    <div class="interp-label">
        <i class="fas fa-lightbulb"></i> Interprétation automatique
    </div>
    <p><?= e($interpretation) ?></p>
</div>

<?php elseif ($group_var && $test_var): ?>
<div class="card">
    <div class="empty-state">
        <i class="fas fa-exclamation-triangle"></i>
        <h3>Pas assez de données</h3>
        <p>Assurez-vous qu'il y a au moins 2 groupes avec des réponses.</p>
    </div>
</div>
<?php elseif (empty($questions_grouping) || empty($questions_numeric)): ?>
<div class="card">
    <div class="empty-state">
        <i class="fas fa-clipboard-list"></i>
        <h3>Questions insuffisantes</h3>
        <p>Cette étude nécessite au moins une question fermée (grouping) et une question numérique (test).</p>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="empty-state">
        <i class="fas fa-square-root-variable"></i>
        <h3>Sélectionnez les variables</h3>
        <p>Choisissez une variable de grouping et une variable à tester.</p>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
