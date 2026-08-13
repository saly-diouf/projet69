<?php
require_once __DIR__ . '/../includes/header.php';

$db = getDB();
$etude_id = (int) getParam('etude_id', 0);

$stmt = $db->prepare("SELECT * FROM etudes WHERE id = ?");
$stmt->execute([$etude_id]);
$etude = $stmt->fetch();

if (!$etude) {
    redirect(APP_URL . "/etudes/list.php");
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = postParam('action');

    if ($action == 'add_section') {
        $titre = trim(postParam('section_titre'));
        $description = trim(postParam('section_description'));
        $ordre = (int) postParam('section_ordre', 0);
        if ($titre) {
            $stmt = $db->prepare("INSERT INTO sections (etude_id, titre, description, ordre) VALUES (?, ?, ?, ?)");
            $stmt->execute([$etude_id, $titre, $description, $ordre]);
        }
        redirect(APP_URL . "/questionnaire/builder.php?etude_id=" . $etude_id . "&msg=section_added");
    }

    if ($action == 'add_question') {
        $section_id = (int) postParam('section_id');
        $libelle = trim(postParam('libelle'));
        $type = postParam('type');
        $obligatoire = (int) postParam('obligatoire', 1);
        $ordre = (int) postParam('ordre', 0);
        $echelle_min = (int) postParam('echelle_min', 1);
        $echelle_max = (int) postParam('echelle_max', 5);
        $echelle_libelle_min = trim(postParam('echelle_libelle_min'));
        $echelle_libelle_max = trim(postParam('echelle_libelle_max'));
        $saut_conditionnel = null;
        $saut_reponse = postParam('saut_reponse');
        $saut_question_id = (int) postParam('saut_question_id', 0);
        if ($saut_reponse !== null && $saut_reponse !== '' && $saut_question_id > 0) {
            $saut_conditionnel = json_encode(['reponse_valeur' => $saut_reponse, 'question_id_destination' => $saut_question_id]);
        }

        if ($libelle && $type) {
            $stmt = $db->prepare("INSERT INTO questions (section_id, etude_id, libelle, type, obligatoire, ordre, echelle_min, echelle_max, echelle_libelle_min, echelle_libelle_max, saut_conditionnel) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$section_id, $etude_id, $libelle, $type, $obligatoire, $ordre, $echelle_min, $echelle_max, $echelle_libelle_min, $echelle_libelle_max, $saut_conditionnel]);
            $question_id = $db->lastInsertId();

            // Ajouter les options si nécessaire
            if (in_array($type, ['fermee_une', 'fermee_multiple', 'likert', 'classement'])) {
                $options = postParam('options', []);
                if (is_array($options)) {
                    $ordre_opt = 0;
                    foreach ($options as $idx => $opt_libelle) {
                        $opt_libelle = trim($opt_libelle);
                        if ($opt_libelle) {
                            $valeur = $type == 'likert' ? ($idx + 1) : 0;
                            $stmt = $db->prepare("INSERT INTO reponses_possibles (question_id, libelle, valeur, ordre) VALUES (?, ?, ?, ?)");
                            $stmt->execute([$question_id, $opt_libelle, $valeur, $ordre_opt++]);
                        }
                    }
                }
            }

            // Options Likert par défaut
            if ($type == 'likert' && empty(postParam('options'))) {
                $likert_defaults = ['Pas du tout d\'accord', 'Pas d\'accord', 'Neutre', 'D\'accord', 'Tout à fait d\'accord'];
                foreach ($likert_defaults as $idx => $opt) {
                    $stmt = $db->prepare("INSERT INTO reponses_possibles (question_id, libelle, valeur, ordre) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$question_id, $opt, $idx + 1, $idx]);
                }
            }
        }
        redirect(APP_URL . "/questionnaire/builder.php?etude_id=" . $etude_id . "&msg=question_added");
    }

    if ($action == 'delete_question') {
        $qid = (int) postParam('question_id');
        $stmt = $db->prepare("DELETE FROM questions WHERE id = ? AND etude_id = ?");
        $stmt->execute([$qid, $etude_id]);
        redirect(APP_URL . "/questionnaire/builder.php?etude_id=" . $etude_id . "&msg=question_deleted");
    }

    if ($action == 'delete_section') {
        $sid = (int) postParam('section_id');
        $stmt = $db->prepare("DELETE FROM sections WHERE id = ? AND etude_id = ?");
        $stmt->execute([$sid, $etude_id]);
        redirect(APP_URL . "/questionnaire/builder.php?etude_id=" . $etude_id . "&msg=section_deleted");
    }
}

// Récupérer les sections et questions
$sections = $db->prepare("SELECT * FROM sections WHERE etude_id = ? ORDER BY ordre, id");
$sections->execute([$etude_id]);
$sections = $sections->fetchAll();

$msg = getParam('msg');
$messages = [
    'section_added' => 'Section ajoutée avec succès',
    'question_added' => 'Question ajoutée avec succès',
    'question_deleted' => 'Question supprimée',
    'section_deleted' => 'Section supprimée',
];
?>

<div class="breadcrumb">
    <a href="<?= APP_URL ?>/index.php">Tableau de bord</a>
    <span class="separator"><i class="fas fa-chevron-right"></i></span>
    <a href="<?= APP_URL ?>/etudes/view.php?id=<?= $etude_id ?>"><?= e($etude['titre']) ?></a>
    <span class="separator"><i class="fas fa-chevron-right"></i></span>
    <span>Questionnaire</span>
</div>

<div class="page-header">
    <div>
        <h1>Constructeur de questionnaire</h1>
        <p><?= e($etude['titre']) ?></p>
    </div>
    <div class="flex gap-2">
        <button onclick="document.getElementById('modal-section').classList.add('show')" class="btn btn-outline">
            <i class="fas fa-plus"></i> Section
        </button>
        <a href="<?= APP_URL ?>/survey/preview.php?etude_id=<?= $etude_id ?>" target="_blank" class="btn btn-secondary">
            <i class="fas fa-eye"></i> Aperçu
        </a>
    </div>
</div>

<?php if ($msg && isset($messages[$msg])): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i>
    <div><?= $messages[$msg] ?></div>
</div>
<?php endif; ?>

<?php if (empty($sections)): ?>
    <div class="card">
        <div class="empty-state">
            <i class="fas fa-clipboard-list"></i>
            <h3>Aucune section pour le moment</h3>
            <p>Créez une section pour commencer à ajouter des questions</p>
            <button onclick="document.getElementById('modal-section').classList.add('show')" class="btn btn-primary">
                <i class="fas fa-plus"></i> Créer une section
            </button>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($sections as $section): 
        $questions = $db->prepare("SELECT * FROM questions WHERE section_id = ? ORDER BY ordre, id");
        $questions->execute([$section['id']]);
        $questions = $questions->fetchAll();
    ?>
    <div class="card mb-6 fade-in">
        <div class="card-header">
            <h3><i class="fas fa-folder text-primary"></i> <?= e($section['titre']) ?></h3>
            <div class="flex gap-2">
                <button onclick="openQuestionModal(<?= $section['id'] ?>)" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Question
                </button>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="delete_section">
                    <input type="hidden" name="section_id" value="<?= $section['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm confirm-delete"><i class="fas fa-trash"></i></button>
                </form>
            </div>
        </div>
        <?php if ($section['description']): ?>
            <div style="padding: 12px 24px; color: var(--gray-500); font-size: 14px;"><?= e($section['description']) ?></div>
        <?php endif; ?>
        <div class="card-body">
            <?php if (empty($questions)): ?>
                <p class="text-muted text-center" style="padding: 20px;">Aucune question dans cette section. Cliquez sur "Question" pour en ajouter une.</p>
            <?php else: ?>
                <?php foreach ($questions as $idx => $q): 
                    $opts = $db->prepare("SELECT * FROM reponses_possibles WHERE question_id = ? ORDER BY ordre");
                    $opts->execute([$q['id']]);
                    $opts = $opts->fetchAll();
                ?>
                <div class="question-card" style="margin-bottom: 12px;">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="q-number"><?= $idx + 1 ?></span>
                            <span class="q-text"><?= e($q['libelle']) ?></span>
                            <?php if ($q['obligatoire']): ?>
                                <span class="q-required">*</span>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="badge badge-primary">
                                <i class="fas <?= typeQuestionIcon($q['type']) ?>"></i>
                                <?= typeQuestionLabel($q['type']) ?>
                            </span>
                            <?php if (!empty($q['saut_conditionnel'])): ?>
                            <span class="badge badge-secondary" title="Saut conditionnel défini">
                                <i class="fas fa-code-branch"></i> Saut
                            </span>
                            <?php endif; ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete_question">
                                <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm confirm-delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                    <?php if (!empty($opts)): ?>
                        <div style="margin-top: 10px; padding-left: 36px;">
                            <?php foreach ($opts as $opt): ?>
                                <div class="text-sm text-muted" style="padding: 2px 0;">
                                    <i class="fas fa-circle" style="font-size: 6px; margin-right: 6px;"></i>
                                    <?= e($opt['libelle']) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($q['type'] == 'echelle'): ?>
                        <div style="margin-top: 10px; padding-left: 36px;" class="text-sm text-muted">
                            Échelle de <?= $q['echelle_min'] ?> (<?= e($q['echelle_libelle_min'] ?: '') ?>) à <?= $q['echelle_max'] ?> (<?= e($q['echelle_libelle_max'] ?: '') ?>)
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Modal: Ajouter une section -->
<div class="modal-overlay" id="modal-section">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-folder-plus text-primary"></i> Nouvelle section</h3>
            <button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('show')">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add_section">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Titre de la section <span class="required">*</span></label>
                    <input type="text" name="section_titre" class="form-control" placeholder="Ex : Informations démographiques" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="section_description" class="form-control" placeholder="Description optionnelle..."></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Ordre</label>
                    <input type="number" name="section_ordre" class="form-control" value="0" min="0">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="this.closest('.modal-overlay').classList.remove('show')">Annuler</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Créer</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Ajouter une question -->
<div class="modal-overlay" id="modal-question">
    <div class="modal" style="max-width: 700px;">
        <div class="modal-header">
            <h3><i class="fas fa-question-circle text-primary"></i> Nouvelle question</h3>
            <button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('show')">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add_question">
            <input type="hidden" name="section_id" id="question-section-id">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Question <span class="required">*</span></label>
                    <textarea name="libelle" class="form-control" placeholder="Saisissez votre question..." required></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Type de question <span class="required">*</span></label>
                        <select name="type" id="question-type" class="form-control" required>
                            <option value="fermee_une">Fermée (choix unique)</option>
                            <option value="fermee_multiple">Fermée (choix multiple)</option>
                            <option value="likert">Échelle de Likert</option>
                            <option value="echelle">Échelle numérique</option>
                            <option value="ouverte">Question ouverte</option>
                            <option value="numerique">Question numérique</option>
                            <option value="classement">Classement</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Obligatoire</label>
                        <select name="obligatoire" class="form-control">
                            <option value="1">Oui</option>
                            <option value="0">Non</option>
                        </select>
                    </div>
                </div>

                <!-- Options pour questions fermées/Likert/classement -->
                <div id="options-fields" style="display:none;">
                    <label class="form-label">Options de réponse</label>
                    <div id="options-container">
                        <div class="flex items-center gap-2 mb-2">
                            <input type="text" name="options[]" class="form-control" placeholder="Option 1">
                            <button type="button" class="btn btn-danger btn-sm remove-option"><i class="fas fa-trash"></i></button>
                        </div>
                        <div class="flex items-center gap-2 mb-2">
                            <input type="text" name="options[]" class="form-control" placeholder="Option 2">
                            <button type="button" class="btn btn-danger btn-sm remove-option"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    <button type="button" id="add-option" class="btn btn-outline btn-sm">
                        <i class="fas fa-plus"></i> Ajouter une option
                    </button>
                    <div class="form-text">Pour l'échelle de Likert, laissez vide pour utiliser les options par défaut (5 niveaux).</div>
                </div>

                <!-- Champs pour échelle numérique -->
                <div id="scale-fields" style="display:none;">
                    <div class="form-row-3">
                        <div class="form-group">
                            <label class="form-label">Valeur min</label>
                            <input type="number" name="echelle_min" class="form-control" value="1">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Valeur max</label>
                            <input type="number" name="echelle_max" class="form-control" value="10">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ordre</label>
                            <input type="number" name="ordre" class="form-control" value="0">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Libellé min</label>
                            <input type="text" name="echelle_libelle_min" class="form-control" placeholder="Ex : Pas satisfait">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Libellé max</label>
                            <input type="text" name="echelle_libelle_max" class="form-control" placeholder="Ex : Très satisfait">
                        </div>
                    </div>
                </div>

                <div class="form-group" id="ordre-field">
                    <label class="form-label">Ordre</label>
                    <input type="number" name="ordre" class="form-control" value="0" min="0">
                </div>

                <!-- Saut conditionnel -->
                <div id="skip-logic-fields" style="display:none;">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-code-branch text-primary"></i> Saut conditionnel (optionnel)</label>
                        <div class="form-row">
                            <div class="form-group">
                                <select name="saut_reponse" id="saut-reponse" class="form-control">
                                    <option value="">— Si la réponse est... —</option>
                                </select>
                                <div class="form-text">Choisissez une option qui déclenchera le saut</div>
                            </div>
                            <div class="form-group">
                                <select name="saut_question_id" id="saut-question" class="form-control">
                                    <option value="0">— Aller à la question... —</option>
                                </select>
                                <div class="form-text">Question de destination</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="this.closest('.modal-overlay').classList.remove('show')">Annuler</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Ajouter</button>
            </div>
        </form>
    </div>
</div>

<script>
// Toutes les questions de l'étude (pour le saut conditionnel)
const allQuestions = <?= json_encode(array_map(function($q) {
    return ['id' => (int)$q['id'], 'libelle' => mb_substr($q['libelle'], 0, 60), 'type' => $q['type']];
}, $sections ? array_merge(...array_map(function($s) use ($db) {
    $qs = $db->prepare("SELECT * FROM questions WHERE section_id = ? ORDER BY ordre, id");
    $qs->execute([$s['id']]);
    return $qs->fetchAll();
}, $sections)) : [])) ?>;

function openQuestionModal(sectionId) {
    document.getElementById('question-section-id').value = sectionId;
    document.getElementById('modal-question').classList.add('show');

    // Peupler la liste des questions de destination
    const sautQ = document.getElementById('saut-question');
    sautQ.innerHTML = '<option value="0">— Aller à la question... —</option>';
    allQuestions.forEach(function(q) {
        const opt = document.createElement('option');
        opt.value = q.id;
        opt.textContent = q.libelle;
        sautQ.appendChild(opt);
    });
}

// Gérer l'affichage des champs selon le type
document.getElementById('question-type').addEventListener('change', function() {
    const type = this.value;
    const optionsDiv = document.getElementById('options-fields');
    const scaleDiv = document.getElementById('scale-fields');
    const ordreField = document.getElementById('ordre-field');
    const skipDiv = document.getElementById('skip-logic-fields');
    const needsOptions = ['fermee_une', 'fermee_multiple', 'classement'].includes(type);
    const needsScale = ['echelle'].includes(type);
    const isLikert = type === 'likert';

    optionsDiv.style.display = (needsOptions || isLikert) ? 'block' : 'none';
    scaleDiv.style.display = needsScale ? 'block' : 'none';
    ordreField.style.display = needsScale ? 'none' : 'block';
    // Le saut conditionnel n'est disponible que pour les questions à choix unique
    skipDiv.style.display = (type === 'fermee_une') ? 'block' : 'none';
});

// Quand on ajoute une option, peupler le select des sauts
function updateSkipOptions() {
    const optInputs = document.querySelectorAll('#options-container input[name="options[]"]');
    const sautRep = document.getElementById('saut-reponse');
    sautRep.innerHTML = '<option value="">— Si la réponse est... —</option>';
    optInputs.forEach(function(inp, idx) {
        if (inp.value.trim()) {
            const opt = document.createElement('option');
            opt.value = inp.value.trim();
            opt.textContent = inp.value.trim();
            sautRep.appendChild(opt);
        }
    });
}

// Mettre à jour les options de saut quand on tape dans les options
document.addEventListener('input', function(e) {
    if (e.target && e.target.name === 'options[]') {
        updateSkipOptions();
    }
});

// Fermer les modals en cliquant sur l'overlay
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('show');
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
