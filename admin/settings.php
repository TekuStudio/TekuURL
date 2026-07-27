<?php
require_once __DIR__ . '/../lang.php';
require_once __DIR__ . '/../config.php';
requireAdmin();
$admin = getCurrentAdmin($tekupdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) {
        header("Location: settings.php");
        exit;
    }

    if (isset($_POST['create_user'])) {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';
        $plan = $_POST['plan_status'] ?? 'free';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlash('error', $lang === 'es' ? 'Email invalido.' : 'Invalid email.');
        } elseif (strlen($password) < 6) {
            setFlash('error', $lang === 'es' ? 'Contrasena muy corta.' : 'Password too short.');
        } else {
            $stmt = $tekupdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                setFlash('error', $lang === 'es' ? 'El email ya existe.' : 'Email already exists.');
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $tekupdo->prepare("INSERT INTO users (email, password_hash, role, plan_status) VALUES (?, ?, ?, ?)");
                $stmt->execute([$email, $hash, $role, $plan]);
                $newUserId = $tekupdo->lastInsertId();
                logAdminAction($admin['id'], 'create_user', 'user', $newUserId, "Created user {$email} ({$role}, {$plan})", $tekupdo);
                setFlash('success', $lang === 'es' ? 'Usuario creado.' : 'User created.');
            }
        }
        header("Location: settings.php");
        exit;
    }
}

$totalUsers = $tekupdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalLinks = $tekupdo->query("SELECT COUNT(*) FROM shortened_urls")->fetchColumn();
$totalClicks = $tekupdo->query("SELECT COUNT(*) FROM link_metrics")->fetchColumn();

$planStats = $tekupdo->query("SELECT plan_status, COUNT(*) as count FROM users GROUP BY plan_status")->fetchAll();

$adminEmails = defined('ADMIN_EMAILS') ? ADMIN_EMAILS : ['admin@tekuurl.com'];

$recentAudit = $tekupdo->query("SELECT aal.*, u.email as admin_email FROM admin_audit_log aal JOIN users u ON aal.admin_id = u.id ORDER BY aal.created_at DESC LIMIT 10")->fetchAll();

include __DIR__ . '/../views/layout_header.php';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2rem;flex-wrap:wrap;gap:1rem;">
    <div>
        <a href="index.php" style="font-size:0.7rem;font-weight:700;text-transform:uppercase;color:#999;display:inline-flex;align-items:center;gap:0.35rem;margin-bottom:0.5rem;"><i class="fas fa-arrow-left"></i> <?= e($lang === 'es' ? 'Volver' : 'Back') ?></a>
        <h1 style="font-size:1.75rem;font-weight:700;text-transform:uppercase;margin-bottom:0.25rem;"><i class="fas fa-cog" style="margin-right:0.5rem;font-size:1.25rem;"></i> <?= e($lang === 'es' ? 'Configuracion Admin' : 'Admin Settings') ?></h1>
    </div>
</div>

<div class="nb-grid-3" style="margin-bottom:2rem;">
    <div class="nb-stat">
        <div class="nb-stat-value"><?= formatNumber($totalUsers) ?></div>
        <div class="nb-stat-label"><?= e($lang === 'es' ? 'Usuarios' : 'Users') ?></div>
    </div>
    <div class="nb-stat">
        <div class="nb-stat-value"><?= formatNumber($totalLinks) ?></div>
        <div class="nb-stat-label"><?= e($lang === 'es' ? 'Enlaces' : 'Links') ?></div>
    </div>
    <div class="nb-stat">
        <div class="nb-stat-value"><?= formatNumber($totalClicks) ?></div>
        <div class="nb-stat-label"><?= e($lang === 'es' ? 'Clics Totales' : 'Total Clicks') ?></div>
    </div>
</div>

<div class="nb-grid-2" style="margin-bottom:2rem;">
    <div class="nb-card">
        <h3 style="font-size:0.8rem;font-weight:700;text-transform:uppercase;margin-bottom:1rem;"><i class="fas fa-user-plus" style="margin-right:0.5rem;font-size:0.7rem;"></i> <?= e($lang === 'es' ? 'Crear Usuario' : 'Create User') ?></h3>
        <form method="POST" style="display:flex;flex-direction:column;gap:1rem;">
            <?= csrfField() ?>
            <input type="hidden" name="create_user" value="1">
            <div>
                <label style="display:block;font-size:0.7rem;font-weight:700;text-transform:uppercase;margin-bottom:0.5rem;"><?= e($lang === 'es' ? 'Email' : 'Email') ?></label>
                <input type="email" name="email" required class="nb-input" placeholder="user@example.com">
            </div>
            <div>
                <label style="display:block;font-size:0.7rem;font-weight:700;text-transform:uppercase;margin-bottom:0.5rem;"><?= e($t['auth_password']) ?></label>
                <input type="password" name="password" required minlength="6" class="nb-input" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div>
                    <label style="display:block;font-size:0.7rem;font-weight:700;text-transform:uppercase;margin-bottom:0.5rem;"><?= e($lang === 'es' ? 'Rol' : 'Role') ?></label>
                    <select name="role" class="nb-select">
                        <option value="user"><?= e($lang === 'es' ? 'Usuario' : 'User') ?></option>
                        <option value="admin"><?= e($lang === 'es' ? 'Administrador' : 'Admin') ?></option>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:0.7rem;font-weight:700;text-transform:uppercase;margin-bottom:0.5rem;"><?= e($lang === 'es' ? 'Plan' : 'Plan') ?></label>
                    <select name="plan_status" class="nb-select">
                        <option value="free">Free</option>
                        <option value="pro">Pro</option>
                        <option value="enterprise">Enterprise</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="nb-btn nb-btn-filled" style="width:100%;justify-content:center;"><i class="fas fa-plus" style="margin-right:0.5rem;"></i> <?= e($lang === 'es' ? 'Crear Usuario' : 'Create User') ?></button>
        </form>
    </div>

    <div class="nb-card">
        <h3 style="font-size:0.8rem;font-weight:700;text-transform:uppercase;margin-bottom:1rem;"><i class="fas fa-chart-pie" style="margin-right:0.5rem;font-size:0.7rem;"></i> <?= e($lang === 'es' ? 'Distribucion de Planes' : 'Plan Distribution') ?></h3>
        <div style="display:flex;flex-direction:column;gap:0.75rem;">
            <?php foreach ($planStats as $ps): ?>
            <div>
                <div style="display:flex;justify-content:space-between;margin-bottom:0.35rem;">
                    <span style="font-size:0.75rem;font-weight:700;text-transform:uppercase;"><?= e(strtoupper($ps['plan_status'])) ?></span>
                    <span style="font-size:0.75rem;font-weight:700;"><?= $totalUsers > 0 ? round(($ps['count'] / $totalUsers) * 100) : 0 ?>% (<?= $ps['count'] ?>)</span>
                </div>
                <div class="nb-progress"><div class="nb-progress-fill" style="width:<?= $totalUsers > 0 ? round(($ps['count'] / $totalUsers) * 100) : 0 ?>%"></div></div>
            </div>
            <?php endforeach; ?>
        </div>

        <hr class="nb-divider">

        <h3 style="font-size:0.8rem;font-weight:700;text-transform:uppercase;margin-bottom:1rem;"><i class="fas fa-info-circle" style="margin-right:0.5rem;font-size:0.7rem;"></i> <?= e($lang === 'es' ? 'Sistema' : 'System') ?></h3>
        <div style="display:flex;flex-direction:column;gap:0.5rem;">
            <div style="display:flex;justify-content:space-between;padding:0.4rem 0;border-bottom:2px solid #F3F4F6;">
                <span style="font-size:0.7rem;color:#888;text-transform:uppercase;font-weight:600;">PHP</span>
                <span style="font-size:0.7rem;font-weight:700;"><?= phpversion() ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:0.4rem 0;border-bottom:2px solid #F3F4F6;">
                <span style="font-size:0.7rem;color:#888;text-transform:uppercase;font-weight:600;">MySQL</span>
                <span style="font-size:0.7rem;font-weight:700;"><?= $tekupdo->query('SELECT VERSION()')->fetchColumn() ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:0.4rem 0;border-bottom:2px solid #F3F4F6;">
                <span style="font-size:0.7rem;color:#888;text-transform:uppercase;font-weight:600;">Admin Email</span>
                <span style="font-size:0.7rem;font-weight:700;"><?= e(implode(', ', $adminEmails)) ?></span>
            </div>
        </div>
    </div>
</div>

<div class="nb-card">
    <h3 style="font-size:0.8rem;font-weight:700;text-transform:uppercase;margin-bottom:1rem;"><i class="fas fa-history" style="margin-right:0.5rem;font-size:0.7rem;"></i> <?= e($t['admin_audit_log']) ?></h3>
    <?php if (empty($recentAudit)): ?>
    <div class="nb-empty" style="padding:1.5rem;"><div class="nb-empty-sub"><?= e($lang === 'es' ? 'Sin registros aun' : 'No audit records yet') ?></div></div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;max-height:300px;overflow-y:auto;">
        <?php foreach ($recentAudit as $log): ?>
        <div style="display:flex;align-items:flex-start;gap:0.75rem;padding:0.5rem 0;border-bottom:1px solid #F3F4F6;">
            <div style="width:6px;height:6px;background:#000;border-radius:50%;margin-top:5px;flex-shrink:0;"></div>
            <div style="min-width:0;flex:1;">
                <div style="font-size:0.7rem;font-weight:700;"><?= e($log['admin_email']) ?> <span style="color:#999;font-weight:400;"><?= e($log['action']) ?></span></div>
                <?php if ($log['target_type']): ?>
                <div style="font-size:0.6rem;color:#BBB;text-transform:uppercase;"><?= e($log['target_type']) ?> #<?= $log['target_id'] ?> <?= $log['details'] ? '&middot; ' . e($log['details']) : '' ?></div>
                <?php endif; ?>
                <div style="font-size:0.55rem;color:#DDD;text-transform:uppercase;"><?= e($log['ip_address']) ?> &middot; <?= timeAgo($log['created_at']) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../views/layout_footer.php'; ?>
