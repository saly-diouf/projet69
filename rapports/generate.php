<?php
require_once __DIR__ . '/../includes/header.php';

$db = getDB();
$etude_id = (int) getParam('etude_id', 0);

$etudes = $db->query("SELECT id, titre FROM etudes ORDER BY date_creation DESC")->fetchAll();
if (!$etude_id && !empty($etudes)) {
    $etude_id = $etudes[0]['id'];
}

$stmt = $db->prepare("SELECT * FROM etudes WHERE id = ?");
$stmt->execute([$etude_id]);
$etude = $stmt->fetch();

// Récupérer toutes les questions
$questions = [];
$sections = [];
if ($etude_id) {
    $stmt = $db->prepare("SELECT * FROM sections WHERE etude_id = ? ORDER BY ordre, id");
    $stmt->execute([$etude_id]);
    $sections = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT * FROM questions WHERE etude_id = ? ORDER BY ordre, id");
    $stmt->execute([$etude_id]);
    $questions = $stmt->fetchAll();
}

$nb_respondents = $db->query("SELECT COUNT(*) FROM respondents WHERE etude_id = {$etude_id} AND statut = 'termine'")->fetchColumn();
$nb_questions = count($questions);
$nb_sections = count($sections);
?>

<div class="page-header">
    <div>
        <h1>Génération du rapport d'étude</h1>
        <p><?= e($etude['titre'] ?? '') ?></p>
    </div>
    <div class="flex gap-2">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Imprimer / PDF
        </button>
    </div>
</div>

<?php if (!$etude): ?>
<div class="card">
    <div class="empty-state">
        <i class="fas fa-folder-open"></i>
        <h3>Aucune étude sélectionnée</h3>
    </div>
</div>
<?php else: ?>

<!-- Sélecteur d'étude -->
<div class="card mb-6" style="display:none;" id="study-selector">
    <div class="card-body">
        <select class="form-control" onchange="window.location.href='?etude_id='+this.value">
            <?php foreach ($etudes as $e): ?>
            <option value="<?= $e['id'] ?>" <?= $e['id'] == $etude_id ? 'selected' : '' ?>><?= e($e['titre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<!-- Rapport -->
<div class="card mb-6" id="rapport">
    <div class="card-body" style="padding: 40px;">
        <!-- En-tête du rapport -->
        <div style="text-align: center; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 3px solid var(--primary);">
            <div style="font-size: 12px; color: var(--gray-500); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 8px;">Rapport d'étude de marché</div>
            <h1 style="font-size: 28px; font-weight: 700; color: var(--gray-900); margin-bottom: 8px;"><?= e($etude['titre']) ?></h1>
            <p style="color: var(--gray-500);"><?= e($etude['domaine'] ?: '') ?></p>
            <p style="color: var(--gray-400); font-size: 13px; margin-top: 8px;">Généré le <?= formatDate(date('Y-m-d H:i:s'), 'd/m/Y à H:i') ?></p>
        </div>

        <!-- Synthèse -->
        <h2 style="font-size: 20px; font-weight: 700; color: var(--gray-900); margin-bottom: 16px; padding-bottom: 8px; border-bottom: 2px solid var(--gray-200);">
            1. Synthèse de l'étude
        </h2>
        <p style="font-size: 14px; color: var(--gray-700); line-height: 1.8; margin-bottom: 20px;"><?= e($etude['description'] ?: 'Aucune description fournie.') ?></p>

        <table style="width: 100%; margin-bottom: 30px; border-collapse: collapse;">
            <tr>
                <td style="padding: 10px; border: 1px solid var(--gray-200); font-weight: 600; background: var(--gray-50); width: 30%;">Statut</td>
                <td style="padding: 10px; border: 1px solid var(--gray-200);"><?= statutLabel($etude['statut']) ?></td>
                <td style="padding: 10px; border: 1px solid var(--gray-200); font-weight: 600; background: var(--gray-50);">Date de création</td>
                <td style="padding: 10px; border: 1px solid var(--gray-200);"><?= formatDate($etude['date_creation']) ?></td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid var(--gray-200); font-weight: 600; background: var(--gray-50);">Nombre de sections</td>
                <td style="padding: 10px; border: 1px solid var(--gray-200);"><?= $nb_sections ?></td>
                <td style="padding: 10px; border: 1px solid var(--gray-200); font-weight: 600; background: var(--gray-50);">Nombre de questions</td>
                <td style="padding: 10px; border: 1px solid var(--gray-200);"><?= $nb_questions ?></td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid var(--gray-200); font-weight: 600; background: var(--gray-50);">Répondants</td>
                <td style="padding: 10px; border: 1px solid var(--gray-200);"><?= $nb_respondents ?> / <?= $etude['taille_cible'] ?></td>
                <td style="padding: 10px; border: 1px solid var(--gray-200); font-weight: 600; background: var(--gray-50);">Méthode d'échantillonnage</td>
                <td style="padding: 10px; border: 1px solid var(--gray-200);"><?= methodeEchantillonnageLabel($etude['methode_echantillonnage']) ?></td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid var(--gray-200); font-weight: 600; background: var(--gray-50);">Marge d'erreur</td>
                <td style="padding: 10px; border: 1px solid var(--gray-200);"><?= formatNumber($etude['marge_erreur']) ?>%</td>
                <td style="padding: 10px; border: 1px solid var(--gray-200); font-weight: 600; background: var(--gray-50);">Niveau de confiance</td>
                <td style="padding: 10px; border: 1px solid var(--gray-200);"><?= formatNumber($etude['niveau_confiance']) ?>%</td>
            </tr>
        </table>

        <!-- Résultats par question -->
        <h2 style="font-size: 20px; font-weight: 700; color: var(--gray-900); margin-bottom: 16px; padding-bottom: 8px; border-bottom: 2px solid var(--gray-200);">
            2. Résultats détaillés par question
        </h2>

        <?php 
        $q_num = 0;
        $chart_data = [];
        foreach ($sections as $section): 
            $sec_questions = $db->prepare("SELECT * FROM questions WHERE section_id = ? ORDER BY ordre, id");
            $sec_questions->execute([$section['id']]);
            $sec_questions = $sec_questions->fetchAll();
        ?>
        <h3 style="font-size: 16px; font-weight: 600; color: var(--primary); margin: 24px 0 12px;"><?= e($section['titre']) ?></h3>

        <?php foreach ($sec_questions as $q): 
            $q_num++;
            // Récupérer les réponses
            $results = null; $vals = null; $text_reponses = null;
            if (in_array($q['type'], ['fermee_une', 'likert'])) {
                $stmt = $db->prepare("SELECT rp.libelle, COUNT(*) as eff 
                    FROM reponses r 
                    JOIN reponses_possibles rp ON r.reponse_possibles_id = rp.id 
                    WHERE r.question_id = ? AND r.reponse_possibles_id IS NOT NULL 
                    GROUP BY rp.id ORDER BY rp.ordre");
                $stmt->execute([$q['id']]);
                $results = $stmt->fetchAll();
                $total = array_sum(array_map(fn($r) => $r['eff'], $results));
                // Préparer les données pour le graphique
                if (!empty($results)) {
                    $chart_data[] = [
                        'id' => 'chart_q_' . $q['id'],
                        'type' => 'bar',
                        'labels' => array_map(fn($r) => $r['libelle'], $results),
                        'data' => array_map(fn($r) => (int)$r['eff'], $results),
                    ];
                }
            } elseif ($q['type'] == 'echelle' || $q['type'] == 'numerique') {
                $stmt = $db->prepare("SELECT valeur_numerique FROM reponses WHERE question_id = ? AND valeur_numerique IS NOT NULL");
                $stmt->execute([$q['id']]);
                $vals = array_map(fn($r) => (float) $r['valeur_numerique'], $stmt->fetchAll());
                $total = count($vals);
                // Histogramme pour les données numériques
                if (!empty($vals) && $total > 1) {
                    $min_val = min($vals);
                    $max_val = max($vals);
                    $nb_bins = min(10, max(5, (int)ceil(sqrt($total))));
                    $bin_size = ($max_val - $min_val) / $nb_bins;
                    if ($bin_size == 0) $bin_size = 1;
                    $bins = array_fill(0, $nb_bins, 0);
                    $bin_labels = [];
                    foreach ($vals as $v) {
                        $idx = min($nb_bins - 1, (int)floor(($v - $min_val) / $bin_size));
                        $bins[$idx]++;
                    }
                    for ($i = 0; $i < $nb_bins; $i++) {
                        $bin_labels[] = round($min_val + $i * $bin_size, 1) . '-' . round($min_val + ($i + 1) * $bin_size, 1);
                    }
                    $chart_data[] = [
                        'id' => 'chart_q_' . $q['id'],
                        'type' => 'bar',
                        'labels' => $bin_labels,
                        'data' => $bins,
                    ];
                }
            } elseif ($q['type'] == 'ouverte') {
                $stmt = $db->prepare("SELECT valeur_texte FROM reponses WHERE question_id = ? AND valeur_texte IS NOT NULL AND valeur_texte != ''");
                $stmt->execute([$q['id']]);
                $text_reponses = $stmt->fetchAll();
                $total = count($text_reponses);
            } else {
                $total = 0;
            }
        ?>
        <div style="margin-bottom: 24px; padding: 16px; background: var(--gray-50); border-radius: 8px;">
            <div style="font-weight: 600; margin-bottom: 8px; color: var(--gray-900);">
                Question <?= $q_num ?> : <?= e($q['libelle']) ?>
                <span style="font-size: 12px; color: var(--gray-500); margin-left: 8px;"><?= typeQuestionLabel($q['type']) ?> — <?= $total ?> réponse(s)</span>
            </div>

            <?php if (isset($results) && !empty($results) && in_array($q['type'], ['fermee_une', 'likert'])): ?>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; align-items: start;">
                <div style="background: white; border-radius: 8px; padding: 12px;">
                    <canvas id="chart_q_<?= $q['id'] ?>" style="max-height: 250px;"></canvas>
                </div>
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="background: white;">
                            <th style="padding: 8px; text-align: left; border-bottom: 2px solid var(--gray-200);">Modalité</th>
                            <th style="padding: 8px; text-align: center; border-bottom: 2px solid var(--gray-200);">Eff.</th>
                            <th style="padding: 8px; text-align: center; border-bottom: 2px solid var(--gray-200);">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $r): ?>
                        <tr style="background: white;">
                            <td style="padding: 8px; border-bottom: 1px solid var(--gray-100);"><?= e($r['libelle']) ?></td>
                            <td style="padding: 8px; text-align: center; border-bottom: 1px solid var(--gray-100);"><?= $r['eff'] ?></td>
                            <td style="padding: 8px; text-align: center; border-bottom: 1px solid var(--gray-100);"><?= formatPercent($total > 0 ? ($r['eff'] / $total) * 100 : 0) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php elseif (isset($vals) && !empty($vals) && in_array($q['type'], ['echelle', 'numerique'])): ?>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; align-items: start;">
                <div style="background: white; border-radius: 8px; padding: 12px;">
                    <canvas id="chart_q_<?= $q['id'] ?>" style="max-height: 250px;"></canvas>
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px; padding: 12px;">
                    <div style="display: flex; gap: 20px; flex-wrap: wrap; font-size: 14px;">
                        <div><strong>Moyenne :</strong> <?= formatNumber(moyenne($vals)) ?></div>
                        <div><strong>Médiane :</strong> <?= formatNumber(mediane($vals)) ?></div>
                    </div>
                    <div style="display: flex; gap: 20px; flex-wrap: wrap; font-size: 14px;">
                        <div><strong>Écart-type :</strong> <?= formatNumber(ecartType($vals)) ?></div>
                        <div><strong>Min :</strong> <?= minimum($vals) ?></div>
                        <div><strong>Max :</strong> <?= maximum($vals) ?></div>
                    </div>
                </div>
            </div>
            <?php elseif (isset($text_reponses) && !empty($text_reponses)): ?>
            <div style="margin-top: 8px;">
                <?php foreach ($text_reponses as $tr): ?>
                <div style="padding: 6px 12px; background: white; border-left: 3px solid var(--primary); margin-bottom: 4px; font-size: 13px;">
                    <?= e($tr['valeur_texte']) ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p style="color: var(--gray-400); font-size: 13px;">Aucune réponse.</p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endforeach; ?>

        <!-- Pied de page -->
        <div style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid var(--gray-200); color: var(--gray-400); font-size: 12px;">
            Rapport généré par MarketStudy Pro — <?= formatDate(date('Y-m-d'), 'd/m/Y') ?>
        </div>
    </div>
</div>

<?php endif; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const reportCharts = <?= json_encode($chart_data) ?>;
const chartColors = ['#4f46e5','#ec4899','#10b981','#f59e0b','#0ea5e9','#8b5cf6','#ef4444','#14b8a6','#f97316','#6366f1'];

reportCharts.forEach(function(c) {
    const ctx = document.getElementById(c.id);
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: c.labels,
            datasets: [{
                label: 'Effectif',
                data: c.data,
                backgroundColor: c.labels.map(function(_, i) { return chartColors[i % chartColors.length]; }),
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } },
                x: { ticks: { font: { size: 10 } } }
            }
        }
    });
});
</script>

<style>
@media print {
    .sidebar, .top-header, .page-header, #study-selector, .btn { display: none !important; }
    .main-content { margin-left: 0 !important; }
    .content-area { padding: 0 !important; }
    .card { box-shadow: none !important; border: none !important; }
    body { background: white !important; }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
