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

$survey_url = APP_URL . "/survey/take.php?etude_id=" . $etude_id;

// Envoyer des invitations par email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && postParam('action') == 'send_emails') {
    $emails_raw = array_map('trim', explode("\n", postParam('emails', '')));
    $emails = array_filter($emails_raw, function($e) { return filter_var($e, FILTER_VALIDATE_EMAIL); });
    $sent = 0;
    foreach ($emails as $email) {
        $token = generateToken();
        $stmt = $db->prepare("INSERT INTO invitations (etude_id, email, token) VALUES (?, ?, ?)");
        $stmt->execute([$etude_id, $email, $token]);

        // Créer un répondant
        $stmt2 = $db->prepare("INSERT INTO respondents (etude_id, token, statut, date_invitation) VALUES (?, ?, 'invite', NOW())");
        $stmt2->execute([$etude_id, $token]);

        // En production, envoyer l'email ici avec mail() ou une API
        // mail($email, "Invitation: " . $etude['titre'], "Participez à notre étude: " . APP_URL . "/survey/take.php?etude_id=" . $etude_id . "&token=" . $token);
        $sent++;
    }
    $msg = "$sent invitation(s) envoyée(s)";
}

// Récupérer les invitations
$invitations = $db->prepare("SELECT * FROM invitations WHERE etude_id = ? ORDER BY date_envoi DESC LIMIT 20");
$invitations->execute([$etude_id]);
$invitations = $invitations->fetchAll();
?>

<div class="breadcrumb">
    <a href="<?= APP_URL ?>/index.php">Tableau de bord</a>
    <span class="separator"><i class="fas fa-chevron-right"></i></span>
    <a href="<?= APP_URL ?>/etudes/view.php?id=<?= $etude_id ?>"><?= e($etude['titre']) ?></a>
    <span class="separator"><i class="fas fa-chevron-right"></i></span>
    <span>Distribution</span>
</div>

<div class="page-header">
    <div>
        <h1>Distribution de l'enquête</h1>
        <p>Partagez votre questionnaire avec les répondants</p>
    </div>
</div>

<?php if (isset($msg)): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i>
    <div><?= e($msg) ?></div>
</div>
<?php endif; ?>

<div class="grid grid-2">
    <!-- Lien de participation -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-link text-primary"></i> Lien de participation</h3>
        </div>
        <div class="card-body">
            <p class="text-muted text-sm mb-4">Partagez ce lien pour permettre aux répondants d'accéder directement au questionnaire.</p>
            <div class="flex gap-2">
                <input type="text" id="survey-url" class="form-control" value="<?= e($survey_url) ?>" readonly>
                <button class="btn btn-primary copy-btn" data-target="survey-url"><i class="fas fa-copy"></i></button>
            </div>
            <div class="mt-4">
                <a href="<?= e($survey_url) ?>" target="_blank" class="btn btn-outline btn-sm">
                    <i class="fas fa-external-link-alt"></i> Ouvrir le questionnaire
                </a>
            </div>
        </div>
    </div>

    <!-- QR Code -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-qrcode text-primary"></i> QR Code</h3>
        </div>
        <div class="card-body">
            <p class="text-muted text-sm mb-4">Scannez ce QR code pour accéder au questionnaire depuis un mobile.</p>
            <div class="qr-container">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode($survey_url) ?>" alt="QR Code" width="200" height="200">
                <a href="https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=<?= urlencode($survey_url) ?>" download="qr_code_<?= $etude_id ?>.png" class="btn btn-outline btn-sm">
                    <i class="fas fa-download"></i> Télécharger
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Email -->
<div class="card mt-6">
    <div class="card-header">
        <h3><i class="fas fa-envelope text-primary"></i> Invitations par email</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="action" value="send_emails">
            <div class="form-group">
                <label class="form-label">Adresses email (une par ligne)</label>
                <textarea name="emails" class="form-control" placeholder="email1@example.com&#10;email2@example.com&#10;..." style="min-height: 120px;"></textarea>
                <div class="form-text">Les répondants recevront un lien unique pour participer à l'enquête.</div>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> Envoyer les invitations
            </button>
        </form>

        <?php if (!empty($invitations)): ?>
        <h4 class="font-semibold mt-6 mb-4">Invitations récentes</h4>
        <div style="overflow-x:auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Statut</th>
                        <th>Date d'envoi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invitations as $inv): ?>
                    <tr>
                        <td><?= e($inv['email']) ?></td>
                        <td><span class="badge badge-secondary"><?= e($inv['statut']) ?></span></td>
                        <td><?= formatDate($inv['date_envoi'], 'd/m/Y H:i') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
