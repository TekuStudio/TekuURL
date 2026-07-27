<?php
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/config.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) {
        header("Location: forgot_password.php");
        exit;
    }
    if (!checkRateLimit(getClientIp(), 'forgot_password', $tekupdo)) {
        setFlash('error', $lang === 'es' ? 'Demasiadas peticiones. Espera un momento.' : 'Too many requests. Please wait.');
        header("Location: forgot_password.php");
        exit;
    }

    $email = trim($_POST['email'] ?? '');
    $stmt = $tekupdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $stmt = $tekupdo->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$user['id'], $token, $expires]);

        $resetUrl = BASE_URL . "/reset_password.php?token=" . $token;
        error_log("Password reset requested for user ID {$user['id']}");
    }

    recordRateLimit(getClientIp(), 'forgot_password', $tekupdo);
    setFlash('success', $t['auth_reset_sent']);
    header("Location: forgot_password.php");
    exit;
}

include __DIR__ . '/views/layout_header.php';
?>

<div style="max-width:420px;margin:3rem auto;">
    <div class="nb-card">
        <div style="text-align:center;margin-bottom:1.5rem;">
            <div class="nb-avatar" style="width:48px;height:48px;margin:0 auto 0.75rem;font-size:1.25rem;border-width:3px;"><i class="fas fa-key"></i></div>
            <h1 style="font-size:1.25rem;font-weight:700;text-transform:uppercase;margin-bottom:0.25rem;"><?= e($t['auth_reset_title']) ?></h1>
            <p style="font-size:0.7rem;color:#888;font-weight:600;text-transform:uppercase;"><?= e($t['auth_reset_subtitle']) ?></p>
        </div>
        <form method="POST" style="display:flex;flex-direction:column;gap:1rem;">
            <?= csrfField() ?>
            <div>
                <label style="display:block;font-size:0.7rem;font-weight:700;text-transform:uppercase;margin-bottom:0.5rem;"><?= e($t['auth_email']) ?></label>
                <input type="email" name="email" required class="nb-input" placeholder="you@example.com" value="<?= e($_GET['email'] ?? '') ?>">
            </div>
            <button type="submit" class="nb-btn nb-btn-filled" style="width:100%;justify-content:center;"><?= e($t['auth_reset_btn']) ?></button>
        </form>
        <div style="text-align:center;margin-top:1.5rem;">
            <a href="login.php" style="font-size:0.7rem;font-weight:700;text-transform:uppercase;color:#999;"><i class="fas fa-arrow-left" style="margin-right:0.35rem;"></i> <?= e($lang === 'es' ? 'Volver al login' : 'Back to login') ?></a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/views/layout_footer.php'; ?>
