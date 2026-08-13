<?php
require_once __DIR__ . '/../includes/header.php';

$db = getDB();
$etude_id = (int) getParam('etude_id', 0);

// Lister toutes les études pour le sélecteur
$etudes = $db->query("SELECT id, titre FROM etudes ORDER BY date_creation DESC")->fetchAll();

if (!$etude_id && !empty($etudes)) {
    $etude_id = $etudes[0]['id'];
}

$stmt = $db->prepare("SELECT * FROM etudes WHERE id = ?");
$stmt->execute([$etude_id]);
$etude = $stmt->fetch();

// Récupérer les questions de l'étude
$questions = [];
if ($etude_id) {
    $stmt = $db->prepare("SELECT * FROM questions WHERE etude_id = ? ORDER BY ordre, id");
    $stmt->execute([$etude_id]);
    $questions = $stmt->fetchAll();
}

$selected_q = (int) getParam('question_id', 0);
$analysis = null;
$interpretation = '';

if ($selected_q) {
    $stmt = $db->prepare("SELECT * FROM questions WHERE id = ? AND etude_id = ?");
    $stmt->execute([$selected_q, $etude_id]);
    $question = $stmt->fetch();

    if ($question) {
        if (in_array($question['type'], ['fermee_une', 'likert', 'echelle', 'numerique'])) {
            // Récupérer les réponses
            if ($question['type'] == 'fermee_une' || $question['type'] == 'likert') {
                $stmt = $db->prepare("SELECT rp.libelle, r.reponse_possibles_id 
                    FROM reponses r 
                    JOIN reponses_possibles rp ON r.reponse_possibles_id = rp.id 
                    WHERE r.question_id = ? AND r.reponse_possibles_id IS NOT NULL");
                $stmt->execute([$selected_q]);
                $raw = $stmt->fetchAll();
                $values = array_map(fn($r) => $r['libelle'], $raw);
            } elseif ($question['type'] == 'echelle' || $question['type'] == 'numerique') {
                $stmt = $db->prepare("SELECT valeur_numerique FROM reponses WHERE question_id = ? AND valeur_numerique IS NOT NULL");
                $stmt->execute([$selected_q]);
                $raw = $stmt->fetchAll();
                $values = array_map(fn($r) => (float) $r['valeur_numerique'], $raw);
            }

            if (!empty($values)) {
                $tri = triAPlat($values);
                $analysis = $tri;

                // Stats descriptives pour numériques
                if ($question['type'] == 'echelle' || $question['type'] == 'numerique') {
                    $analysis['moyenne'] = moyenne($values);
                    $analysis['mediane'] = mediane($values);
                    $analysis['ecart_type'] = ecartType($values);
                    $analysis['min'] = minimum($values);
                    $analysis['max'] = maximum($values);
                    $analysis['n'] = count($values);
                }

                $interpretation = interpreterTriAPlat($tri['data'], $tri['total'], $question['libelle']);
            }
        } elseif ($question['type'] == 'ouverte') {
            $stmt = $db->prepare("SELECT valeur_texte FROM reponses WHERE question_id = ? AND valeur_texte IS NOT NULL AND valeur_texte != ''");
            $stmt->execute([$selected_q]);
            $raw = $stmt->fetchAll();
            $analysis = ['type' => 'ouverte', 'reponses' => array_map(fn($r) => $r['valeur_texte'], $raw)];
        } elseif ($question['type'] == 'fermee_multiple') {
            $stmt = $db->prepare("SELECT r.valeur_multiple, rp.libelle 
                FROM reponses r 
                JOIN reponses_possibles rp ON 1=1 
                WHERE r.question_id = ? AND r.valeur_multiple IS NOT NULL
                ORDER BY rp.ordre");
            $stmt->execute([$selected_q]);
            // Récupérer toutes les options
            $opts = $db->prepare("SELECT * FROM reponses_possibles WHERE question_id = ? ORDER BY ordre");
            $opts->execute([$selected_q]);
            $opts = $opts->fetchAll();

            $stmt = $db->prepare("SELECT valeur_multiple FROM reponses WHERE question_id = ? AND valeur_multiple IS NOT NULL");
            $stmt->execute([$selected_q]);
            $raw = $stmt->fetchAll();

            $counts = [];
            foreach ($opts as $opt) {
                $counts[$opt['libelle']] = 0;
            }
            foreach ($raw as $r) {
                $selected = json_decode($r['valeur_multiple'], true);
                if (is_array($selected)) {
                    foreach ($selected as $opt_id) {
                        foreach ($opts as $opt) {
                            if ($opt['id'] == $opt_id) {
                                $counts[$opt['libelle']]++;
                            }
                        }
                    }
                }
            }
            $total = count($raw);
            $data = [];
            foreach ($counts as $lib => $cnt) {
                $data[] = ['valeur' => $lib, 'effectif' => $cnt, 'pourcentage' => $total > 0 ? ($cnt / $total) * 100 : 0];
            }
            $analysis = ['data' => $data, 'total' => $total];
            $interpretation = interpreterTriAPlat($data, $total, $question['libelle']);
        }
    }
}
?>

<div class="page-header">
    <div>
        <h1>Tris à plat</h1>
        <p>Effectifs et pourcentages pour chaque question</p>
    </div>
</div>

<!-- Sélecteur d'étude -->
<div class="card mb-6">
    <div class="card-body">
        <div class="form-row">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Étude</label>
                <select class="form-control" onchange="window.location.href='?etude_id='+this.value">
                    <?php foreach ($etudes as $e): ?>
                    <option value="<?= $e['id'] ?>" <?= $e['id'] == $etude_id ? 'selected' : '' ?>><?= e($e['titre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Question</label>
                <select class="form-control" onchange="window.location.href='?etude_id=<?= $etude_id ?>&question_id='+this.value">
                    <option value="0">— Choisir une question —</option>
                    <?php foreach ($questions as $q): ?>
                    <option value="<?= $q['id'] ?>" <?= $q['id'] == $selected_q ? 'selected' : '' ?>>
                        <?= e(substr($q['libelle'], 0, 60)) ?> (<?= typeQuestionLabel($q['type']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>

<?php if ($analysis): ?>
    <?php if (isset($analysis['reponses'])): ?>
        <!-- Question ouverte -->
        <div class="card">
            <div class="card-header">
                <h3>Réponses ouvertes (<?= count($analysis['reponses']) ?>)</h3>
            </div>
            <div class="card-body">
                <?php if (empty($analysis['reponses'])): ?>
                    <p class="text-muted text-center">Aucune réponse pour cette question.</p>
                <?php else: ?>
                    <?php foreach ($analysis['reponses'] as $rep): ?>
                    <div class="question-card" style="margin-bottom: 8px; padding: 14px;">
                        <i class="fas fa-quote-left text-primary text-sm"></i>
                        <span style="margin-left: 8px;"><?= e($rep) ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <!-- Graphique + tableau -->
        <div class="grid grid-2 mb-6">
            <div class="card">
                <div class="card-header">
                    <h3>Graphique</h3>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 320px;">
                        <canvas id="chart-tri-a-plat"></canvas>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3>Tableau des effectifs</h3>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Modalité</th>
                                <th>Effectif</th>
                                <th>Pourcentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($analysis['data'] as $item): ?>
                            <tr>
                                <td class="font-semibold"><?= e($item['valeur']) ?></td>
                                <td><?= $item['effectif'] ?></td>
                                <td><?= formatPercent($item['pourcentage']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr style="border-top: 2px solid var(--gray-200);">
                                <td class="font-bold">Total</td>
                                <td class="font-bold"><?= $analysis['total'] ?></td>
                                <td class="font-bold">100 %</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Stats descriptives (pour numériques) -->
        <?php if (isset($analysis['moyenne'])): ?>
        <div class="card mb-6">
            <div class="card-header">
                <h3>Statistiques descriptives</h3>
            </div>
            <div class="card-body">
                <div class="grid grid-4">
                    <div class="stat-result">
                        <div>
                            <div class="stat-value"><?= formatNumber($analysis['moyenne']) ?></div>
                            <div class="stat-label">Moyenne</div>
                        </div>
                    </div>
                    <div class="stat-result">
                        <div>
                            <div class="stat-value"><?= formatNumber($analysis['mediane']) ?></div>
                            <div class="stat-label">Médiane</div>
                        </div>
                    </div>
                    <div class="stat-result">
                        <div>
                            <div class="stat-value"><?= formatNumber($analysis['ecart_type']) ?></div>
                            <div class="stat-label">Écart-type</div>
                        </div>
                    </div>
                    <div class="stat-result">
                        <div>
                            <div class="stat-value"><?= $analysis['min'] ?> - <?= $analysis['max'] ?></div>
                            <div class="stat-label">Min - Max</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Interprétation -->
        <div class="interpretation">
            <div class="interp-label">
                <i class="fas fa-lightbulb"></i> Interprétation automatique
            </div>
            <p><?= e($interpretation) ?></p>
        </div>

        <script>
        const triLabels = <?= json_encode(array_map(fn($d) => (string)$d['valeur'], $analysis['data'])) ?>;
        const triData = <?= json_encode(array_map(fn($d) => (int)$d['effectif'], $analysis['data'])) ?>;
        createBarChart('chart-tri-a-plat', triLabels, triData, 'Effectifs');
        </script>
    <?php endif; ?>
<?php elseif ($selected_q == 0 && !empty($questions)): ?>
    <div class="card">
        <div class="empty-state">
            <i class="fas fa-chart-bar"></i>
            <h3>Sélectionnez une question</h3>
            <p>Choisissez une question dans le menu déroulant pour afficher son tri à plat.</p>
        </div>
    </div>
<?php elseif (empty($questions)): ?>
    <div class="card">
        <div class="empty-state">
            <i class="fas fa-clipboard-list"></i>
            <h3>Aucune question dans cette étude</h3>
            <p>Ajoutez d'abord des questions via le constructeur de questionnaire.</p>
            <a href="<?= APP_URL ?>/questionnaire/builder.php?etude_id=<?= $etude_id ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Construire le questionnaire
            </a>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
