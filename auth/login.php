<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/stats.php';

if (isLoggedIn()) {
    redirect(APP_URL . '/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(postParam('email'));
    $password = postParam('password');

    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs';
    } elseif (loginUser($email, $password)) {
        redirect(APP_URL . '/index.php');
    } else {
        $error = 'Email ou mot de passe incorrect';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — MarketStudy Pro</title>
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
        .login-container {
            max-width: 440px;
            width: 100%;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 32px;
        }
        .login-logo .logo-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            box-shadow: 0 8px 32px rgba(99,102,241,0.4);
        }
        .login-logo .logo-icon i { color: white; font-size: 28px; }
        .login-logo h1 { color: white; font-size: 24px; font-weight: 700; }
        .login-logo p { color: rgba(255,255,255,0.6); font-size: 14px; margin-top: 4px; }
        .login-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .demo-accounts {
            margin-top: 24px;
            padding: 16px;
            background: var(--gray-50);
            border-radius: 12px;
            border: 1px solid var(--gray-200);
        }
        .demo-accounts h4 {
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-600);
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .demo-account {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
            background: white;
            border-radius: 8px;
            margin-bottom: 6px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid var(--gray-200);
        }
        .demo-account:hover {
            border-color: var(--primary);
            background: var(--primary-50);
        }
        .demo-account .email { font-weight: 600; color: var(--gray-700); }
        .demo-account .pass { color: var(--gray-400); }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-logo">
        <a href="<?= APP_URL ?>/landing.php" style="text-decoration:none;">
            <div class="logo-icon"><i class="fas fa-chart-pie"></i></div>
            <h1>MarketStudy Pro</h1>
            <p>Plateforme d'études de marché</p>
        </a>
    </div>

    <div class="login-card">
        <h2 style="font-size: 22px; font-weight: 700; color: var(--gray-900); margin-bottom: 4px;">Connexion</h2>
        <p style="color: var(--gray-500); font-size: 14px; margin-bottom: 28px;">Accédez à votre espace de travail</p>

        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <div><?= e($error) ?></div>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Adresse email</label>
                <div style="position: relative;">
                    <i class="fas fa-envelope" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--gray-400); font-size: 14px;"></i>
                    <input type="email" name="email" class="form-control" placeholder="vous@exemple.com" required value="<?= e(postParam('email')) ?>" style="padding-left: 40px;">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Mot de passe</label>
                <div style="position: relative;">
                    <i class="fas fa-lock" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--gray-400); font-size: 14px;"></i>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required style="padding-left: 40px;">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg btn-block" style="margin-top: 8px;">
                <i class="fas fa-sign-in-alt"></i> Se connecter
            </button>
        </form>

        <div style="text-align: center; margin-top: 20px;">
            <p style="font-size: 14px; color: var(--gray-500);">
                Pas encore de compte ? 
                <a href="<?= APP_URL ?>/auth/register.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">S'inscrire</a>
            </p>
        </div>

        <div class="demo-accounts">
            <h4><i class="fas fa-info-circle"></i> Comptes de démonstration</h4>
            <div class="demo-account" onclick="fillLogin('admin@marketstudy.com', 'admin123')">
                <div>
                    <span class="badge badge-danger" style="margin-right: 6px;">Admin</span>
                    <span class="email">admin@marketstudy.com</span>
                </div>
                <span class="pass">admin123</span>
            </div>
            <div class="demo-account" onclick="fillLogin('chercheur@marketstudy.com', 'chercheur123')">
                <div>
                    <span class="badge badge-primary" style="margin-right: 6px;">Chercheur</span>
                    <span class="email">chercheur@marketstudy.com</span>
                </div>
                <span class="pass">chercheur123</span>
            </div>
            <div class="demo-account" onclick="fillLogin('repondant@marketstudy.com', 'repondant123')">
                <div>
                    <span class="badge badge-info" style="margin-right: 6px;">Répondant</span>
                    <span class="email">repondant@marketstudy.com</span>
                </div>
                <span class="pass">repondant123</span>
            </div>
        </div>
    </div>
    <div style="text-align:center; margin-top: 24px;">
        <a href="<?= APP_URL ?>/landing.php" style="color: rgba(255,255,255,0.6); text-decoration: none; font-size: 14px; font-weight: 500; transition: color 0.2s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,0.6)'">
            <i class="fas fa-arrow-left"></i> Retour à la page d'accueil
        </a>
    </div>
</div>

<script>
function fillLogin(email, pass) {
    document.querySelector('input[name="email"]').value = email;
    document.querySelector('input[name="password"]').value = pass;
}
</script>
</body>
</html>
