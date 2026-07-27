<?php
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/config.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$token = $_GET['token'] ?? '';
if (!$token) {
    setFlash('error', $t['auth_reset_invalid']);
    header("Location: login.php");
    exit;
}

$stmt = $tekupdo->prepare("SELECT prt.*, u.email FROM password_reset_tokens prt JOIN users u ON prt.user_id = u.id WHERE prt.token = ? AND prt.used = 0 AND prt.expires_at > NOW()");
$stmt->execute([$token]);
$resetData = $stmt->fetch();

if (!$resetData) {
    setFlash('error', $t['auth_reset_invalid']);
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) {
        header("Location: reset_password.php?token=" . urlencode($token));
        exit;
    }
    if (!checkRateLimit($resetData['email'], 'reset_password', $tekupdo)) {
        setFlash('error', $lang === 'es' ? 'Demasiados intentos. Espera un momento.' : 'Too many attempts. Please wait.');
        header("Location: reset_password.php?token=" . urlencode($token));
        exit;
    }

    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {
        setFlash('error', $lang === 'es' ? 'Contrasena muy corta (minimo 6 caracteres).' : 'Password too short (minimum 6 characters).');
    } elseif (strlen($password) > 128) {
        setFlash('error', $lang === 'es' ? 'Contrasena muy larga (maximo 128 caracteres).' : 'Password too long (maximum 128 characters).');
    } elseif ($password !== $confirm) {
        setFlash('error', $lang === 'es' ? 'Las contrasenas no coinciden.' : 'Passwords do not match.');
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $tekupdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$hash, $resetData['user_id']]);

        $stmt = $tekupdo->prepare("UPDATE password_reset_tokens SET used = 1 WHERE token = ?");
        $stmt->execute([$token]);

        resetRateLimit($resetData['email'], 'login', $tekupdo);
        setFlash('success', $t['auth_reset_success']);
        header("Location: login.php");
        exit;
    }
    header("Location: reset_password.php?token=" . urlencode($token));
    exit;
}

include __DIR__ . '/views/layout_header.php';
?>

<div style="max-width:420px;margin:3rem auto;">
    <div class="nb-card">
        <div style="text-align:center;margin-bottom:1.5rem;">
            <div class="nb-avatar" style="width:48px;height:48px;margin:0 auto 0.75rem;font-size:1.25rem;border-width:3px;"><i class="fas fa-lock"></i></div>
            <h1 style="font-size:1.25rem;font-weight:700;text-transform:uppercase;margin-bottom:0.25rem;"><?= e($t['auth_reset_title']) ?></h1>
            <p style="font-size:0.7rem;color:#888;font-weight:600;text-transform:uppercase;"><?= e($lang === 'es' ? 'Restablece tu contrasena para' : 'Reset password for') ?> <?= e($resetData['email']) ?></p>
        </div>
        <form method="POST" style="display:flex;flex-direction:column;gap:1rem;">
            <?= csrfField() ?>
            <div>
                <label style="display:block;font-size:0.7rem;font-weight:700;text-transform:uppercase;margin-bottom:0.5rem;"><?= e($t['auth_reset_new_password']) ?></label>
                <input type="password" name="password" required minlength="6" class="nb-input" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
            </div>
            <div>
                <label style="display:block;font-size:0.7rem;font-weight:700;text-transform:uppercase;margin-bottom:0.5rem;"><?= e($t['auth_reset_confirm']) ?></label>
                <input type="password" name="confirm_password" required minlength="6" class="nb-input" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
            </div>
            <button type="submit" class="nb-btn nb-btn-filled" style="width:100%;justify-content:center;"><?= e($t['auth_reset_submit']) ?></button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/views/layout_footer.php'; ?>
