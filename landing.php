<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/stats.php';

if (isLoggedIn()) {
    redirect(APP_URL . '/index.php');
}

$db = getDB();
try {
    $nb_etudes = $db->query("SELECT COUNT(*) FROM etudes")->fetchColumn();
    $nb_respondents = $db->query("SELECT COUNT(*) FROM respondents WHERE statut = 'termine'")->fetchColumn();
} catch (Exception $e) { $nb_etudes = 0; $nb_respondents = 0; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MarketStudy Pro — Études de marché & analyses statistiques</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #4f46e5;
            --accent: #ec4899;
            --dark: #0a0613;
        }
        body { font-family: 'Inter', sans-serif; color: #374151; overflow-x: hidden; }

        /* ===== Navbar ===== */
        .navbar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            padding: 16px 0; transition: all 0.3s;
        }
        .navbar.scrolled {
            background: rgba(10,6,19,0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.06);
            padding: 10px 0;
        }
        .nav-inner {
            max-width: 1200px; margin: 0 auto; padding: 0 32px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .nav-brand { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .nav-brand .logo {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 11px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 16px rgba(79,70,229,0.4);
        }
        .nav-brand .logo i { color: white; font-size: 18px; }
        .nav-brand span { color: white; font-size: 18px; font-weight: 800; letter-spacing: -0.3px; }
        .nav-actions { display: flex; gap: 10px; align-items: center; }
        .nav-actions a {
            color: rgba(255,255,255,0.7); text-decoration: none;
            font-size: 14px; font-weight: 600; padding: 9px 22px;
            border-radius: 10px; transition: all 0.2s;
        }
        .nav-actions a:hover { color: white; background: rgba(255,255,255,0.08); }
        .nav-actions a.cta {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white; box-shadow: 0 4px 16px rgba(79,70,229,0.35);
        }
        .nav-actions a.cta:hover { transform: translateY(-1px); box-shadow: 0 6px 24px rgba(79,70,229,0.5); }

        /* ===== Hero ===== */
        .hero {
            min-height: 100vh;
            background: #0a0613;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 100px 32px 60px;
            overflow: hidden;
        }
        /* Animated gradient orbs */
        .hero .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.5;
            animation: orbFloat 12s ease-in-out infinite;
        }
        .hero .orb-1 {
            width: 500px; height: 500px;
            background: radial-gradient(circle, #4f46e5, transparent 70%);
            top: -10%; left: -5%;
        }
        .hero .orb-2 {
            width: 400px; height: 400px;
            background: radial-gradient(circle, #ec4899, transparent 70%);
            bottom: -10%; right: -5%;
            animation-delay: -4s;
        }
        .hero .orb-3 {
            width: 350px; height: 350px;
            background: radial-gradient(circle, #0ea5e9, transparent 70%);
            top: 40%; left: 50%;
            animation-delay: -8s;
        }
        @keyframes orbFloat {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(40px, -30px) scale(1.1); }
            66% { transform: translate(-30px, 20px) scale(0.95); }
        }
        /* Grid overlay */
        .hero::after {
            content: '';
            position: absolute; inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
        }
        .hero-content { position: relative; z-index: 3; max-width: 820px; }

        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 7px 18px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 100px;
            color: rgba(255,255,255,0.7);
            font-size: 12px; font-weight: 600;
            margin-bottom: 32px;
            backdrop-filter: blur(10px);
            letter-spacing: 0.5px;
        }
        .hero-badge .dot {
            width: 7px; height: 7px;
            background: #10b981; border-radius: 50%;
            box-shadow: 0 0 8px #10b981;
            animation: dotPulse 2s infinite;
        }
        @keyframes dotPulse {
            0%, 100% { opacity: 1; } 50% { opacity: 0.4; }
        }

        .hero h1 {
            font-size: clamp(40px, 7vw, 80px);
            font-weight: 900;
            color: white;
            line-height: 1.05;
            letter-spacing: -3px;
            margin-bottom: 28px;
        }
        .hero h1 .gradient {
            background: linear-gradient(135deg, #818cf8 0%, #ec4899 50%, #f59e0b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero .sub {
            font-size: clamp(16px, 2.2vw, 21px);
            color: rgba(255,255,255,0.5);
            max-width: 600px;
            margin: 0 auto 44px;
            line-height: 1.6;
            font-weight: 400;
        }

        .hero-cta {
            display: flex; gap: 14px; justify-content: center; flex-wrap: wrap;
        }
        .btn-go {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white; padding: 16px 38px; border-radius: 14px;
            text-decoration: none; font-size: 16px; font-weight: 700;
            transition: all 0.3s;
            box-shadow: 0 8px 30px rgba(79,70,229,0.5);
            display: inline-flex; align-items: center; gap: 10px;
        }
        .btn-go:hover { transform: translateY(-3px); box-shadow: 0 12px 40px rgba(79,70,229,0.65); }
        .btn-ghost-hero {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.15);
            color: white; padding: 16px 38px; border-radius: 14px;
            text-decoration: none; font-size: 16px; font-weight: 600;
            transition: all 0.3s; backdrop-filter: blur(10px);
            display: inline-flex; align-items: center; gap: 10px;
        }
        .btn-ghost-hero:hover { background: rgba(255,255,255,0.12); border-color: rgba(255,255,255,0.25); }

        .hero-stats {
            display: flex; gap: 56px; justify-content: center;
            margin-top: 72px; flex-wrap: wrap;
        }
        .hstat .num {
            font-size: 38px; font-weight: 800;
            background: linear-gradient(135deg, #818cf8, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hstat .label {
            font-size: 12px; color: rgba(255,255,255,0.4);
            margin-top: 2px; text-transform: uppercase; letter-spacing: 1.5px;
        }

        /* ===== Features (compact) ===== */
        .features {
            padding: 90px 32px;
            background: white;
        }
        .features-inner { max-width: 1100px; margin: 0 auto; }
        .features h2 {
            text-align: center;
            font-size: clamp(26px, 4vw, 38px);
            font-weight: 800; color: #111827;
            letter-spacing: -1px; margin-bottom: 12px;
        }
        .features .sub {
            text-align: center; font-size: 17px; color: #6b7280;
            margin-bottom: 52px;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        @media (max-width: 768px) { .features-grid { grid-template-columns: 1fr; } }
        .fcard {
            padding: 28px;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        .fcard::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            transform: scaleX(0); transform-origin: left;
            transition: transform 0.3s;
        }
        .fcard:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 40px rgba(0,0,0,0.08);
            border-color: #d1d5db;
        }
        .fcard:hover::before { transform: scaleX(1); }
        .fcard .ficon {
            width: 50px; height: 50px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 21px; margin-bottom: 16px;
        }
        .fcard h3 { font-size: 17px; font-weight: 700; color: #111827; margin-bottom: 8px; }
        .fcard p { font-size: 14px; color: #6b7280; line-height: 1.6; }

        /* ===== Roles (compact) ===== */
        .roles {
            padding: 90px 32px;
            background: #f9fafb;
        }
        .roles-inner { max-width: 1100px; margin: 0 auto; }
        .roles h2 {
            text-align: center;
            font-size: clamp(26px, 4vw, 38px);
            font-weight: 800; color: #111827;
            letter-spacing: -1px; margin-bottom: 12px;
        }
        .roles .sub {
            text-align: center; font-size: 17px; color: #6b7280;
            margin-bottom: 52px;
        }
        .roles-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        @media (max-width: 768px) { .roles-grid { grid-template-columns: 1fr; } }
        .rcard {
            background: white;
            border-radius: 18px;
            padding: 32px 24px;
            text-align: center;
            border: 2px solid transparent;
            transition: all 0.3s;
        }
        .rcard:hover {
            border-color: #4f46e5;
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(79,70,229,0.1);
        }
        .rcard .ravatar {
            width: 64px; height: 64px; border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px; font-size: 26px; color: white;
        }
        .rcard h3 { font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 4px; }
        .rcard .rtag { font-size: 11px; color: #9ca3af; margin-bottom: 14px; text-transform: uppercase; letter-spacing: 1px; }
        .rcard p { font-size: 13px; color: #6b7280; line-height: 1.6; }

        /* ===== CTA ===== */
        .cta {
            padding: 90px 32px;
            background: #0a0613;
            text-align: center;
            position: relative; overflow: hidden;
        }
        .cta::before {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(circle at 50% 50%, rgba(79,70,229,0.15), transparent 60%);
        }
        .cta-inner { position: relative; z-index: 2; max-width: 600px; margin: 0 auto; }
        .cta h2 {
            font-size: clamp(26px, 4vw, 42px); font-weight: 800;
            color: white; letter-spacing: -1px; margin-bottom: 16px;
        }
        .cta p {
            font-size: 17px; color: rgba(255,255,255,0.5);
            margin-bottom: 36px; line-height: 1.6;
        }

        /* ===== Footer ===== */
        .footer {
            background: #0a0613;
            border-top: 1px solid rgba(255,255,255,0.06);
            padding: 32px;
            text-align: center;
            color: rgba(255,255,255,0.3);
            font-size: 13px;
        }
        .footer a { color: rgba(255,255,255,0.5); text-decoration: none; }
        .footer a:hover { color: white; }

        /* ===== Nav links ===== */
        .nav-links {
            display: flex; align-items: center; gap: 4px;
        }
        .nav-links a {
            color: rgba(255,255,255,0.6); text-decoration: none;
            font-size: 14px; font-weight: 500; padding: 8px 16px;
            border-radius: 8px; transition: all 0.2s;
        }
        .nav-links a:hover { color: white; background: rgba(255,255,255,0.08); }
        .nav-toggle {
            display: none; background: none; border: none;
            color: white; font-size: 22px; cursor: pointer;
        }
        @media (max-width: 900px) {
            .nav-links { display: none; }
            .nav-links.open {
                display: flex; flex-direction: column;
                position: absolute; top: 100%; left: 0; right: 0;
                background: rgba(10,6,19,0.95); backdrop-filter: blur(20px);
                padding: 16px 32px; gap: 4px;
                border-bottom: 1px solid rgba(255,255,255,0.08);
            }
            .nav-links.open a { padding: 12px 16px; }
            .nav-toggle { display: block; }
            .nav-actions { gap: 6px; }
            .nav-actions a { padding: 8px 14px; font-size: 13px; }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar" id="navbar">
    <div class="nav-inner">
        <a href="<?= APP_URL ?>/landing.php" class="nav-brand">
            <div class="logo"><i class="fas fa-chart-pie"></i></div>
            <span>MarketStudy Pro</span>
        </a>
        <div class="nav-links" id="navLinks">
            <a href="#features">Fonctionnalités</a>
            <a href="#roles">Acteurs</a>
            <a href="<?= APP_URL ?>/auth/login.php">Connexion</a>
            <a href="<?= APP_URL ?>/auth/register.php">Inscription</a>
        </div>
        <div class="nav-actions">
            <a href="<?= APP_URL ?>/auth/login.php">Connexion</a>
            <a href="<?= APP_URL ?>/auth/register.php" class="cta">S'inscrire <i class="fas fa-arrow-right" style="font-size:11px;"></i></a>
            <button class="nav-toggle" onclick="document.getElementById('navLinks').classList.toggle('open')"><i class="fas fa-bars"></i></button>
        </div>
    </div>
</nav>

<!-- Hero -->
<section class="hero">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <div class="hero-content">
        <div class="hero-badge">
            <span class="dot"></span>
            Plateforme complète d'études de marché
        </div>

        <h1>
            Études de marché<br>
            <span class="gradient">& analyses statistiques</span>
        </h1>

        <p class="sub">
            Du questionnaire à l'analyse : tris, tests, ACP, classification
            et rapport PDF. Tout le cycle en une seule plateforme.
        </p>

        <div class="hero-cta">
            <a href="<?= APP_URL ?>/auth/register.php" class="btn-go">
                <i class="fas fa-rocket"></i> Commencer
            </a>
            <a href="<?= APP_URL ?>/auth/login.php" class="btn-ghost-hero">
                <i class="fas fa-sign-in-alt"></i> Se connecter
            </a>
        </div>

        <div class="hero-stats">
            <div class="hstat">
                <div class="num"><?= max($nb_etudes, 1) ?></div>
                <div class="label">Études</div>
            </div>
            <div class="hstat">
                <div class="num"><?= max($nb_respondents, 30) ?></div>
                <div class="label">Répondants</div>
            </div>
            <div class="hstat">
                <div class="num">15+</div>
                <div class="label">Analyses</div>
            </div>
        </div>
    </div>
</section>

<!-- Features (compact, 6 cards) -->
<section class="features">
    <div class="features-inner">
        <h2>Tout le cycle d'étude, simplement</h2>
        <p class="sub">De la conception du questionnaire au rapport final</p>

        <div class="features-grid">
            <div class="fcard">
                <div class="ficon" style="background:linear-gradient(135deg,#4f46e5,#818cf8);color:white;"><i class="fas fa-clipboard-list"></i></div>
                <h3>Questionnaire</h3>
                <p>Fermées, Likert, ouvertes, numériques. Sections et contrôles de cohérence.</p>
            </div>
            <div class="fcard">
                <div class="ficon" style="background:linear-gradient(135deg,#ec4899,#f59e0b);color:white;"><i class="fas fa-share-nodes"></i></div>
                <h3>Distribution</h3>
                <p>Lien, QR code et email avec tokens uniques. Suivi des invitations.</p>
            </div>
            <div class="fcard">
                <div class="ficon" style="background:linear-gradient(135deg,#10b981,#0ea5e9);color:white;"><i class="fas fa-chart-column"></i></div>
                <h3>Tris & tableaux</h3>
                <p>Tris à plat (effectifs, %), tris croisés et tableaux de contingence.</p>
            </div>
            <div class="fcard">
                <div class="ficon" style="background:linear-gradient(135deg,#ef4444,#ec4899);color:white;"><i class="fas fa-calculator"></i></div>
                <h3>Tests statistiques</h3>
                <p>Khi², V de Cramer, t de Student, ANOVA, Pearson & Spearman.</p>
            </div>
            <div class="fcard">
                <div class="ficon" style="background:linear-gradient(135deg,#8b5cf6,#6366f1);color:white;"><i class="fas fa-diagram-project"></i></div>
                <h3>Analyse multivariée</h3>
                <p>ACP, CAH, K-means avec interprétation textuelle automatique.</p>
            </div>
            <div class="fcard">
                <div class="ficon" style="background:linear-gradient(135deg,#f59e0b,#ef4444);color:white;"><i class="fas fa-file-pdf"></i></div>
                <h3>Rapport PDF</h3>
                <p>Rapport complet avec graphiques et interprétations. Export PDF.</p>
            </div>
        </div>
    </div>
</section>

<!-- Roles (3 acteurs) -->
<section class="roles">
    <div class="roles-inner">
        <h2>Trois acteurs, une plateforme</h2>
        <p class="sub">Des rôles adaptés à chaque profil d'utilisateur</p>
        <div class="roles-grid">
            <div class="rcard">
                <div class="ravatar" style="background:linear-gradient(135deg,#ef4444,#ec4899);"><i class="fas fa-user-shield"></i></div>
                <h3>Administrateur</h3>
                <div class="rtag">Accès complet</div>
                <p>Supervise la plateforme, gère les utilisateurs et accède à toutes les études et analyses.</p>
            </div>
            <div class="rcard">
                <div class="ravatar" style="background:linear-gradient(135deg,#4f46e5,#818cf8);"><i class="fas fa-user-graduate"></i></div>
                <h3>Chercheur</h3>
                <div class="rtag">Études & analyses</div>
                <p>Crée des études, construit des questionnaires, distribue les enquêtes et analyse les résultats.</p>
            </div>
            <div class="rcard">
                <div class="ravatar" style="background:linear-gradient(135deg,#0ea5e9,#10b981);"><i class="fas fa-user"></i></div>
                <h3>Répondant</h3>
                <div class="rtag">Participation</div>
                <p>Participe aux enquêtes via des liens sécurisés avec tokens uniques. Interface intuitive.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta">
    <div class="cta-inner">
        <h2>Prêt à commencer ?</h2>
        <p>Créez votre compte et lancez votre première étude en minutes.</p>
        <div class="hero-cta">
            <a href="<?= APP_URL ?>/auth/register.php" class="btn-go">
                <i class="fas fa-rocket"></i> Créer mon compte
            </a>
            <a href="<?= APP_URL ?>/auth/login.php" class="btn-ghost-hero">
                <i class="fas fa-sign-in-alt"></i> Se connecter
            </a>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <p>&copy; <?= date('Y') ?> MarketStudy Pro · <a href="<?= APP_URL ?>/auth/login.php">Connexion</a></p>
</footer>

<script>
window.addEventListener('scroll', function() {
    document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 40);
});

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(function(a) {
    a.addEventListener('click', function(e) {
        var target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth' });
            document.getElementById('navLinks').classList.remove('open');
        }
    });
});
</script>
</body>
</html>
