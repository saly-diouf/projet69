<?php
$page = 'create';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin', 'chercheur']);

$db = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim(postParam('titre'));
    $description = trim(postParam('description'));
    $domaine = trim(postParam('domaine'));
    $taille_cible = (int) postParam('taille_cible', 100);
    $marge_erreur = (float) postParam('marge_erreur', 5);
    $niveau_confiance = (float) postParam('niveau_confiance', 95);
    $methode = postParam('methode_echantillonnage', 'aleatoire_simple');
    $date_ouverture = postParam('date_ouverture');
    $date_cloture = postParam('date_cloture');

    if (empty($titre)) {
        $errors[] = "Le titre est obligatoire";
    }

    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO etudes (titre, description, domaine, taille_cible, marge_erreur, niveau_confiance, methode_echantillonnage, date_ouverture, date_cloture, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$titre, $description, $domaine, $taille_cible, $marge_erreur, $niveau_confiance, $methode, $date_ouverture ?: null, $date_cloture ?: null, $current_user['id']]);
        $etude_id = $db->lastInsertId();
        redirect(APP_URL . "/etudes/view.php?id=" . $etude_id . "&created=1");
    }
}
?>

<div class="breadcrumb">
    <a href="<?= APP_URL ?>/index.php">Tableau de bord</a>
    <span class="separator"><i class="fas fa-chevron-right"></i></span>
    <a href="<?= APP_URL ?>/etudes/list.php">Études</a>
    <span class="separator"><i class="fas fa-chevron-right"></i></span>
    <span>Nouvelle étude</span>
</div>

<div class="page-header">
    <div>
        <h1>Créer une étude de marché</h1>
        <p>Définissez les paramètres de votre étude</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <div>
            <?php foreach ($errors as $err): ?>
                <div><?= e($err) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="card" style="max-width: 800px;">
    <div class="card-body">
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Titre de l'étude <span class="required">*</span></label>
                <input type="text" name="titre" class="form-control" placeholder="Ex : Satisfaction client - Produit X" required value="<?= e(postParam('titre')) ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" placeholder="Décrivez l'objectif et le contexte de votre étude..."><?= e(postParam('description')) ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Domaine</label>
                <input type="text" name="domaine" class="form-control" placeholder="Ex : Marketing — Études de marché" value="<?= e(postParam('domaine')) ?>">
            </div>

            <h3 class="font-semibold text-lg mb-4 mt-6" style="color: var(--gray-900);">Paramètres d'échantillonnage</h3>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Taille cible de l'échantillon</label>
                    <input type="number" name="taille_cible" class="form-control" value="<?= (int) postParam('taille_cible', 100) ?>" min="1">
                    <div class="form-text">Nombre de répondants visés</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Méthode d'échantillonnage</label>
                    <select name="methode_echantillonnage" class="form-control">
                        <option value="aleatoire_simple">Aléatoire simple</option>
                        <option value="aleatoire_stratifie">Aléatoire stratifié</option>
                        <option value="quotas">Méthode des quotas</option>
                        <option value="convenance">De convenance</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Marge d'erreur (%)</label>
                    <input type="number" name="marge_erreur" class="form-control" value="<?= (float) postParam('marge_erreur', 5) ?>" min="1" max="20" step="0.5">
                    <div class="form-text">Typiquement 5%</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Niveau de confiance (%)</label>
                    <input type="number" name="niveau_confiance" class="form-control" value="<?= (float) postParam('niveau_confiance', 95) ?>" min="80" max="99" step="1">
                    <div class="form-text">Typiquement 95%</div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Date d'ouverture</label>
                    <input type="date" name="date_ouverture" class="form-control" value="<?= e(postParam('date_ouverture')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Date de clôture</label>
                    <input type="date" name="date_cloture" class="form-control" value="<?= e(postParam('date_cloture')) ?>">
                </div>
            </div>

            <div class="flex gap-2 mt-6">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Créer l'étude
                </button>
                <a href="<?= APP_URL ?>/etudes/list.php" class="btn btn-outline btn-lg">Annuler</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
