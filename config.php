<?php
// TekuURL — porque las URLs largas son para los debiles
// (c) TuStudio — si robas esto, te hackeo el alma
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tekuurl');
define('BASE_URL', 'http://localhost/TekuURL');

define('STRIPE_SECRET_KEY', 'sk_test_tu_llave_secreta_aqui');
define('STRIPE_WEBHOOK_SECRET', 'whsec_tu_secreto_webhook_aqui');
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_tu_llave_publica_aqui');

define('LIMIT_FREE_LINKS', 100);
define('LIMIT_PRO_LINKS', 5000);
define('LIMIT_ENTERPRISE_LINKS', 999999);

define('ADMIN_EMAILS', ['admin@tekuurl.com']);

define('RATE_LIMIT_MAX_ATTEMPTS', 5);
define('RATE_LIMIT_WINDOW', 900);

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => true
    ];
    $tekupdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log("Connection error: " . $e->getMessage());
    die("Internal infrastructure error.");
}

function getLinkLimit($planStatus) {
    switch ($planStatus) {
        case 'enterprise': return LIMIT_ENTERPRISE_LINKS;
        case 'pro': return LIMIT_PRO_LINKS;
        default: return LIMIT_FREE_LINKS;
    }
}

function checkUserQuota($userId, $planStatus, $tekupdo) {
    $limit = getLinkLimit($planStatus);
    $stmt = $tekupdo->prepare("SELECT COUNT(*) FROM shortened_urls WHERE user_id = ?");
    $stmt->execute([$userId]);
    $total = $stmt->fetchColumn();
    return ($total < $limit);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
}

function getCurrentUser($tekupdo) {
    if (!isLoggedIn()) return null;
    $stmt = $tekupdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function isAdmin($user) {
    return isset($user['role']) && $user['role'] === 'admin';
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header("Location: " . BASE_URL . "/admin_login.php");
        exit;
    }
    global $tekupdo;
    $stmt = $tekupdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();
    if (!$admin || !isAdmin($admin) || !$admin['is_active']) {
        unset($_SESSION['admin_id']);
        header("Location: " . BASE_URL . "/admin_login.php");
        exit;
    }
}

function getCurrentAdmin($tekupdo) {
    if (!isAdminLoggedIn()) return null;
    $stmt = $tekupdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    return $stmt->fetch();
}

function generateShortCode($length = 6) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

function formatNumber($n) {
    if ($n >= 1000000) return round($n / 1000000, 1) . 'M';
    if ($n >= 1000) return round($n / 1000, 1) . 'K';
    return number_format($n);
}

function timeAgo($datetime) {
    $now = time();
    $ts = strtotime($datetime);
    $diff = $now - $ts;
    if ($diff < 60) return $diff . 's';
    if ($diff < 3600) return floor($diff / 60) . 'm';
    if ($diff < 86400) return floor($diff / 3600) . 'h';
    if ($diff < 604800) return floor($diff / 86400) . 'd';
    return date('d/m/Y', $ts);
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField() {
    return '<input type="hidden" name="_csrf_token" value="' . csrfToken() . '">';
}

function csrfVerify() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return false;
    $token = $_POST['_csrf_token'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        setFlash('error', 'Invalid security token. Please try again.');
        return false;
    }
    return true;
}

function checkRateLimit($identifier, $action, $tekupdo) {
    $stmt = $tekupdo->prepare("SELECT attempts, first_attempt_at FROM rate_limits WHERE identifier = ? AND action = ?");
    $stmt->execute([$identifier, $action]);
    $row = $stmt->fetch();
    if (!$row) return true;
    $elapsed = time() - strtotime($row['first_attempt_at']);
    if ($elapsed > RATE_LIMIT_WINDOW) {
        $stmt = $tekupdo->prepare("DELETE FROM rate_limits WHERE identifier = ? AND action = ?");
        $stmt->execute([$identifier, $action]);
        return true;
    }
    return $row['attempts'] < RATE_LIMIT_MAX_ATTEMPTS;
}

function recordRateLimit($identifier, $action, $tekupdo) {
    $stmt = $tekupdo->prepare("INSERT INTO rate_limits (identifier, action, attempts, first_attempt_at, last_attempt_at) VALUES (?, ?, 1, NOW(), NOW()) ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt_at = NOW()");
    $stmt->execute([$identifier, $action]);
}

function resetRateLimit($identifier, $action, $tekupdo) {
    $stmt = $tekupdo->prepare("DELETE FROM rate_limits WHERE identifier = ? AND action = ?");
    $stmt->execute([$identifier, $action]);
}

function logAdminAction($adminId, $action, $targetType, $targetId, $details, $tekupdo) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = $tekupdo->prepare("INSERT INTO admin_audit_log (admin_id, action, target_type, target_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$adminId, $action, $targetType, $targetId, $details, $ip]);
}

function getClientIp() {
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

function isLinkExpired($expiresAt) {
    if (!$expiresAt) return false;
    return strtotime($expiresAt) < time();
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function tekuDev($str) {
    return '👁️‍🗨️ ' . $str;
}
