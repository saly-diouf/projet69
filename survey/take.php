<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/stats.php';

$db = getDB();
$etude_id = (int) getParam('etude_id', 0);
$token = getParam('token');

// Récupérer l'étude
$stmt = $db->prepare("SELECT * FROM etudes WHERE id = ? AND statut = 'active'");
$stmt->execute([$etude_id]);
$etude = $stmt->fetch();

if (!$etude) {
    die('<h1 style="text-align:center; margin-top:100px; font-family:Inter,sans-serif;">Cette étude n\'est pas disponible.</h1>');
}

// Gérer le répondant
$respondent = null;
if ($token) {
    $stmt = $db->prepare("SELECT * FROM respondents WHERE token = ? AND etude_id = ?");
    $stmt->execute([$token, $etude_id]);
    $respondent = $stmt->fetch();
}

if (!$respondent) {
    $token = generateToken();
    $stmt = $db->prepare("INSERT INTO respondents (etude_id, token, statut, date_invitation, ip_address, user_agent) VALUES (?, ?, 'en_cours', NOW(), ?, ?)");
    $stmt->execute([$etude_id, $token, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null]);
    $respondent_id = $db->lastInsertId();
    $stmt = $db->prepare("UPDATE respondents SET date_debut = NOW() WHERE id = ?");
    $stmt->execute([$respondent_id]);
} else {
    $respondent_id = $respondent['id'];
}

// Traitement des réponses
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $questions_stmt = $db->prepare("SELECT * FROM questions WHERE etude_id = ?");
    $questions_stmt->execute([$etude_id]);
    $all_questions = $questions_stmt->fetchAll();

    foreach ($all_questions as $q) {
        $field_name = 'q_' . $q['id'];
        $valeur_texte = null;
        $valeur_numerique = null;
        $reponse_possibles_id = null;
        $valeur_multiple = null;

        if ($q['type'] == 'fermee_une' || $q['type'] == 'likert' || $q['type'] == 'echelle') {
            $val = postParam($field_name);
            if ($val !== null && $val !== '') {
                if ($q['type'] == 'echelle' || $q['type'] == 'likert') {
                    $valeur_numerique = (float) $val;
                }
                $reponse_possibles_id = (int) $val;
            }
        } elseif ($q['type'] == 'fermee_multiple') {
            $vals = postParam($field_name, []);
            if (is_array($vals) && count($vals) > 0) {
                $valeur_multiple = json_encode($vals);
            }
        } elseif ($q['type'] == 'ouverte') {
            $valeur_texte = trim(postParam($field_name, ''));
        } elseif ($q['type'] == 'numerique') {
            $val = postParam($field_name);
            if ($val !== null && $val !== '') {
                $valeur_numerique = (float) $val;
            }
        } elseif ($q['type'] == 'classement') {
            $val = postParam($field_name);
            if ($val) {
                $valeur_classement = $val;
            }
        }

        // Vérifier si une réponse existe déjà
        $check = $db->prepare("SELECT id FROM reponses WHERE respondent_id = ? AND question_id = ?");
        $check->execute([$respondent_id, $q['id']]);
        $existing = $check->fetch();

        if ($existing) {
            $stmt = $db->prepare("UPDATE reponses SET reponse_possibles_id = ?, valeur_texte = ?, valeur_numerique = ?, valeur_multiple = ?, date_reponse = NOW() WHERE id = ?");
            $stmt->execute([$reponse_possibles_id, $valeur_texte, $valeur_numerique, $valeur_multiple, $existing['id']]);
        } else {
            $stmt = $db->prepare("INSERT INTO reponses (respondent_id, question_id, reponse_possibles_id, valeur_texte, valeur_numerique, valeur_multiple) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$respondent_id, $q['id'], $reponse_possibles_id, $valeur_texte, $valeur_numerique, $valeur_multiple]);
        }
    }

    // Mettre à jour les infos du répondant
    $age = postParam('age') ? (int) postParam('age') : null;
    $genre = postParam('genre') ?: null;
    $ville = trim(postParam('ville', '')) ?: null;
    $profession = trim(postParam('profession', '')) ?: null;

    $stmt = $db->prepare("UPDATE respondents SET statut = 'termine', date_fin = NOW(), age = ?, genre = ?, ville = ?, profession = ? WHERE id = ?");
    $stmt->execute([$age, $genre, $ville, $profession, $respondent_id]);

    redirect(APP_URL . "/survey/thank_you.php?etude_id=" . $etude_id);
}

// Récupérer les sections et questions
$sections = $db->prepare("SELECT * FROM sections WHERE etude_id = ? ORDER BY ordre, id");
$sections->execute([$etude_id]);
$sections = $sections->fetchAll();

$total_questions = 0;
foreach ($sections as $s) {
    $qcount = $db->query("SELECT COUNT(*) FROM questions WHERE section_id = {$s['id']}")->fetchColumn();
    $total_questions += $qcount;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($etude['titre']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <style>
        body { background: linear-gradient(135deg, #eef2ff 0%, #fdf2f8 100%); }
        .survey-container { max-width: 800px; margin: 40px auto; padding: 0 20px; }
        .survey-header { text-align: center; margin-bottom: 32px; }
        .survey-header h1 { font-size: 28px; font-weight: 700; color: var(--gray-900); margin-bottom: 8px; }
        .survey-header p { color: var(--gray-500); font-size: 15px; }
        .survey-progress { margin-bottom: 24px; }
        .survey-progress .progress { height: 10px; }
        .survey-info { background: white; border-radius: var(--radius-md); padding: 20px; margin-bottom: 24px; box-shadow: var(--shadow); border: 1px solid var(--gray-200); }
        .survey-info h3 { font-size: 15px; font-weight: 600; margin-bottom: 12px; color: var(--gray-700); }
    </style>
</head>
<body>
<div class="survey-container">
    <div class="survey-header">
        <div style="width:56px; height:56px; background:linear-gradient(135deg,var(--primary),var(--accent)); border-radius:14px; display:inline-flex; align-items:center; justify-content:center; margin-bottom:16px; box-shadow:0 8px 24px rgba(79,70,229,0.3);">
            <i class="fas fa-chart-pie" style="color:white; font-size:24px;"></i>
        </div>
        <h1><?= e($etude['titre']) ?></h1>
        <p><?= e($etude['description'] ?: 'Merci de prendre quelques minutes pour répondre à ce questionnaire') ?></p>
    </div>

    <div class="survey-progress">
        <div class="flex justify-between mb-2">
            <span class="text-sm text-muted"><?= $total_questions ?> question(s)</span>
            <span class="text-sm text-muted">≈ 5 minutes</span>
        </div>
        <div class="progress">
            <div class="progress-bar" style="width: 0%"></div>
        </div>
    </div>

    <?php if ($total_questions == 0): ?>
        <div class="card">
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <h3>Questionnaire vide</h3>
                <p>Cette étude n'a pas encore de questions.</p>
            </div>
        </div>
    <?php else: ?>
    <form method="POST" action="<?= APP_URL ?>/survey/take.php?etude_id=<?= $etude_id ?>&token=<?= e($token) ?>">
        <!-- Informations démographiques -->
        <div class="survey-info">
            <h3><i class="fas fa-user text-primary"></i> Informations sur vous (optionnel)</h3>
            <div class="form-row-3">
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Âge</label>
                    <input type="number" name="age" class="form-control" min="1" max="120" placeholder="Ex : 25">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Genre</label>
                    <select name="genre" class="form-control">
                        <option value="">—</option>
                        <option value="M">Homme</option>
                        <option value="F">Femme</option>
                        <option value="Autre">Autre</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Ville</label>
                    <input type="text" name="ville" class="form-control" placeholder="Ex : Dakar">
                </div>
            </div>
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
                <?php if ($section['description']): ?>
                    <p class="text-muted mb-4"><?= e($section['description']) ?></p>
                <?php endif; ?>

                <?php foreach ($questions as $q_idx => $q): 
                    $opts = $db->prepare("SELECT * FROM reponses_possibles WHERE question_id = ? ORDER BY ordre");
                    $opts->execute([$q['id']]);
                    $opts = $opts->fetchAll();
                    $saut = !empty($q['saut_conditionnel']) ? json_decode($q['saut_conditionnel'], true) : null;
                ?>
                <div class="question-card" id="question-card-<?= $q['id'] ?>" <?= $saut ? 'data-has-skip="1"' : '' ?>>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="q-number"><?= $q_idx + 1 ?></span>
                        <span class="q-text"><?= e($q['libelle']) ?></span>
                        <?php if ($q['obligatoire']): ?>
                            <span class="q-required">*</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($q['type'] == 'fermee_une'): ?>
                        <div class="option-list">
                            <?php foreach ($opts as $opt): ?>
                            <div class="option-item">
                                <input type="radio" name="q_<?= $q['id'] ?>" id="opt_<?= $opt['id'] ?>" value="<?= $opt['id'] ?>" <?= $q['obligatoire'] ? 'required' : '' ?> <?= $saut ? 'data-skip-reponse="' . e($saut['reponse_valeur']) . '" data-skip-dest="' . (int)$saut['question_id_destination'] . '"' : '' ?>>
                                <label for="opt_<?= $opt['id'] ?>"><?= e($opt['libelle']) ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>

                    <?php elseif ($q['type'] == 'fermee_multiple'): ?>
                        <div class="option-list">
                            <?php foreach ($opts as $opt): ?>
                            <div class="option-item">
                                <input type="checkbox" name="q_<?= $q['id'] ?>[]" id="opt_<?= $opt['id'] ?>" value="<?= $opt['id'] ?>">
                                <label for="opt_<?= $opt['id'] ?>"><?= e($opt['libelle']) ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>

                    <?php elseif ($q['type'] == 'likert'): ?>
                        <div class="likert-scale">
                            <?php foreach ($opts as $opt): ?>
                            <div class="likert-option">
                                <input type="radio" name="q_<?= $q['id'] ?>" value="<?= $opt['id'] ?>" id="likert_<?= $opt['id'] ?>" <?= $q['obligatoire'] ? 'required' : '' ?>>
                                <span><?= $opt['valeur'] ?></span>
                                <span class="likert-label"><?= e($opt['libelle']) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>

                    <?php elseif ($q['type'] == 'echelle'): ?>
                        <div style="margin-top: 14px;">
                            <div class="flex justify-between mb-2">
                                <span class="text-sm text-muted"><?= e($q['echelle_libelle_min'] ?: 'Min') ?></span>
                                <span class="text-sm text-muted"><?= e($q['echelle_libelle_max'] ?: 'Max') ?></span>
                            </div>
                            <input type="range" name="q_<?= $q['id'] ?>" min="<?= $q['echelle_min'] ?>" max="<?= $q['echelle_max'] ?>" value="<?= $q['echelle_min'] ?>" step="1" class="form-control" style="padding:0; height:auto; border:none;" oninput="document.getElementById('scale-val-<?= $q['id'] ?>').textContent = this.value">
                            <div class="text-center font-bold text-lg text-primary" id="scale-val-<?= $q['id'] ?>"><?= $q['echelle_min'] ?></div>
                        </div>

                    <?php elseif ($q['type'] == 'ouverte'): ?>
                        <textarea name="q_<?= $q['id'] ?>" class="form-control" placeholder="Saisissez votre réponse..." <?= $q['obligatoire'] ? 'required' : '' ?> style="margin-top: 14px;"></textarea>

                    <?php elseif ($q['type'] == 'numerique'): ?>
                        <input type="number" name="q_<?= $q['id'] ?>" class="form-control" placeholder="Saisissez un nombre" <?= $q['obligatoire'] ? 'required' : '' ?> style="margin-top: 14px;">

                    <?php elseif ($q['type'] == 'classement'): ?>
                        <div class="option-list" id="ranking-<?= $q['id'] ?>">
                            <?php foreach ($opts as $rank_idx => $opt): ?>
                            <div class="option-item" draggable="true" data-id="<?= $opt['id'] ?>" style="cursor: move;">
                                <i class="fas fa-grip-vertical text-muted"></i>
                                <label><?= e($opt['libelle']) ?></label>
                                <span class="badge badge-primary rank-badge"><?= $rank_idx + 1 ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="q_<?= $q['id'] ?>" id="ranking-input-<?= $q['id'] ?>">
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="card">
            <div class="card-body text-center">
                <button type="submit" class="btn btn-primary btn-lg" style="padding: 16px 48px;">
                    <i class="fas fa-paper-plane"></i> Soumettre mes réponses
                </button>
                <p class="text-sm text-muted mt-4">Vos réponses sont confidentielles et traitées de manière anonyme.</p>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
// Skip logic (sauts conditionnels)
// Récupérer tous les inputs avec saut
document.querySelectorAll('input[data-skip-reponse]').forEach(function(input) {
    input.addEventListener('change', function() {
        var skipReponse = this.getAttribute('data-skip-reponse');
        var skipDest = parseInt(this.getAttribute('data-skip-dest'));
        var currentCard = this.closest('.question-card');
        var currentId = parseInt(currentCard.id.replace('question-card-', ''));

        // Obtenir le libellé de l'option sélectionnée
        var selectedLabel = this.nextElementSibling ? this.nextElementSibling.textContent.trim() : '';

        // Si la réponse correspond à la règle de saut
        if (selectedLabel === skipReponse) {
            // Masquer toutes les questions entre la question actuelle et la destination
            var allCards = document.querySelectorAll('.question-card');
            var foundCurrent = false;
            allCards.forEach(function(card) {
                var cardId = parseInt(card.id.replace('question-card-', ''));
                if (cardId === currentId) {
                    foundCurrent = true;
                    return;
                }
                if (foundCurrent && cardId !== skipDest) {
                    card.style.display = 'none';
                    // Désactiver les required des questions masquées
                    card.querySelectorAll('input[required], textarea[required]').forEach(function(inp) {
                        inp.removeAttribute('required');
                        inp.setAttribute('data-was-required', '1');
                    });
                }
                if (cardId === skipDest) {
                    foundCurrent = false;
                }
            });
        } else {
            // Réafficher toutes les questions masquées par le saut
            var allCards2 = document.querySelectorAll('.question-card');
            var foundCurrent2 = false;
            allCards2.forEach(function(card) {
                var cardId = parseInt(card.id.replace('question-card-', ''));
                if (cardId === currentId) {
                    foundCurrent2 = true;
                    return;
                }
                if (foundCurrent2 && cardId !== skipDest) {
                    card.style.display = '';
                    // Restaurer les required
                    card.querySelectorAll('[data-was-required="1"]').forEach(function(inp) {
                        inp.setAttribute('required', 'required');
                        inp.removeAttribute('data-was-required');
                    });
                }
                if (cardId === skipDest) {
                    foundCurrent2 = false;
                }
            });
        }
    });
});

// Ranking drag and drop
document.querySelectorAll('[id^="ranking-"]').forEach(list => {
    let dragged = null;
    list.querySelectorAll('.option-item').forEach(item => {
        item.addEventListener('dragstart', function() { dragged = this; });
        item.addEventListener('dragover', function(e) { e.preventDefault(); });
        item.addEventListener('drop', function() {
            if (dragged && dragged !== this) {
                const all = [...list.querySelectorAll('.option-item')];
                const draggedIdx = all.indexOf(dragged);
                const targetIdx = all.indexOf(this);
                if (draggedIdx < targetIdx) {
                    this.parentNode.insertBefore(dragged, this.nextSibling);
                } else {
                    this.parentNode.insertBefore(dragged, this);
                }
                updateRanks(list);
            }
        });
    });
    function updateRanks(list) {
        const qId = list.id.replace('ranking-', '');
        const items = list.querySelectorAll('.option-item');
        const order = [];
        items.forEach((item, idx) => {
            item.querySelector('.rank-badge').textContent = idx + 1;
            order.push(item.dataset.id);
        });
        document.getElementById('ranking-input-' + qId).value = JSON.stringify(order);
    }
    updateRanks(list);
});

// Progress bar
const form = document.querySelector('form');
if (form) {
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    const progressBar = document.querySelector('.survey-progress .progress-bar');
    inputs.forEach(input => {
        input.addEventListener('change', function() {
            const filled = [...inputs].filter(i => i.value && i.value.trim() !== '').length;
            const pct = (filled / inputs.length) * 100;
            progressBar.style.width = pct + '%';
        });
    });
}
</script>
</body>
</html>
