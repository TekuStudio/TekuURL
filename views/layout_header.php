<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$lang = $_SESSION['lang'] ?? 'es';
$langFile = __DIR__ . "/../lang/{$lang}.php";
if (!file_exists($langFile)) { $lang = 'es'; $langFile = __DIR__ . "/../lang/es.php"; }
$t = require $langFile;

$isAdminSession = isset($_SESSION['admin_id']);
if ($isAdminSession) {
    $stmt = $tekupdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $user = $stmt->fetch();
    if (!$user || !isAdmin($user)) {
        unset($_SESSION['admin_id']);
        header("Location: " . BASE_URL . "/admin_login.php");
        exit;
    }
} else {
    $user = getCurrentUser($tekupdo);
}
$currentFile = basename($_SERVER['PHP_SELF']);
$isAdmin = isAdmin($user);

if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'es'])) {
    $_SESSION['lang'] = $_GET['lang'];
    $redirectUrl = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: " . $redirectUrl);
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($t['site_name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>
    <div style="display:flex;height:100vh;overflow:hidden;">
        <aside class="nb-sidebar" id="desktop-sidebar">
            <div class="nb-sidebar-brand">
                <a href="<?= BASE_URL ?>/dashboard.php"><?= e($t['site_name']) ?></a>
                <span class="nb-badge nb-badge-filled" style="font-size:0.5rem;"><?= e(strtoupper($user['plan_status'] ?? 'free')) ?></span>
            </div>
            <nav class="nb-sidebar-nav">
                <div class="nb-sidebar-section"><?= e($lang === 'es' ? 'Principal' : 'Main') ?></div>
                <a href="<?= BASE_URL ?>/dashboard.php" class="nb-sidebar-link <?= $currentFile === 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-chart-pie"></i> <?= e($t['nav_dashboard']) ?></a>
                <a href="<?= BASE_URL ?>/links.php" class="nb-sidebar-link <?= $currentFile === 'links.php' ? 'active' : '' ?>"><i class="fas fa-link"></i> <?= e($t['nav_links']) ?></a>
                <a href="<?= BASE_URL ?>/analytics.php" class="nb-sidebar-link <?= $currentFile === 'analytics.php' ? 'active' : '' ?>"><i class="fas fa-chart-bar"></i> <?= e($t['nav_analytics']) ?></a>

                <?php if ($isAdmin && $isAdminSession): ?>
                <div class="nb-sidebar-section" style="margin-top:0.5rem;"><?= e($lang === 'es' ? 'Admin' : 'Admin') ?></div>
                <a href="<?= BASE_URL ?>/admin/" class="nb-sidebar-link <?= $currentFile === 'index.php' && dirname($_SERVER['PHP_SELF']) === '/TekuURL/admin' ? 'active' : '' ?>"><i class="fas fa-shield-alt"></i> <?= e($lang === 'es' ? 'Panel Admin' : 'Admin Panel') ?></a>
                <a href="<?= BASE_URL ?>/admin/settings.php" class="nb-sidebar-link <?= $currentFile === 'settings.php' && dirname($_SERVER['PHP_SELF']) === '/TekuURL/admin' ? 'active' : '' ?>"><i class="fas fa-history"></i> <?= e($t['admin_audit_log']) ?></a>
                <?php endif; ?>

                <div class="nb-sidebar-section" style="margin-top:0.5rem;"><?= e($lang === 'es' ? 'Cuenta' : 'Account') ?></div>
                <a href="<?= BASE_URL ?>/settings.php" class="nb-sidebar-link <?= $currentFile === 'settings.php' ? 'active' : '' ?>"><i class="fas fa-cog"></i> <?= e($t['nav_settings']) ?></a>
                <a href="<?= BASE_URL ?>/pricing.php" class="nb-sidebar-link <?= $currentFile === 'pricing.php' ? 'active' : '' ?>"><i class="fas fa-gem"></i> <?= e($t['nav_upgrade']) ?></a>
            </nav>
            <div class="nb-sidebar-footer">
                <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.75rem;">
                    <div class="nb-avatar" style="width:32px;height:32px;font-size:0.7rem;border-width:2px;"><?= e(substr($user['email'] ?? 'U', 0, 1)) ?></div>
                    <div style="overflow:hidden;">
                        <div style="font-size:0.7rem;font-weight:700;text-transform:uppercase;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($user['email'] ?? '') ?></div>
                        <div style="font-size:0.55rem;color:#999;text-transform:uppercase;font-weight:600;"><?= $isAdminSession ? 'ADMIN' : e(strtoupper($user['plan_status'] ?? 'free')) ?></div>
                    </div>
                </div>
                <div style="display:flex;gap:0.5rem;">
                    <a href="?lang=en" class="nb-tag <?= $lang === 'en' ? 'nb-tag-filled' : '' ?>" style="flex:1;justify-content:center;font-size:0.55rem;">EN</a>
                    <a href="?lang=es" class="nb-tag <?= $lang === 'es' ? 'nb-tag-filled' : '' ?>" style="flex:1;justify-content:center;font-size:0.55rem;">ES</a>
                    <a href="<?= BASE_URL ?>/<?= $isAdminSession ? 'admin_logout.php' : 'logout.php' ?>" class="nb-tag" style="flex:1;justify-content:center;font-size:0.55rem;"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>
        </aside>

        <div style="flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0;">
            <div id="mobile-top" class="nb-navbar" style="display:none;">
                <a href="<?= BASE_URL ?>/dashboard.php" class="nb-navbar-brand" style="font-size:0.85rem;"><?= e($t['site_name']) ?></a>
                <div class="nb-navbar-actions">
                    <a href="?lang=en" class="nb-navbar-link" style="<?= $lang === 'en' ? 'color:#fff;' : '' ?>">EN</a>
                    <a href="?lang=es" class="nb-navbar-link" style="<?= $lang === 'es' ? 'color:#fff;' : '' ?>">ES</a>
                    <a href="<?= BASE_URL ?>/logout.php" class="nb-navbar-link"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>
            <main style="flex:1;overflow-y:auto;padding:2rem;" id="main-content">
                <?php $flash = getFlash(); if ($flash): ?>
                <script>document.addEventListener('DOMContentLoaded',function(){showToast('<?= e($flash['message']) ?>','<?= e($flash['type']) ?>');});</script>
                <?php endif; ?>
                <style>
                    @media(min-width:1024px){#mobile-top{display:none!important}}
                    @media(max-width:1023px){#mobile-top{display:flex!important}#main-content{padding:1rem}}
                </style>
