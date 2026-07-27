<?php
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/config.php';
requireLogin();
$user = getCurrentUser($tekupdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) {
        header("Location: settings.php");
        exit;
    }
    if (isset($_POST['new_password'])) {
        if (!checkRateLimit(getClientIp(), 'password_change', $tekupdo)) {
            setFlash('error', $lang === 'es' ? 'Demasiados intentos. Espera un momento.' : 'Too many attempts. Please wait.');
        } else {
            $current = $_POST['current_password'] ?? '';
            $new = $_POST['new_password'] ?? '';
            if (!password_verify($current, $user['password_hash'])) {
                setFlash('error', $lang === 'es' ? 'La contrasena actual es incorrecta.' : 'Current password is incorrect.');
            } elseif (strlen($new) < 6) {
                setFlash('error', $lang === 'es' ? 'La nueva contrasena debe tener al menos 6 caracteres.' : 'New password must be at least 6 characters.');
            } elseif (strlen($new) > 128) {
                setFlash('error', $lang === 'es' ? 'La contrasena no puede tener mas de 128 caracteres.' : 'Password cannot exceed 128 characters.');
            } else {
                $hash = password_hash($new, PASSWORD_BCRYPT);
                $stmt = $tekupdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->execute([$hash, $user['id']]);
                resetRateLimit(getClientIp(), 'password_change', $tekupdo);
                setFlash('success', $lang === 'es' ? 'Contrasena actualizada.' : 'Password updated.');
            }
        }
        header("Location: settings.php");
        exit;
    }
    if (isset($_POST['delete_account'])) {
        $stmt = $tekupdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        session_destroy();
        header("Location: index.php");
        exit;
    }
}

$totalLinksStmt = $tekupdo->prepare("SELECT COUNT(*) FROM shortened_urls WHERE user_id = ?");
$totalLinksStmt->execute([$user['id']]);
$totalLinks = $totalLinksStmt->fetchColumn();

$totalClicksStmt = $tekupdo->prepare("SELECT COUNT(*) FROM link_metrics lm JOIN shortened_urls su ON lm.url_id = su.id WHERE su.user_id = ?");
$totalClicksStmt->execute([$user['id']]);
$totalClicks = $totalClicksStmt->fetchColumn();

include __DIR__ . '/views/layout_header.php';
?>

<div style="margin-bottom:2rem;">
    <h1 style="font-size:1.75rem;font-weight:700;text-transform:uppercase;margin-bottom:0.25rem;"><?= e($t['settings_title']) ?></h1>
    <p style="font-size:0.75rem;color:#999;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;"><?= e($lang === 'es' ? 'Gestiona tu cuenta' : 'Manage your account') ?></p>
</div>

<div class="nb-grid-2" style="margin-bottom:2rem;">
    <div class="nb-card">
        <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;">
            <div class="nb-avatar" style="width:50px;height:50px;font-size:1.1rem;border-width:3px;"><?= e(substr($user['email'], 0, 1)) ?></div>
            <div>
                <div style="font-size:0.85rem;font-weight:700;text-transform:uppercase;"><?= e($user['email']) ?></div>
                <div style="display:flex;gap:0.5rem;margin-top:0.35rem;">
                    <span class="nb-badge nb-badge-filled"><?= e(strtoupper($user['plan_status'])) ?></span>
                    <span class="nb-badge nb-badge-muted"><?= e(strtoupper($user['role'] ?? 'user')) ?></span>
                </div>
            </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:0.75rem;">
            <div style="display:flex;justify-content:space-between;padding:0.5rem 0;border-bottom:2px solid #F3F4F6;">
                <span style="font-size:0.75rem;font-weight:600;color:#888;text-transform:uppercase;"><?= e($t['settings_email']) ?></span>
                <span style="font-size:0.75rem;font-weight:700;"><?= e($user['email']) ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:0.5rem 0;border-bottom:2px solid #F3F4F6;">
                <span style="font-size:0.75rem;font-weight:600;color:#888;text-transform:uppercase;"><?= e($lang === 'es' ? 'Rol' : 'Role') ?></span>
                <span class="nb-badge nb-badge-muted" style="font-size:0.55rem;"><?= e(strtoupper($user['role'] ?? 'user')) ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:0.5rem 0;border-bottom:2px solid #F3F4F6;">
                <span style="font-size:0.75rem;font-weight:600;color:#888;text-transform:uppercase;"><?= e($lang === 'es' ? 'Plan' : 'Plan') ?></span>
                <span class="nb-badge nb-badge-filled" style="font-size:0.55rem;"><?= e(strtoupper($user['plan_status'])) ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:0.5rem 0;border-bottom:2px solid #F3F4F6;">
                <span style="font-size:0.75rem;font-weight:600;color:#888;text-transform:uppercase;"><?= e($lang === 'es' ? 'Enlaces' : 'Links') ?></span>
                <span style="font-size:0.75rem;font-weight:700;"><?= formatNumber($totalLinks) ?> / <?= formatNumber(getLinkLimit($user['plan_status'])) ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:0.5rem 0;border-bottom:2px solid #F3F4F6;">
                <span style="font-size:0.75rem;font-weight:600;color:#888;text-transform:uppercase;"><?= e($t['dash_total_clicks']) ?></span>
                <span style="font-size:0.75rem;font-weight:700;"><?= formatNumber($totalClicks) ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:0.5rem 0;">
                <span style="font-size:0.75rem;font-weight:600;color:#888;text-transform:uppercase;"><?= e($lang === 'es' ? 'Miembro desde' : 'Member since') ?></span>
                <span style="font-size:0.75rem;font-weight:700;"><?= date('d/m/Y', strtotime($user['created_at'])) ?></span>
            </div>
        </div>
        <?php
        $limit = getLinkLimit($user['plan_status']);
        $usage = $limit > 0 ? min(100, round(($totalLinks / $limit) * 100)) : 0;
        ?>
        <div style="margin-top:1.25rem;">
            <div style="display:flex;justify-content:space-between;margin-bottom:0.35rem;">
                <span style="font-size:0.6rem;font-weight:700;text-transform:uppercase;color:#999;"><?= e($lang === 'es' ? 'Uso del Plan' : 'Plan Usage') ?></span>
                <span style="font-size:0.6rem;font-weight:700;"><?= $usage ?>%</span>
            </div>
            <div class="nb-progress"><div class="nb-progress-fill" style="width:<?= $usage ?>%"></div></div>
        </div>
    </div>

    <div class="nb-card">
        <h3 style="font-size:0.8rem;font-weight:700;text-transform:uppercase;margin-bottom:1.25rem;"><i class="fas fa-lock" style="margin-right:0.5rem;font-size:0.7rem;"></i> <?= e($t['settings_change_password']) ?></h3>
        <form method="POST" style="display:flex;flex-direction:column;gap:1.25rem;">
            <?= csrfField() ?>
            <div>
                <label style="display:block;font-size:0.7rem;font-weight:700;text-transform:uppercase;margin-bottom:0.5rem;letter-spacing:0.05em;"><?= e($t['settings_current_password']) ?></label>
                <input type="password" name="current_password" required class="nb-input" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
            </div>
            <div>
                <label style="display:block;font-size:0.7rem;font-weight:700;text-transform:uppercase;margin-bottom:0.5rem;letter-spacing:0.05em;"><?= e($t['settings_new_password']) ?></label>
                <input type="password" name="new_password" required minlength="6" class="nb-input" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
            </div>
            <button type="submit" class="nb-btn nb-btn-filled" style="width:100%;justify-content:center;"><i class="fas fa-save" style="margin-right:0.5rem;"></i> <?= e($t['settings_save']) ?></button>
        </form>

        <hr class="nb-divider">

        <h3 style="font-size:0.8rem;font-weight:700;text-transform:uppercase;margin-bottom:1rem;color:#000;"><i class="fas fa-exclamation-triangle" style="margin-right:0.5rem;font-size:0.7rem;"></i> <?= e($lang === 'es' ? 'Zona de Peligro' : 'Danger Zone') ?></h3>
        <form method="POST" onsubmit="return confirm('<?= e($t['settings_delete_confirm']) ?>')">
            <?= csrfField() ?>
            <input type="hidden" name="delete_account" value="1">
            <button type="submit" class="nb-btn nb-btn-danger" style="width:100%;justify-content:center;"><i class="fas fa-trash" style="margin-right:0.5rem;"></i> <?= e($t['settings_delete_account']) ?></button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/views/layout_footer.php'; ?>
