<?php
require_once __DIR__ . '/../lang.php';
require_once __DIR__ . '/../config.php';
requireAdmin();
$admin = getCurrentAdmin($tekupdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) {
        header("Location: links.php");
        exit;
    }

    if (isset($_POST['toggle'])) {
        $lid = (int)$_POST['toggle'];
        $stmt = $tekupdo->prepare("UPDATE shortened_urls SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$lid]);
        logAdminAction($admin['id'], 'toggle_link', 'link', $lid, 'Toggled active status', $tekupdo);
        setFlash('success', $lang === 'es' ? 'Estado actualizado.' : 'Status updated.');
    }

    if (isset($_POST['delete'])) {
        $lid = (int)$_POST['delete'];
        $stmt = $tekupdo->prepare("DELETE FROM shortened_urls WHERE id = ?");
        $stmt->execute([$lid]);
        logAdminAction($admin['id'], 'delete_link', 'link', $lid, null, $tekupdo);
        setFlash('success', $lang === 'es' ? 'Enlace eliminado.' : 'Link deleted.');
    }

    header("Location: links.php" . (isset($_POST['search']) ? "?search=" . urlencode($_POST['search']) : ""));
    exit;
}

$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = "1=1";
$params = [];
if ($search) {
    $where .= " AND (su.original_url LIKE ? OR su.short_code LIKE ? OR u.email LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$countStmt = $tekupdo->prepare("SELECT COUNT(*) FROM shortened_urls su JOIN users u ON su.user_id = u.id WHERE {$where}");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));

$stmt = $tekupdo->prepare("SELECT su.*, u.email, (SELECT COUNT(*) FROM link_metrics lm WHERE lm.url_id = su.id) as click_count FROM shortened_urls su JOIN users u ON su.user_id = u.id WHERE {$where} ORDER BY su.created_at DESC LIMIT {$perPage} OFFSET {$offset}");
$stmt->execute($params);
$links = $stmt->fetchAll();

include __DIR__ . '/../views/layout_header.php';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
    <div>
        <a href="index.php" style="font-size:0.7rem;font-weight:700;text-transform:uppercase;color:#999;display:inline-flex;align-items:center;gap:0.35rem;margin-bottom:0.5rem;"><i class="fas fa-arrow-left"></i> <?= e($lang === 'es' ? 'Volver' : 'Back') ?></a>
        <h1 style="font-size:1.75rem;font-weight:700;text-transform:uppercase;margin-bottom:0.25rem;"><?= e($lang === 'es' ? 'Gestionar Enlaces' : 'Manage Links') ?></h1>
        <p style="font-size:0.75rem;color:#999;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;"><?= formatNumber($total) ?> <?= e($lang === 'es' ? 'enlaces en el sistema' : 'links in the system') ?></p>
    </div>
</div>

<div class="nb-card mb-2" style="padding:1rem;">
    <form method="GET" style="display:flex;gap:0.5rem;">
        <input type="text" name="search" value="<?= e($search) ?>" class="nb-input" placeholder="<?= e($lang === 'es' ? 'Buscar por URL, codigo o email...' : 'Search by URL, code or email...') ?>" style="box-shadow:none;border-width:2px;flex:1;">
        <button type="submit" class="nb-btn nb-btn-sm"><i class="fas fa-search"></i></button>
        <?php if ($search): ?>
        <a href="links.php" class="nb-btn nb-btn-ghost nb-btn-sm"><i class="fas fa-times"></i></a>
        <?php endif; ?>
    </form>
</div>

<?php if (empty($links)): ?>
<div class="nb-card">
    <div class="nb-empty">
        <div class="nb-empty-icon"><i class="fas fa-link"></i></div>
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
                    <th><?= e($lang === 'es' ? 'Codigo' : 'Code') ?></th>
                    <th><?= e($lang === 'es' ? 'URL Destino' : 'Destination') ?></th>
                    <th><?= e($lang === 'es' ? 'Usuario' : 'User') ?></th>
                    <th style="text-align:center;"><?= e($lang === 'es' ? 'Clics' : 'Clicks') ?></th>
                    <th style="text-align:center;"><?= e($lang === 'es' ? 'Estado' : 'Status') ?></th>
                    <th><?= e($lang === 'es' ? 'Creado' : 'Created') ?></th>
                    <th style="text-align:center;"><?= e($lang === 'es' ? 'Acciones' : 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($links as $l): ?>
                <tr>
                    <td style="font-size:0.7rem;color:#999;"><?= $l['id'] ?></td>
                    <td class="text-mono" style="font-size:0.8rem;"><?= e($l['short_code']) ?></td>
                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#666;font-size:0.75rem;" title="<?= e($l['original_url']) ?>"><?= e($l['original_url']) ?></td>
                    <td style="font-size:0.7rem;color:#666;"><?= e($l['email']) ?></td>
                    <td style="text-align:center;font-weight:700;font-size:0.8rem;"><?= formatNumber($l['click_count']) ?></td>
                    <td style="text-align:center;">
                        <form method="POST" style="display:inline;">
                            <?= csrfField() ?>
                            <input type="hidden" name="toggle" value="<?= $l['id'] ?>">
                            <input type="hidden" name="search" value="<?= e($search) ?>">
                            <button type="submit" style="background:none;border:none;padding:0;cursor:pointer;">
                                <span class="nb-badge <?= $l['is_active'] ? 'nb-badge-success' : 'nb-badge-muted' ?>" style="font-size:0.5rem;"><?= $l['is_active'] ? 'ON' : 'OFF' ?></span>
                            </button>
                        </form>
                    </td>
                    <td style="font-size:0.7rem;color:#999;"><?= timeAgo($l['created_at']) ?></td>
                    <td style="text-align:center;">
                        <div style="display:flex;gap:0.35rem;justify-content:center;">
                            <a href="../link_analytics.php?id=<?= $l['id'] ?>" class="nb-btn nb-btn-xs" title="Analytics"><i class="fas fa-chart-line"></i></a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('<?= e($lang === 'es' ? 'Eliminar enlace?' : 'Delete link?') ?>')">
                                <?= csrfField() ?>
                                <input type="hidden" name="delete" value="<?= $l['id'] ?>">
                                <input type="hidden" name="search" value="<?= e($search) ?>">
                                <button type="submit" class="nb-btn nb-btn-xs nb-btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
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
