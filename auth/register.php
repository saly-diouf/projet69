<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/stats.php';

if (isLoggedIn()) {
    redirect(APP_URL . '/index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nom' => trim(postParam('nom')),
        'prenom' => trim(postParam('prenom')),
        'email' => trim(postParam('email')),
        'mot_de_passe' => postParam('mot_de_passe'),
        'role' => postParam('role', 'repondant'),
        'telephone' => trim(postParam('telephone')),
        'organisation' => trim(postParam('organisation')),
    ];

    if (empty($data['nom']) || empty($data['prenom']) || empty($data['email']) || empty($data['mot_de_passe'])) {
        $error = 'Veuillez remplir tous les champs obligatoires';
    } elseif (strlen($data['mot_de_passe']) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide';
    } else {
        $result = registerUser($data);
        if ($result['success']) {
            loginUser($data['email'], $data['mot_de_passe']);
            redirect(APP_URL . '/index.php');
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription — MarketStudy Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4f46e5 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .register-container { max-width: 540px; width: 100%; }
        .register-logo { text-align: center; margin-bottom: 24px; }
        .register-logo .logo-icon {
            width: 56px; height: 56px;
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border-radius: 14px;
            display: inline-flex; align-items: center; justify-content: center;
            margin-bottom: 12px;
            box-shadow: 0 8px 32px rgba(99,102,241,0.4);
        }
        .register-logo .logo-icon i { color: white; font-size: 24px; }
        .register-logo h1 { color: white; font-size: 22px; font-weight: 700; }
        .register-card {
            background: white;
            border-radius: 20px;
            padding: 36px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .role-selector {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 4px;
        }
        .role-option {
            padding: 14px 8px;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .role-option:hover { border-color: var(--primary-300); }
        .role-option.selected {
            border-color: var(--primary);
            background: var(--primary-50);
        }
        .role-option i { font-size: 22px; margin-bottom: 6px; display: block; }
        .role-option .role-name { font-size: 13px; font-weight: 600; color: var(--gray-700); }
        .role-option.selected .role-name { color: var(--primary); }
        .role-option input { display: none; }
    </style>
</head>
<body>
<div class="register-container">
    <div class="register-logo">
        <a href="<?= APP_URL ?>/landing.php" style="text-decoration:none;">
            <div class="logo-icon"><i class="fas fa-chart-pie"></i></div>
            <h1>MarketStudy Pro</h1>
        </a>
    </div>

    <div class="register-card">
        <h2 style="font-size: 22px; font-weight: 700; color: var(--gray-900); margin-bottom: 4px;">Créer un compte</h2>
        <p style="color: var(--gray-500); font-size: 14px; margin-bottom: 24px;">Rejoignez la plateforme d'études de marché</p>

        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <div><?= e($error) ?></div>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Prénom <span class="required">*</span></label>
                    <input type="text" name="prenom" class="form-control" required value="<?= e(postParam('prenom')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Nom <span class="required">*</span></label>
                    <input type="text" name="nom" class="form-control" required value="<?= e(postParam('nom')) ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Adresse email <span class="required">*</span></label>
                <input type="email" name="email" class="form-control" required placeholder="vous@exemple.com" value="<?= e(postParam('email')) ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Mot de passe <span class="required">*</span></label>
                <input type="password" name="mot_de_passe" class="form-control" required minlength="6" placeholder="Minimum 6 caractères">
            </div>

            <div class="form-group">
                <label class="form-label">Je suis... <span class="required">*</span></label>
                <div class="role-selector">
                    <label class="role-option" onclick="selectRole(this, 'repondant')">
                        <input type="radio" name="role" value="repondant" checked>
                        <i class="fas fa-user" style="color: var(--info);"></i>
                        <div class="role-name">Répondant</div>
                    </label>
                    <label class="role-option" onclick="selectRole(this, 'chercheur')">
                        <input type="radio" name="role" value="chercheur">
                        <i class="fas fa-user-graduate" style="color: var(--primary);"></i>
                        <div class="role-name">Chercheur</div>
                    </label>
                </div>
                <div class="form-text" style="margin-top: 6px;"><i class="fas fa-info-circle"></i> Les comptes administrateur sont créés uniquement par un administrateur existant.</div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Téléphone</label>
                    <input type="tel" name="telephone" class="form-control" placeholder="+221 ..." value="<?= e(postParam('telephone')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Organisation</label>
                    <input type="text" name="organisation" class="form-control" placeholder="Université, entreprise..." value="<?= e(postParam('organisation')) ?>">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg btn-block" style="margin-top: 8px;">
                <i class="fas fa-user-plus"></i> Créer mon compte
            </button>
        </form>

        <div style="text-align: center; margin-top: 20px;">
            <p style="font-size: 14px; color: var(--gray-500);">
                Déjà inscrit ? 
                <a href="<?= APP_URL ?>/auth/login.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">Se connecter</a>
            </p>
        </div>
    </div>
    <div style="text-align:center; margin-top: 24px;">
        <a href="<?= APP_URL ?>/landing.php" style="color: rgba(255,255,255,0.6); text-decoration: none; font-size: 14px; font-weight: 500; transition: color 0.2s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,0.6)'">
            <i class="fas fa-arrow-left"></i> Retour à la page d'accueil
        </a>
    </div>
</div>

<script>
// Auto-select first role
document.querySelector('.role-option').classList.add('selected');

function selectRole(el, role) {
    document.querySelectorAll('.role-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    el.querySelector('input').checked = true;
}
</script>
</body>
</html>
