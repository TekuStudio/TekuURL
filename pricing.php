<?php
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($t['pricing_title']) ?> — <?= e($t['site_name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="nb-navbar">
        <a href="index.php" class="nb-navbar-brand"><?= e($t['site_name']) ?></a>
        <div class="nb-navbar-actions">
            <a href="?lang=en" class="nb-navbar-link" style="<?= $lang === 'en' ? 'color:#fff;' : '' ?>">EN</a>
            <a href="?lang=es" class="nb-navbar-link" style="<?= $lang === 'es' ? 'color:#fff;' : '' ?>">ES</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php" class="nb-navbar-link"><?= e($t['nav_dashboard']) ?></a>
            <?php else: ?>
                <a href="login.php" class="nb-navbar-link"><?= e($t['nav_login']) ?></a>
                <a href="register.php" class="nb-btn nb-btn-filled nb-btn-sm" style="background:#FFF;color:#000;border-color:#FFF;box-shadow:3px 3px 0 #666;"><?= e($t['nav_register']) ?></a>
            <?php endif; ?>
        </div>
    </nav>

    <section style="background:#FFF;border-bottom:6px solid #000;padding:5rem 0;">
        <div class="nb-container">
            <div style="text-align:center;margin-bottom:3rem;">
                <h1 style="font-size:2.5rem;font-weight:700;text-transform:uppercase;margin-bottom:0.5rem;"><?= e($t['pricing_title']) ?></h1>
                <div style="width:60px;height:4px;background:#000;margin:0 auto 1rem;"></div>
                <p style="font-size:0.9rem;color:#666;font-weight:500;"><?= e($t['pricing_subtitle']) ?></p>
            </div>
            <div class="nb-grid-3">
                <div class="nb-card">
                    <h3 style="font-size:1rem;margin-bottom:0.5rem;"><?= e($t['pricing_free']) ?></h3>
                    <div style="font-size:2.5rem;font-weight:700;margin-bottom:1rem;">$0<span style="font-size:0.8rem;color:#666;font-weight:600;"><?= e($t['pricing_month']) ?></span></div>
                    <ul style="list-style:none;margin-bottom:1.5rem;">
                        <li style="padding:0.5rem 0;border-bottom:2px solid #F3F4F6;font-size:0.8rem;font-weight:600;"><i class="fas fa-check" style="margin-right:0.5rem;font-size:0.7rem;"></i> 100 Links</li>
                        <li style="padding:0.5rem 0;border-bottom:2px solid #F3F4F6;font-size:0.8rem;font-weight:600;"><i class="fas fa-check" style="margin-right:0.5rem;font-size:0.7rem;"></i> <?= e($lang === 'es' ? 'Analiticas Basicas' : 'Basic Analytics') ?></li>
                        <li style="padding:0.5rem 0;border-bottom:2px solid #F3F4F6;font-size:0.8rem;font-weight:600;"><i class="fas fa-check" style="margin-right:0.5rem;font-size:0.7rem;"></i> <?= e($lang === 'es' ? 'Soporte Estandar' : 'Standard Support') ?></li>
                        <li style="padding:0.5rem 0;font-size:0.8rem;font-weight:600;"><i class="fas fa-check" style="margin-right:0.5rem;font-size:0.7rem;"></i> QR Codes</li>
                    </ul>
                    <a href="register.php" class="nb-btn" style="width:100%;justify-content:center;"><?= e($t['cta_start']) ?></a>
                </div>
                <div class="nb-card" style="border-width:4px;box-shadow:8px 8px 0 #000;">
                    <div style="position:absolute;top:-12px;right:-12px;background:#000;color:#FFF;padding:4px 12px;font-size:0.55rem;font-weight:700;text-transform:uppercase;box-shadow:3px 3px 0 #000;">POPULAR</div>
                    <h3 style="font-size:1rem;margin-bottom:0.5rem;"><?= e($t['pricing_pro']) ?></h3>
                    <div style="font-size:2.5rem;font-weight:700;margin-bottom:1rem;">$9<span style="font-size:0.8rem;color:#666;font-weight:600;"><?= e($t['pricing_month']) ?></span></div>
                    <ul style="list-style:none;margin-bottom:1.5rem;">
                        <li style="padding:0.5rem 0;border-bottom:2px solid #F3F4F6;font-size:0.8rem;font-weight:600;"><i class="fas fa-check" style="margin-right:0.5rem;font-size:0.7rem;"></i> 5,000 Links</li>
                        <li style="padding:0.5rem 0;border-bottom:2px solid #F3F4F6;font-size:0.8rem;font-weight:600;"><i class="fas fa-check" style="margin-right:0.5rem;font-size:0.7rem;"></i> <?= e($lang === 'es' ? 'Analiticas Avanzadas' : 'Advanced Analytics') ?></li>
                        <li style="padding:0.5rem 0;border-bottom:2px solid #F3F4F6;font-size:0.8rem;font-weight:600;"><i class="fas fa-check" style="margin-right:0.5rem;font-size:0.7rem;"></i> <?= e($lang === 'es' ? 'Soporte Prioritario' : 'Priority Support') ?></li>
                        <li style="padding:0.5rem 0;border-bottom:2px solid #F3F4F6;font-size:0.8rem;font-weight:600;"><i class="fas fa-check" style="margin-right:0.5rem;font-size:0.7rem;"></i> <?= e($lang === 'es' ? 'Dominios Custom' : 'Custom Domains') ?></li>
                        <li style="padding:0.5rem 0;font-size:0.8rem;font-weight:600;"><i class="fas fa-check" style="margin-right:0.5rem;font-size:0.7rem;"></i> API Access</li>
                    </ul>
                    <a href="create-checkout.php?plan=pro" class="nb-btn nb-btn-filled" style="width:100%;justify-content:center;"><?= e($t['pricing_upgrade']) ?></a>
                </div>
                <div class="nb-card">
                    <h3 style="font-size:1rem;margin-bottom:0.5rem;"><?= e($t['pricing_enterprise']) ?></h3>
                    <div style="font-size:2.5rem;font-weight:700;margin-bottom:1rem;">$29<span style="font-size:0.8rem;color:#666;font-weight:600;"><?= e($t['pricing_month']) ?></span></div>
                    <ul style="list-style:none;margin-bottom:1.5rem;">
                        <li style="padding:0.5rem 0;border-bottom:2px solid #F3F4F6;font-size:0.8rem;font-weight:600;"><i class="fas fa-check" style="margin-right:0.5rem;font-size:0.7rem;"></i> <?= e($lang === 'es' ? 'Enlaces Ilimitados' : 'Unlimited Links') ?></li>
                        <li style="padding:0.5rem 0;border-bottom:2px solid #F3F4F6;font-size:0.8rem;font-weight:600;"><i class="fas fa-check" style="margin-right:0.5rem;font-size:0.7rem;"></i> <?= e($lang === 'es' ? 'Suite Completa' : 'Full Analytics Suite') ?></li>
                        <li style="padding:0.5rem 0;border-bottom:2px solid #F3F4F6;font-size:0.8rem;font-weight:600;"><i class="fas fa-check" style="margin-right:0.5rem;font-size:0.7rem;"></i> <?= e($lang === 'es' ? 'Soporte Dedicado' : 'Dedicated Support') ?></li>
                        <li style="padding:0.5rem 0;border-bottom:2px solid #F3F4F6;font-size:0.8rem;font-weight:600;"><i class="fas fa-check" style="margin-right:0.5rem;font-size:0.7rem;"></i> White Label</li>
                        <li style="padding:0.5rem 0;font-size:0.8rem;font-weight:600;"><i class="fas fa-check" style="margin-right:0.5rem;font-size:0.7rem;"></i> <?= e($lang === 'es' ? 'Integraciones Custom' : 'Custom Integrations') ?></li>
                    </ul>
                    <a href="create-checkout.php?plan=enterprise" class="nb-btn" style="width:100%;justify-content:center;"><?= e($t['pricing_upgrade']) ?></a>
                </div>
            </div>
        </div>
    </section>

    <footer style="background:#000;padding:2rem;text-align:center;">
        <div class="nb-container">
            <div style="color:#666;font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;">&copy; <?= date('Y') ?> <?= e($t['site_name']) ?> &middot; <?= e($t['footer_rights']) ?></div>
        </div>
    </footer>
    <script src="js/app.js"></script>
</body>
</html>
