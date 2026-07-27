<?php
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/config.php';

$code = $_GET['code'] ?? '';
if (empty($code)) {
    header("HTTP/1.1 404 Not Found");
    include __DIR__ . '/views/404.php';
    exit;
}

$stmt = $tekupdo->prepare("SELECT id, original_url, is_active, expires_at, link_password_hash FROM shortened_urls WHERE short_code = ? LIMIT 1");
$stmt->execute([$code]);
$urlData = $stmt->fetch();

if (!$urlData || $urlData['is_active'] == 0) {
    header("HTTP/1.1 404 Not Found");
    include __DIR__ . '/views/404.php';
    exit;
}

if (isLinkExpired($urlData['expires_at'])) {
    $lang = $_SESSION['lang'] ?? 'es';
    $langFile = __DIR__ . "/lang/{$lang}.php";
    if (!file_exists($langFile)) { $lang = 'es'; $langFile = __DIR__ . "/lang/es.php"; }
    $t = require $langFile;
    ?>
    <!DOCTYPE html>
    <html lang="<?= $lang ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($t['link_expired_title']) ?> — <?= e($t['site_name']) ?></title>
        <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <link rel="stylesheet" href="css/style.css">
    </head>
    <body>
        <div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem;background:#FFF;">
            <div class="nb-card" style="max-width:420px;width:100%;padding:2.5rem;text-align:center;">
                <div class="nb-empty-icon" style="margin-bottom:1rem;"><i class="fas fa-clock"></i></div>
                <h1 style="font-size:1.25rem;font-weight:700;text-transform:uppercase;margin-bottom:0.5rem;"><?= e($t['link_expired_title']) ?></h1>
                <p style="font-size:0.75rem;color:#888;font-weight:600;text-transform:uppercase;"><?= e($t['link_expired_subtitle']) ?></p>
                <a href="index.php" class="nb-btn nb-btn-filled nb-btn-sm" style="margin-top:1.5rem;"><?= e($t['site_name']) ?></a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

if (!empty($urlData['link_password_hash'])) {
    $lang = $_SESSION['lang'] ?? 'es';
    $langFile = __DIR__ . "/lang/{$lang}.php";
    if (!file_exists($langFile)) { $lang = 'es'; $langFile = __DIR__ . "/lang/es.php"; }
    $t = require $langFile;

    $accessGranted = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $submittedPassword = $_POST['link_password'] ?? '';
        if (password_verify($submittedPassword, $urlData['link_password_hash'])) {
            $accessGranted = true;
            $_SESSION['link_access_' . $urlData['id']] = true;
        } else {
            $linkError = $lang === 'es' ? 'Contrasena incorrecta.' : 'Incorrect password.';
        }
    }

    if (isset($_SESSION['link_access_' . $urlData['id']])) {
        $accessGranted = true;
    }

    if (!$accessGranted) {
        ?>
        <!DOCTYPE html>
        <html lang="<?= $lang ?>">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= e($t['link_protected_title']) ?> — <?= e($t['site_name']) ?></title>
            <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
            <link rel="stylesheet" href="css/style.css">
        </head>
        <body>
            <div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem;background:#FFF;">
                <div class="nb-card" style="max-width:420px;width:100%;padding:2.5rem;text-align:center;">
                    <div class="nb-empty-icon" style="margin-bottom:1rem;"><i class="fas fa-lock"></i></div>
                    <h1 style="font-size:1.25rem;font-weight:700;text-transform:uppercase;margin-bottom:0.5rem;"><?= e($t['link_protected_title']) ?></h1>
                    <p style="font-size:0.75rem;color:#888;font-weight:600;text-transform:uppercase;margin-bottom:1.5rem;"><?= e($t['link_protected_subtitle']) ?></p>
                    <?php if (!empty($linkError)): ?>
                    <div style="padding:0.5rem;background:#000;color:#FFF;font-size:0.7rem;font-weight:700;text-transform:uppercase;margin-bottom:1rem;"><?= e($linkError) ?></div>
                    <?php endif; ?>
                    <form method="POST" style="display:flex;flex-direction:column;gap:1rem;">
                        <input type="hidden" name="_csrf_token" value="<?= csrfToken() ?>">
                        <input type="password" name="link_password" required class="nb-input" placeholder="<?= e($t['auth_password']) ?>">
                        <button type="submit" class="nb-btn nb-btn-filled" style="width:100%;justify-content:center;"><?= e($t['link_protected_btn']) ?></button>
                    </form>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$referrer = $_SERVER['HTTP_REFERER'] ?? 'Direct';

$device = 'desktop';
if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $userAgent)) {
    $device = 'mobile';
} elseif (preg_match('/(ipad|tablet|(android(?!.*mobile))|(windows(?!.*phone)(.*touch))|kindle|playbook|silk)|blackberry|(playbook)|(macintosh|mac os x(?=touch))/i', $userAgent)) {
    $device = 'tablet';
}

$browser = 'Unknown';
if (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident/') !== false) $browser = 'Internet Explorer';
elseif (strpos($userAgent, 'Edge') !== false) $browser = 'Edge';
elseif (strpos($userAgent, 'Firefox') !== false) $browser = 'Firefox';
elseif (strpos($userAgent, 'Chrome') !== false) $browser = 'Chrome';
elseif (strpos($userAgent, 'Safari') !== false) $browser = 'Safari';

$refDomain = 'Direct';
if ($referrer !== 'Direct') {
    $parsedUrl = parse_url($referrer);
    $refDomain = $parsedUrl['host'] ?? 'Direct';
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
}

$countryCode = 'UNK';
$countryName = 'Unknown';
$city = 'Unknown';

if (isset($_SERVER['HTTP_CF_IPCOUNTRY'])) {
    $countryCode = $_SERVER['HTTP_CF_IPCOUNTRY'];
} else {
    $ctx = stream_context_create(['http' => ['timeout' => 0.02]]);
    $geoJson = @file_get_contents("http://ip-api.com/json/{$ip}", false, $ctx);
    if ($geoJson) {
        $geoData = json_decode($geoJson, true);
        if (($geoData['status'] ?? '') === 'success') {
            $countryCode = $geoData['countryCode'] ?? 'UNK';
            $countryName = $geoData['country'] ?? 'Unknown';
            $city = $geoData['city'] ?? 'Unknown';
        }
    }
}

$metricsStmt = $tekupdo->prepare("INSERT INTO link_metrics (url_id, country_code, country_name, city, device_type, browser, referrer_domain) VALUES (?, ?, ?, ?, ?, ?, ?)");
$metricsStmt->execute([$urlData['id'], $countryCode, $countryName, $city, $device, $browser, $refDomain]);

header("Location: " . $urlData['original_url'], true, 301);
exit;
