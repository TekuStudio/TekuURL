<?php
require_once __DIR__ . '/../lang.php';
require_once __DIR__ . '/../config.php';
requireAdmin();
$admin = getCurrentAdmin($tekupdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) {
        header("Location: users.php");
        exit;
    }

    if (isset($_POST['toggle'])) {
        $uid = (int)$_POST['toggle'];
        if ($uid !== $admin['id']) {
            $stmt = $tekupdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$uid]);
            logAdminAction($admin['id'], 'toggle_user', 'user', $uid, 'Toggled active status', $tekupdo);
            setFlash('success', $lang === 'es' ? 'Estado actualizado.' : 'Status updated.');
        }
    }

    if (isset($_POST['setrole'])) {
        $uid = (int)$_POST['setrole'];
        $role = $_POST['role'] ?? 'user';
        if (!in_array($role, ['user', 'admin'])) $role = 'user';
        if ($uid !== $admin['id']) {
            $stmt = $tekupdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$role, $uid]);
            logAdminAction($admin['id'], 'set_role', 'user', $uid, "Role set to {$role}", $tekupdo);
            setFlash('success', $lang === 'es' ? 'Rol actualizado.' : 'Role updated.');
        }
    }

    if (isset($_POST['setplan'])) {
        $uid = (int)$_POST['setplan'];
        $plan = $_POST['plan'] ?? 'free';
        if (!in_array($plan, ['free', 'pro', 'enterprise'])) $plan = 'free';
        $stmt = $tekupdo->prepare("UPDATE users SET plan_status = ? WHERE id = ?");
        $stmt->execute([$plan, $uid]);
        logAdminAction($admin['id'], 'set_plan', 'user', $uid, "Plan set to {$plan}", $tekupdo);
        setFlash('success', $lang === 'es' ? 'Plan actualizado.' : 'Plan updated.');
    }

    if (isset($_POST['delete'])) {
        $uid = (int)$_POST['delete'];
        if ($uid !== $admin['id']) {
            $stmt = $tekupdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$uid]);
            logAdminAction($admin['id'], 'delete_user', 'user', $uid, null, $tekupdo);
            setFlash('success', $lang === 'es' ? 'Usuario eliminado.' : 'User deleted.');
        }
    }

    header("Location: users.php");
    exit;
}

$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = "1=1";
$params = [];
if ($search) {
    $where .= " AND (email LIKE ?)";
    $params[] = "%{$search}%";
}

$countStmt = $tekupdo->prepare("SELECT COUNT(*) FROM users WHERE {$where}");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));

$stmt = $tekupdo->prepare("SELECT u.*, (SELECT COUNT(*) FROM shortened_urls su WHERE su.user_id = u.id) as link_count, (SELECT COUNT(*) FROM link_metrics lm JOIN shortened_urls su ON lm.url_id = su.id WHERE su.user_id = u.id) as click_count FROM users u WHERE {$where} ORDER BY u.created_at DESC LIMIT {$perPage} OFFSET {$offset}");
$stmt->execute($params);
$users = $stmt->fetchAll();

include __DIR__ . '/../views/layout_header.php';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
    <div>
        <a href="index.php" style="font-size:0.7rem;font-weight:700;text-transform:uppercase;color:#999;display:inline-flex;align-items:center;gap:0.35rem;margin-bottom:0.5rem;"><i class="fas fa-arrow-left"></i> <?= e($lang === 'es' ? 'Volver' : 'Back') ?></a>
        <h1 style="font-size:1.75rem;font-weight:700;text-transform:uppercase;margin-bottom:0.25rem;"><?= e($lang === 'es' ? 'Gestionar Usuarios' : 'Manage Users') ?></h1>
        <p style="font-size:0.75rem;color:#999;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;"><?= formatNumber($total) ?> <?= e($lang === 'es' ? 'usuarios registrados' : 'registered users') ?></p>
    </div>
</div>

<div class="nb-card mb-2" style="padding:1rem;">
    <form method="GET" style="display:flex;gap:0.5rem;">
        <input type="text" name="search" value="<?= e($search) ?>" class="nb-input" placeholder="<?= e($lang === 'es' ? 'Buscar por email...' : 'Search by email...') ?>" style="box-shadow:none;border-width:2px;flex:1;">
        <button type="submit" class="nb-btn nb-btn-sm"><i class="fas fa-search"></i></button>
        <?php if ($search): ?>
        <a href="users.php" class="nb-btn nb-btn-ghost nb-btn-sm"><i class="fas fa-times"></i></a>
        <?php endif; ?>
    </form>
</div>

<?php if (empty($users)): ?>
<div class="nb-card">
    <div class="nb-empty">
        <div class="nb-empty-icon"><i class="fas fa-users"></i></div>
        <div class="nb-empty-text"><?= e($lang === 'es' ? 'Sin resultados' : 'No results') ?></div>
    </div>
</div>
<?php else: ?>
<div class="nb-card" style="padding:0;overflow:hidden;">
    <div class="nb-table-wrap" style="border:none;">
        <table class="nb-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?= e($lang === 'es' ? 'Email' : 'Email') ?></th>
                    <th style="text-align:center;"><?= e($lang === 'es' ? 'Rol' : 'Role') ?></th>
                    <th style="text-align:center;"><?= e($lang === 'es' ? 'Plan' : 'Plan') ?></th>
                    <th style="text-align:center;"><?= e($lang === 'es' ? 'Estado' : 'Status') ?></th>
                    <th style="text-align:center;"><?= e($lang === 'es' ? 'Enlaces' : 'Links') ?></th>
                    <th style="text-align:center;"><?= e($lang === 'es' ? 'Clics' : 'Clicks') ?></th>
                    <th><?= e($lang === 'es' ? 'Registro' : 'Joined') ?></th>
                    <th style="text-align:center;"><?= e($lang === 'es' ? 'Acciones' : 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td style="font-size:0.7rem;color:#999;"><?= $u['id'] ?></td>
                    <td style="font-size:0.8rem;font-weight:600;"><?= e($u['email']) ?></td>
                    <td style="text-align:center;">
                        <?php if ($u['id'] !== $admin['id']): ?>
                        <form method="POST" style="display:inline;">
                            <?= csrfField() ?>
                            <input type="hidden" name="setrole" value="<?= $u['id'] ?>">
                            <input type="hidden" name="role" value="<?= ($u['role'] ?? 'user') === 'admin' ? 'user' : 'admin' ?>">
                            <button type="submit" style="background:none;border:none;padding:0;cursor:pointer;">
                                <span class="nb-badge <?= ($u['role'] ?? 'user') === 'admin' ? 'nb-badge-filled' : 'nb-badge-muted' ?>" style="font-size:0.5rem;"><?= e(strtoupper($u['role'] ?? 'user')) ?></span>
                            </button>
                        </form>
                        <?php else: ?>
                        <span class="nb-badge nb-badge-filled" style="font-size:0.5rem;"><?= e(strtoupper($u['role'] ?? 'user')) ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;">
                        <?php if ($u['id'] !== $admin['id']): ?>
                        <div style="display:flex;gap:0.25rem;justify-content:center;">
                            <?php foreach (['free', 'pro', 'enterprise'] as $p): ?>
                            <form method="POST" style="display:inline;">
                                <?= csrfField() ?>
                                <input type="hidden" name="setplan" value="<?= $u['id'] ?>">
                                <input type="hidden" name="plan" value="<?= $p ?>">
                                <button type="submit" class="nb-tag <?= $u['plan_status'] === $p ? 'nb-tag-filled' : '' ?>" style="font-size:0.45rem;padding:2px 5px;border:none;cursor:pointer;"><?= strtoupper(substr($p, 0, 2)) ?></button>
                            </form>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <span class="nb-tag nb-tag-filled" style="font-size:0.45rem;padding:2px 5px;"><?= strtoupper(substr($u['plan_status'], 0, 2)) ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;">
                        <?php if ($u['id'] !== $admin['id']): ?>
                        <form method="POST" style="display:inline;">
                            <?= csrfField() ?>
                            <input type="hidden" name="toggle" value="<?= $u['id'] ?>">
                            <button type="submit" style="background:none;border:none;padding:0;cursor:pointer;">
                                <span class="nb-badge <?= $u['is_active'] ? 'nb-badge-success' : 'nb-badge-muted' ?>" style="font-size:0.5rem;"><?= $u['is_active'] ? 'ON' : 'OFF' ?></span>
                            </button>
                        </form>
                        <?php else: ?>
                        <span class="nb-badge nb-badge-success" style="font-size:0.5rem;">ON</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;font-weight:700;font-size:0.8rem;"><?= formatNumber($u['link_count']) ?></td>
                    <td style="text-align:center;font-weight:700;font-size:0.8rem;"><?= formatNumber($u['click_count']) ?></td>
                    <td style="font-size:0.7rem;color:#999;"><?= timeAgo($u['created_at']) ?></td>
                    <td style="text-align:center;">
                        <?php if ($u['id'] !== $admin['id']): ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('<?= e($lang === 'es' ? 'Eliminar usuario?' : 'Delete user?') ?>')">
                            <?= csrfField() ?>
                            <input type="hidden" name="delete" value="<?= $u['id'] ?>">
                            <button type="submit" class="nb-btn nb-btn-xs nb-btn-danger" style="font-size:0.55rem;"><i class="fas fa-trash"></i></button>
                        </form>
                        <?php else: ?>
                        <span class="nb-badge nb-badge-muted" style="font-size:0.45rem;"><?= e($lang === 'es' ? 'TU' : 'YOU') ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div style="border-top:3px solid #000;padding:1rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;">
        <span style="font-size:0.7rem;font-weight:700;text-transform:uppercase;color:#999;"><?= e($lang === 'es' ? 'Pagina' : 'Page') ?> <?= $page ?> / <?= $totalPages ?></span>
        <div style="display:flex;gap:0.35rem;">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" class="nb-btn nb-btn-xs"><i class="fas fa-chevron-left"></i></a>
            <?php endif; ?>
            <?php
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            for ($i = $start; $i <= $end; $i++): ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="nb-btn nb-btn-xs <?= $i === $page ? 'nb-btn-filled' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" class="nb-btn nb-btn-xs"><i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../views/layout_footer.php'; ?>
