<?php
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/config.php';
$loggedIn = isLoggedIn();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($t['site_name']) ?> — <?= e($t['tagline']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .hero-stats{display:flex;gap:2.5rem;justify-content:center;margin-top:2.5rem;}
        .hero-stat-num{font-size:2rem;font-weight:700;line-height:1;}
        .hero-stat-label{font-size:0.6rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#999;margin-top:0.25rem;}
        .feature-icon{width:56px;height:56px;background:#000;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:1.25rem;border:3px solid #000;box-shadow:4px 4px 0 #000;position:relative;}
        .feature-icon::after{content:'';position:absolute;bottom:-4px;left:-4px;width:100%;height:100%;border:2px solid #E5E7EB;z-index:-1;}
        .step-num{width:32px;height:32px;background:#000;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:0.85rem;border:2px solid #000;margin-bottom:0.75rem;}
    </style>
</head>
<body>
    <nav class="nb-navbar">
        <a href="index.php" class="nb-navbar-brand" style="font-size:1.1rem;"><?= e($t['site_name']) ?></a>
        <div class="nb-navbar-actions">
            <a href="?lang=en" class="nb-navbar-link" style="<?= $lang === 'en' ? 'color:#fff;' : '' ?>">EN</a>
            <a href="?lang=es" class="nb-navbar-link" style="<?= $lang === 'es' ? 'color:#fff;' : '' ?>">ES</a>
            <a href="pricing.php" class="nb-navbar-link"><?= e($t['nav_pricing']) ?></a>
            <?php if ($loggedIn): ?>
                <a href="dashboard.php" class="nb-btn nb-btn-filled nb-btn-sm" style="background:#FFF;color:#000;border-color:#FFF;box-shadow:3px 3px 0 #666;"><?= e($t['nav_dashboard']) ?></a>
            <?php else: ?>
                <a href="login.php" class="nb-navbar-link"><?= e($t['nav_login']) ?></a>
                <a href="register.php" class="nb-btn nb-btn-filled nb-btn-sm" style="background:#FFF;color:#000;border-color:#FFF;box-shadow:3px 3px 0 #666;"><?= e($t['nav_register']) ?></a>
            <?php endif; ?>
        </div>
    </nav>

    <section style="background:#FFF;border-bottom:6px solid #000;padding:5rem 0 4rem;">
        <div class="nb-container" style="text-align:center;">
            <div style="display:inline-block;padding:0.35rem 1rem;border:2px solid #000;font-size:0.6rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:1.5rem;"><?= e($lang === 'es' ? 'Plataforma de Enlaces SaaS' : 'SaaS Link Platform') ?></div>
            <h1 style="font-size:clamp(2.5rem,6vw,4rem);font-weight:700;text-transform:uppercase;letter-spacing:-0.04em;line-height:0.9;margin-bottom:1.5rem;"><?= e($t['hero_title']) ?></h1>
            <p style="font-size:1.05rem;font-weight:500;color:#666;max-width:520px;margin:0 auto 2.5rem;line-height:1.5;"><?= e($t['hero_subtitle']) ?></p>
            <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
                <?php if ($loggedIn): ?>
                    <a href="dashboard.php" class="nb-btn nb-btn-filled" style="font-size:0.9rem;padding:1rem 2.5rem;"><?= e($t['nav_dashboard']) ?> <i class="fas fa-arrow-right" style="margin-left:0.5rem;"></i></a>
                <?php else: ?>
                    <a href="register.php" class="nb-btn nb-btn-filled" style="font-size:0.9rem;padding:1rem 2.5rem;"><?= e($t['cta_start']) ?> <i class="fas fa-arrow-right" style="margin-left:0.5rem;"></i></a>
                <?php endif; ?>
                <a href="pricing.php" class="nb-btn" style="font-size:0.9rem;padding:1rem 2.5rem;"><?= e($t['cta_pricing']) ?></a>
            </div>
        </div>
    </section>

    <section style="background:#F3F4F6;border-bottom:6px solid #000;padding:4rem 0;">
        <div class="nb-container">
            <div style="text-align:center;margin-bottom:3rem;">
                <h2 style="font-size:2rem;font-weight:700;text-transform:uppercase;margin-bottom:0.5rem;"><?= e($lang === 'es' ? 'Todo lo que Necesitas' : 'Everything You Need') ?></h2>
                <div style="width:60px;height:4px;background:#000;margin:0 auto;"></div>
            </div>
            <div class="nb-grid-3">
                <div class="nb-card" style="text-align:center;">
                    <div class="feature-icon" style="margin:0 auto 1.25rem;"><i class="fas fa-link"></i></div>
                    <h3 style="font-size:0.95rem;margin-bottom:0.5rem;"><?= e($lang === 'es' ? 'Enlaces Cortos' : 'Short Links') ?></h3>
                    <p style="font-size:0.8rem;color:#666;line-height:1.5;"><?= e($lang === 'es' ? 'Crea URLs cortas con codigos personalizados y dominios propios.' : 'Create short URLs with custom codes and your own domains.') ?></p>
                </div>
                <div class="nb-card" style="text-align:center;">
                    <div class="feature-icon" style="margin:0 auto 1.25rem;"><i class="fas fa-chart-bar"></i></div>
                    <h3 style="font-size:0.95rem;margin-bottom:0.5rem;"><?= e($lang === 'es' ? 'Analiticas en Tiempo Real' : 'Real-Time Analytics') ?></h3>
                    <p style="font-size:0.8rem;color:#666;line-height:1.5;"><?= e($lang === 'es' ? 'Rastrea clics, paises, dispositivos, navegadores y referentes.' : 'Track clicks, countries, devices, browsers, and referrers.') ?></p>
                </div>
                <div class="nb-card" style="text-align:center;">
                    <div class="feature-icon" style="margin:0 auto 1.25rem;"><i class="fas fa-qrcode"></i></div>
                    <h3 style="font-size:0.95rem;margin-bottom:0.5rem;"><?= e($lang === 'es' ? 'QR Codes Dinamicos' : 'Dynamic QR Codes') ?></h3>
                    <p style="font-size:0.8rem;color:#666;line-height:1.5;"><?= e($lang === 'es' ? 'Genera QR codes automaticos para cada enlace, listos para descargar.' : 'Auto-generated QR codes for every link, ready to download.') ?></p>
                </div>
            </div>
            <div class="nb-grid-3" style="margin-top:1.5rem;">
                <div class="nb-card" style="text-align:center;">
                    <div class="feature-icon" style="margin:0 auto 1.25rem;"><i class="fas fa-mobile-alt"></i></div>
                    <h3 style="font-size:0.95rem;margin-bottom:0.5rem;"><?= e($lang === 'es' ? 'Dispositivos' : 'Device Tracking') ?></h3>
                    <p style="font-size:0.8rem;color:#666;line-height:1.5;"><?= e($lang === 'es' ? 'Detecta automaticamente si tus visitantes usan movil, tablet o escritorio.' : 'Automatically detect if your visitors use mobile, tablet, or desktop.') ?></p>
                </div>
                <div class="nb-card" style="text-align:center;">
                    <div class="feature-icon" style="margin:0 auto 1.25rem;"><i class="fas fa-globe"></i></div>
                    <h3 style="font-size:0.95rem;margin-bottom:0.5rem;"><?= e($lang === 'es' ? 'Geolocalizacion' : 'Geo Tracking') ?></h3>
                    <p style="font-size:0.8rem;color:#666;line-height:1.5;"><?= e($lang === 'es' ? 'Ubica a tu audiencia por pais y ciudad con precision.' : 'Locate your audience by country and city with precision.') ?></p>
                </div>
                <div class="nb-card" style="text-align:center;">
                    <div class="feature-icon" style="margin:0 auto 1.25rem;"><i class="fas fa-shield-alt"></i></div>
                    <h3 style="font-size:0.95rem;margin-bottom:0.5rem;"><?= e($lang === 'es' ? 'Seguro y Rapido' : 'Secure & Fast') ?></h3>
                    <p style="font-size:0.8rem;color:#666;line-height:1.5;"><?= e($lang === 'es' ? 'Redirecciones 301 rapidas, enlaces protegidos y datos encriptados.' : 'Fast 301 redirects, protected links, and encrypted data.') ?></p>
                </div>
            </div>
        </div>
    </section>

    <section style="background:#FFF;border-bottom:6px solid #000;padding:4rem 0;">
        <div class="nb-container">
            <div style="text-align:center;margin-bottom:3rem;">
                <h2 style="font-size:2rem;font-weight:700;text-transform:uppercase;margin-bottom:0.5rem;"><?= e($lang === 'es' ? 'Como Funciona' : 'How It Works') ?></h2>
                <div style="width:60px;height:4px;background:#000;margin:0 auto;"></div>
            </div>
            <div class="nb-grid-3">
                <div style="text-align:center;">
                    <div class="step-num">1</div>
                    <h3 style="font-size:0.9rem;margin-bottom:0.5rem;"><?= e($lang === 'es' ? 'Registrate' : 'Sign Up') ?></h3>
                    <p style="font-size:0.8rem;color:#666;"><?= e($lang === 'es' ? 'Crea tu cuenta gratis en segundos.' : 'Create your free account in seconds.') ?></p>
                </div>
                <div style="text-align:center;">
                    <div class="step-num">2</div>
                    <h3 style="font-size:0.9rem;margin-bottom:0.5rem;"><?= e($lang === 'es' ? 'Acorta' : 'Shorten') ?></h3>
                    <p style="font-size:0.8rem;color:#666;"><?= e($lang === 'es' ? 'Pega tu URL larga y obtén un enlace corto al instante.' : 'Paste your long URL and get a short link instantly.') ?></p>
                </div>
                <div style="text-align:center;">
                    <div class="step-num">3</div>
                    <h3 style="font-size:0.9rem;margin-bottom:0.5rem;"><?= e($lang === 'es' ? 'Analiza' : 'Track') ?></h3>
                    <p style="font-size:0.8rem;color:#666;"><?= e($lang === 'es' ? 'Mira las analiticas en tiempo real de cada clic.' : 'View real-time analytics for every click.') ?></p>
                </div>
            </div>
        </div>
    </section>

    <section id="pricing" style="background:#F3F4F6;border-bottom:6px solid #000;padding:5rem 0;">
        <div class="nb-container">
            <div style="text-align:center;margin-bottom:3rem;">
                <h2 style="font-size:2rem;font-weight:700;text-transform:uppercase;margin-bottom:0.5rem;"><?= e($t['pricing_title']) ?></h2>
                <div style="width:60px;height:4px;background:#000;margin:0 auto 1rem;"></div>
                <p style="font-size:0.85rem;color:#666;font-weight:500;"><?= e($t['pricing_subtitle']) ?></p>
            </div>
            <div class="nb-grid-3">
                <div class="nb-card">
                    <h3 style="font-size:1rem;margin-bottom:0.5rem;"><?= e($t['pricing_free']) ?></h3>
                    <div style="font-size:2.5rem;font-weight:700;margin-bottom:0.5rem;">$0<span style="font-size:0.8rem;color:#666;font-weight:600;"><?= e($t['pricing_month']) ?></span></div>
                    <p style="font-size:0.75rem;color:#666;margin-bottom:1.5rem;font-weight:600;line-height:1.5;"><?= e($t['pricing_free_features']) ?></p>
                    <a href="<?= $loggedIn ? 'dashboard.php' : 'register.php' ?>" class="nb-btn" style="width:100%;justify-content:center;"><?= e($t['cta_start']) ?></a>
                </div>
                <div class="nb-card" style="border-width:4px;box-shadow:8px 8px 0 #000;">
                    <div style="position:absolute;top:-12px;right:-12px;background:#000;color:#FFF;padding:4px 12px;font-size:0.55rem;font-weight:700;text-transform:uppercase;box-shadow:3px 3px 0 #000;">POPULAR</div>
                    <h3 style="font-size:1rem;margin-bottom:0.5rem;"><?= e($t['pricing_pro']) ?></h3>
                    <div style="font-size:2.5rem;font-weight:700;margin-bottom:0.5rem;">$9<span style="font-size:0.8rem;color:#666;font-weight:600;"><?= e($t['pricing_month']) ?></span></div>
                    <p style="font-size:0.75rem;color:#666;margin-bottom:1.5rem;font-weight:600;line-height:1.5;"><?= e($t['pricing_pro_features']) ?></p>
                    <a href="create-checkout.php?plan=pro" class="nb-btn nb-btn-filled" style="width:100%;justify-content:center;"><?= e($t['pricing_upgrade']) ?></a>
                </div>
                <div class="nb-card">
                    <h3 style="font-size:1rem;margin-bottom:0.5rem;"><?= e($t['pricing_enterprise']) ?></h3>
                    <div style="font-size:2.5rem;font-weight:700;margin-bottom:0.5rem;">$29<span style="font-size:0.8rem;color:#666;font-weight:600;"><?= e($t['pricing_month']) ?></span></div>
                    <p style="font-size:0.75rem;color:#666;margin-bottom:1.5rem;font-weight:600;line-height:1.5;"><?= e($t['pricing_enterprise_features']) ?></p>
                    <a href="create-checkout.php?plan=enterprise" class="nb-btn" style="width:100%;justify-content:center;"><?= e($t['pricing_upgrade']) ?></a>
                </div>
            </div>
        </div>
    </section>

    <section style="background:#000;padding:4rem 0;">
        <div class="nb-container" style="text-align:center;">
            <h2 style="font-size:2rem;font-weight:700;text-transform:uppercase;color:#FFF;margin-bottom:1rem;"><?= e($lang === 'es' ? 'Listo para Empezar?' : 'Ready to Get Started?') ?></h2>
            <p style="font-size:0.85rem;color:#666;margin-bottom:2rem;max-width:400px;margin-left:auto;margin-right:auto;"><?= e($lang === 'es' ? 'Crea tu cuenta y empieza a rastrear tus enlaces hoy.' : 'Create your account and start tracking your links today.') ?></p>
            <?php if ($loggedIn): ?>
            <a href="dashboard.php" class="nb-btn nb-btn-filled" style="background:#FFF;color:#000;border-color:#FFF;box-shadow:3px 3px 0 #666;font-size:0.9rem;padding:1rem 2.5rem;"><?= e($t['nav_dashboard']) ?> <i class="fas fa-arrow-right" style="margin-left:0.5rem;"></i></a>
            <?php else: ?>
            <a href="register.php" class="nb-btn nb-btn-filled" style="background:#FFF;color:#000;border-color:#FFF;box-shadow:3px 3px 0 #666;font-size:0.9rem;padding:1rem 2.5rem;"><?= e($t['cta_start']) ?> <i class="fas fa-arrow-right" style="margin-left:0.5rem;"></i></a>
            <?php endif; ?>
        </div>
    </section>

    <footer style="background:#FFF;border-top:4px solid #000;padding:2rem 0;">
        <div class="nb-container">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
                <div style="display:flex;align-items:center;gap:1.5rem;">
                    <span style="font-weight:700;font-size:0.85rem;text-transform:uppercase;"><?= e($t['site_name']) ?></span>
                    <a href="pricing.php" style="font-size:0.7rem;font-weight:600;color:#666;text-transform:uppercase;"><?= e($t['nav_pricing']) ?></a>
                    <?php if ($loggedIn): ?>
                    <a href="dashboard.php" style="font-size:0.7rem;font-weight:600;color:#666;text-transform:uppercase;"><?= e($t['nav_dashboard']) ?></a>
                    <?php else: ?>
                    <a href="login.php" style="font-size:0.7rem;font-weight:600;color:#666;text-transform:uppercase;"><?= e($t['nav_login']) ?></a>
                    <a href="register.php" style="font-size:0.7rem;font-weight:600;color:#666;text-transform:uppercase;"><?= e($t['nav_register']) ?></a>
                    <?php endif; ?>
                </div>
                <div style="display:flex;gap:0.5rem;">
                    <a href="?lang=en" class="nb-tag <?= $lang === 'en' ? 'nb-tag-filled' : '' ?>" style="font-size:0.5rem;">EN</a>
                    <a href="?lang=es" class="nb-tag <?= $lang === 'es' ? 'nb-tag-filled' : '' ?>" style="font-size:0.5rem;">ES</a>
                </div>
            </div>
            <div style="border-top:2px solid #F3F4F6;margin-top:1.5rem;padding-top:1rem;text-align:center;">
                <span style="font-size:0.65rem;font-weight:600;color:#999;text-transform:uppercase;letter-spacing:0.1em;">&copy; <?= date('Y') ?> <?= e($t['site_name']) ?> &middot; <?= e($t['footer_rights']) ?></span>
            </div>
        </div>
    </footer>
    <script src="js/app.js"></script>
</body>
</html>
