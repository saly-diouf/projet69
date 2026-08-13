<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/stats.php';

$etude_id = (int) getParam('etude_id', 0);
$db = getDB();
$stmt = $db->prepare("SELECT * FROM etudes WHERE id = ?");
$stmt->execute([$etude_id]);
$etude = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merci — <?= e($etude['titre'] ?? 'Étude') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <style>
        body { background: linear-gradient(135deg, #eef2ff 0%, #fdf2f8 100%); display:flex; align-items:center; justify-content:center; min-height:100vh; }
        .thank-you { text-align: center; max-width: 500px; padding: 40px; }
        .thank-you .icon { width: 80px; height: 80px; background: linear-gradient(135deg, var(--success), #34d399); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 24px; box-shadow: 0 8px 32px rgba(16,185,129,0.3); }
        .thank-you .icon i { color: white; font-size: 36px; }
        .thank-you h1 { font-size: 28px; font-weight: 700; color: var(--gray-900); margin-bottom: 12px; }
        .thank-you p { color: var(--gray-500); font-size: 16px; margin-bottom: 24px; }
    </style>
</head>
<body>
<div class="thank-you">
    <div class="icon"><i class="fas fa-check"></i></div>
    <h1>Merci pour votre participation !</h1>
    <p>Vos réponses ont été enregistrées avec succès. Elles contribueront à l'étude de marché <strong><?= e($etude['titre'] ?? '') ?></strong>.</p>
    <div style="display: inline-block; padding: 12px 24px; background: white; border-radius: 12px; box-shadow: var(--shadow); margin-top: 16px;">
        <i class="fas fa-shield-alt text-success"></i>
        <span class="text-sm">Vos données sont traitées de manière confidentielle</span>
    </div>
</div>
</body>
</html>
