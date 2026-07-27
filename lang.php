<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_trans_sid', 0);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => 1,
        'samesite' => 'Lax'
    ]);
    session_start();
}
$lang = $_SESSION['lang'] ?? 'es';
$langFile = __DIR__ . "/lang/{$lang}.php";
if (!file_exists($langFile)) { $lang = 'es'; $langFile = __DIR__ . "/lang/es.php"; }
$t = require $langFile;

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
