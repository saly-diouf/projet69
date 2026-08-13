<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/stats.php';

$db = getDB();
$etude_id = (int) getParam('etude_id', 0);

$stmt = $db->prepare("SELECT * FROM etudes WHERE id = ?");
$stmt->execute([$etude_id]);
$etude = $stmt->fetch();

if (!$etude) {
    redirect(APP_URL . "/etudes/list.php");
}

$sections = $db->prepare("SELECT * FROM sections WHERE etude_id = ? ORDER BY ordre, id");
$sections->execute([$etude_id]);
$sections = $sections->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aperçu — <?= e($etude['titre']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <style>
        body { background: linear-gradient(135deg, #eef2ff 0%, #fdf2f8 100%); }
        .survey-container { max-width: 800px; margin: 40px auto; padding: 0 20px; }
        .survey-header { text-align: center; margin-bottom: 32px; }
        .survey-header h1 { font-size: 28px; font-weight: 700; color: var(--gray-900); margin-bottom: 8px; }
        .preview-banner { background: var(--warning-50); border: 1px solid #fde68a; border-radius: 12px; padding: 12px 20px; margin-bottom: 24px; text-align: center; color: #92400e; font-size: 14px; }
    </style>
</head>
<body>
<div class="survey-container">
    <div class="preview-banner">
        <i class="fas fa-eye"></i> Ceci est un aperçu du questionnaire. Les réponses ne seront pas enregistrées.
    </div>
    <div class="survey-header">
        <h1><?= e($etude['titre']) ?></h1>
        <p><?= e($etude['description'] ?: 'Aperçu du questionnaire') ?></p>
    </div>

    <?php foreach ($sections as $s_idx => $section): 
        $questions = $db->prepare("SELECT * FROM questions WHERE section_id = ? ORDER BY ordre, id");
        $questions->execute([$section['id']]);
        $questions = $questions->fetchAll();
    ?>
    <div class="card mb-6">
        <div class="card-header">
            <h3>Section <?= $s_idx + 1 ?> : <?= e($section['titre']) ?></h3>
        </div>
        <div class="card-body">
            <?php foreach ($questions as $q_idx => $q): 
                $opts = $db->prepare("SELECT * FROM reponses_possibles WHERE question_id = ? ORDER BY ordre");
                $opts->execute([$q['id']]);
                $opts = $opts->fetchAll();
            ?>
            <div class="question-card">
                <div class="flex items-center gap-2 mb-2">
                    <span class="q-number"><?= $q_idx + 1 ?></span>
                    <span class="q-text"><?= e($q['libelle']) ?></span>
                    <?php if ($q['obligatoire']): ?><span class="q-required">*</span><?php endif; ?>
                    <span class="badge badge-primary" style="margin-left:auto;">
                        <i class="fas <?= typeQuestionIcon($q['type']) ?>"></i> <?= typeQuestionLabel($q['type']) ?>
                    </span>
                </div>
                <?php if ($q['type'] == 'fermee_une'): ?>
                    <div class="option-list">
                        <?php foreach ($opts as $opt): ?>
                        <div class="option-item"><input type="radio" disabled><label><?= e($opt['libelle']) ?></label></div>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($q['type'] == 'fermee_multiple'): ?>
                    <div class="option-list">
                        <?php foreach ($opts as $opt): ?>
                        <div class="option-item"><input type="checkbox" disabled><label><?= e($opt['libelle']) ?></label></div>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($q['type'] == 'likert'): ?>
                    <div class="likert-scale">
                        <?php foreach ($opts as $opt): ?>
                        <div class="likert-option"><span><?= $opt['valeur'] ?></span><span class="likert-label"><?= e($opt['libelle']) ?></span></div>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($q['type'] == 'echelle'): ?>
                    <div style="margin-top:14px;">
                        <input type="range" disabled min="<?= $q['echelle_min'] ?>" max="<?= $q['echelle_max'] ?>" style="width:100%;">
                        <div class="flex justify-between text-sm text-muted">
                            <span><?= e($q['echelle_libelle_min'] ?: 'Min') ?></span>
                            <span><?= e($q['echelle_libelle_max'] ?: 'Max') ?></span>
                        </div>
                    </div>
                <?php elseif ($q['type'] == 'ouverte'): ?>
                    <textarea class="form-control" disabled placeholder="Réponse ouverte..." style="margin-top:14px;"></textarea>
                <?php elseif ($q['type'] == 'numerique'): ?>
                    <input type="number" class="form-control" disabled placeholder="Nombre" style="margin-top:14px;">
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="text-center">
        <a href="<?= APP_URL ?>/questionnaire/builder.php?etude_id=<?= $etude_id ?>" class="btn btn-outline btn-lg">
            <i class="fas fa-arrow-left"></i> Retour au constructeur
        </a>
    </div>
</div>
</body>
</html>
