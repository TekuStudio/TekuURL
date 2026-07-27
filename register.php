<?php
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/config.php';

if (isset($_SESSION['user_id'])) { header("Location: dashboard.php"); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) {
        header("Location: register.php");
        exit;
    }
    if (!checkRateLimit(getClientIp(), 'register', $tekupdo)) {
        $error = $lang === 'es' ? 'Demasiados registros. Espera un momento.' : 'Too many registrations. Please wait.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = $lang === 'es' ? 'Email invalido.' : 'Invalid email.';
        } elseif ($password !== $password2) {
            $error = $lang === 'es' ? 'Las contrasenas no coinciden.' : 'Passwords do not match.';
        } elseif (strlen($password) < 6) {
            $error = $lang === 'es' ? 'La contrasena debe tener al menos 6 caracteres.' : 'Password must be at least 6 characters.';
        } elseif (strlen($password) > 128) {
            $error = $lang === 'es' ? 'La contrasena no puede tener mas de 128 caracteres.' : 'Password cannot exceed 128 characters.';
        } else {
            $stmt = $tekupdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = $lang === 'es' ? 'El email ya esta registrado.' : 'Email already registered.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $tekupdo->prepare("INSERT INTO users (email, password_hash, plan_status) VALUES (?, ?, 'free')");
                $stmt->execute([$email, $hash]);
                $userId = $tekupdo->lastInsertId();
                session_regenerate_id(true);
                $_SESSION['user_id'] = $userId;
                $_SESSION['plan_status'] = 'free';
                resetRateLimit(getClientIp(), 'register', $tekupdo);
                header("Location: dashboard.php");
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($t['nav_register']) ?> — <?= e($t['site_name']) ?></title>
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
                <h1 style="font-size:1.6rem;font-weight:700;text-transform:uppercase;margin-bottom:0.25rem;"><?= e($t['auth_register_title']) ?></h1>
                <p style="font-size:0.7rem;font-weight:600;text-transform:uppercase;color:#999;letter-spacing:0.05em;"><?= e($t['auth_register_subtitle']) ?></p>
            </div>
            <?php if ($error): ?>
            <div style="padding:0.75rem;background:#000;color:#FFF;border:3px solid #000;margin-bottom:1rem;font-weight:700;font-size:0.75rem;text-transform:uppercase;box-shadow:4px 4px 0 #000;display:flex;align-items:center;gap:0.5rem;"><i class="fas fa-exclamation-circle"></i> <?= e($error) ?></div>
            <?php endif; ?>
            <form method="POST" style="display:flex;flex-direction:column;gap:1rem;">
                <?= csrfField() ?>
                <div>
                    <label style="display:block;font-size:0.7rem;font-weight:700;text-transform:uppercase;margin-bottom:0.5rem;"><?= e($t['auth_email']) ?></label>
                    <input type="email" name="email" required class="nb-input" placeholder="you@example.com">
                </div>
                <div>
                    <label style="display:block;font-size:0.7rem;font-weight:700;text-transform:uppercase;margin-bottom:0.5rem;"><?= e($t['auth_password']) ?></label>
                    <input type="password" name="password" required minlength="6" class="nb-input" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                </div>
                <div>
                    <label style="display:block;font-size:0.7rem;font-weight:700;text-transform:uppercase;margin-bottom:0.5rem;"><?= e($t['auth_confirm_password']) ?></label>
                    <input type="password" name="password2" required minlength="6" class="nb-input" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                </div>
                <button type="submit" class="nb-btn nb-btn-filled" style="width:100%;justify-content:center;padding:1rem;"><i class="fas fa-user-plus" style="margin-right:0.5rem;"></i> <?= e($t['auth_register_btn']) ?></button>
                <p style="text-align:center;font-size:0.75rem;font-weight:600;text-transform:uppercase;color:#666;">
                    <?= e($t['auth_has_account']) ?> <a href="login.php" style="color:#000;text-decoration:underline;font-weight:700;"><?= e($t['auth_sign_in']) ?></a>
                </p>
            </form>
            <div style="display:flex;align-items:center;justify-content:center;gap:0.5rem;margin-top:1.5rem;">
                <a href="?lang=en" class="nb-tag <?= $lang === 'en' ? 'nb-tag-filled' : '' ?>" style="font-size:0.55rem;">EN</a>
                <a href="?lang=es" class="nb-tag <?= $lang === 'es' ? 'nb-tag-filled' : '' ?>" style="font-size:0.55rem;">ES</a>
            </div>
        </div>
    </div>
    <script src="js/app.js"></script>
</body>
</html>
