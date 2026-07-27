<?php
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/config.php';

if (isset($_SESSION['admin_id'])) {
    header("Location: admin/index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) {
        header("Location: admin_login.php");
        exit;
    }
    if (!checkRateLimit(getClientIp(), 'admin_login', $tekupdo)) {
        $error = $lang === 'es' ? 'Demasiados intentos. Espera 15 minutos.' : 'Too many attempts. Wait 15 minutes.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $tekupdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if (!$user['is_active']) {
                $error = $lang === 'es' ? 'Tu cuenta esta desactivada.' : 'Your account is disabled.';
            } elseif ($user['role'] !== 'admin') {
                $error = $lang === 'es' ? 'Acceso no autorizado.' : 'Unauthorized access.';
                recordRateLimit(getClientIp(), 'admin_login', $tekupdo);
            } else {
                resetRateLimit(getClientIp(), 'admin_login', $tekupdo);
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $user['id'];
                header("Location: admin/index.php");
                exit;
            }
        } else {
            recordRateLimit(getClientIp(), 'admin_login', $tekupdo);
            $error = $lang === 'es' ? 'Email o contrasena incorrectos.' : 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($t['admin_panel']) ?> — <?= e($t['site_name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem;background:#FFF;">
        <div class="nb-card" style="max-width:420px;width:100%;padding:2.5rem;">
            <div style="text-align:center;margin-bottom:2rem;">
                <a href="index.php" style="font-weight:700;font-size:1.2rem;text-transform:uppercase;color:#000;text-decoration:none;display:block;margin-bottom:1.5rem;"><?= e($t['site_name']) ?></a>
                <div style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.5rem 1rem;border:3px solid #000;background:#000;color:#FFF;font-size:0.7rem;font-weight:700;text-transform:uppercase;margin-bottom:1rem;"><i class="fas fa-shield-alt"></i> <?= e($t['admin_panel']) ?></div>
                <h1 style="font-size:1.4rem;font-weight:700;text-transform:uppercase;margin-bottom:0.25rem;"><?= e($lang === 'es' ? 'Acceso Admin' : 'Admin Access') ?></h1>
                <p style="font-size:0.7rem;font-weight:600;text-transform:uppercase;color:#999;letter-spacing:0.05em;"><?= e($lang === 'es' ? 'Solo administradores autorizados' : 'Authorized admins only') ?></p>
            </div>
            <?php if ($error): ?>
            <div style="padding:0.75rem;background:#000;color:#FFF;border:3px solid #000;margin-bottom:1rem;font-weight:700;font-size:0.75rem;text-transform:uppercase;box-shadow:4px 4px 0 #000;display:flex;align-items:center;gap:0.5rem;"><i class="fas fa-exclamation-circle"></i> <?= e($error) ?></div>
            <?php endif; ?>
            <form method="POST" style="display:flex;flex-direction:column;gap:1.25rem;">
                <?= csrfField() ?>
                <div>
                    <label style="display:block;font-size:0.7rem;font-weight:700;text-transform:uppercase;margin-bottom:0.5rem;letter-spacing:0.05em;"><?= e($t['auth_email']) ?></label>
                    <input type="email" name="email" required class="nb-input" placeholder="admin@tekuurl.com" autocomplete="email">
                </div>
                <div>
                    <label style="display:block;font-size:0.7rem;font-weight:700;text-transform:uppercase;margin-bottom:0.5rem;letter-spacing:0.05em;"><?= e($t['auth_password']) ?></label>
                    <input type="password" name="password" required class="nb-input" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" autocomplete="current-password">
                </div>
                <button type="submit" class="nb-btn nb-btn-filled" style="width:100%;justify-content:center;padding:1rem;"><i class="fas fa-shield-alt" style="margin-right:0.5rem;"></i> <?= e($lang === 'es' ? 'Entrar' : 'Sign In') ?></button>
            </form>
            <div style="text-align:center;margin-top:1.5rem;">
                <a href="login.php" style="font-size:0.7rem;font-weight:700;text-transform:uppercase;color:#999;"><i class="fas fa-arrow-left" style="margin-right:0.35rem;"></i> <?= e($lang === 'es' ? 'Volver al login de usuarios' : 'Back to user login') ?></a>
            </div>
            <div style="display:flex;align-items:center;justify-content:center;gap:0.5rem;margin-top:1.5rem;">
                <a href="?lang=en" class="nb-tag <?= $lang === 'en' ? 'nb-tag-filled' : '' ?>" style="font-size:0.55rem;">EN</a>
                <a href="?lang=es" class="nb-tag <?= $lang === 'es' ? 'nb-tag-filled' : '' ?>" style="font-size:0.55rem;">ES</a>
            </div>
        </div>
    </div>
    <script src="js/app.js"></script>
</body>
</html>
