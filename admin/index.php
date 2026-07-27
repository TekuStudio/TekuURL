<?php
require_once __DIR__ . '/../lang.php';
require_once __DIR__ . '/../config.php';
requireAdmin();
$admin = getCurrentUser($tekupdo);

$totalUsers = $tekupdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalLinks = $tekupdo->query("SELECT COUNT(*) FROM shortened_urls")->fetchColumn();
$totalClicks = $tekupdo->query("SELECT COUNT(*) FROM link_metrics")->fetchColumn();
$totalActiveLinks = $tekupdo->query("SELECT COUNT(*) FROM shortened_urls WHERE is_active = 1")->fetchColumn();

$todayUsers = $tekupdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$todayLinks = $tekupdo->query("SELECT COUNT(*) FROM shortened_urls WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$todayClicks = $tekupdo->query("SELECT COUNT(*) FROM link_metrics WHERE DATE(clicked_at) = CURDATE()")->fetchColumn();

$recentUsers = $tekupdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 10")->fetchAll();
$topLinks = $tekupdo->query("SELECT su.*, u.email, (SELECT COUNT(*) FROM link_metrics lm WHERE lm.url_id = su.id) as click_count FROM shortened_urls su JOIN users u ON su.user_id = u.id ORDER BY click_count DESC LIMIT 10")->fetchAll();

$dailyUsers = $tekupdo->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY date")->fetchAll();
$dailyClicks = $tekupdo->query("SELECT DATE(clicked_at) as date, COUNT(*) as count FROM link_metrics WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(clicked_at) ORDER BY date")->fetchAll();

include __DIR__ . '/../views/layout_header.php';
?>

<div style="margin-bottom:2rem;">
    <h1 style="font-size:1.75rem;font-weight:700;text-transform:uppercase;margin-bottom:0.25rem;"><i class="fas fa-shield-alt" style="margin-right:0.5rem;font-size:1.25rem;"></i> <?= e($lang === 'es' ? 'Panel de Administracion' : 'Admin Panel') ?></h1>
    <p style="font-size:0.75rem;color:#999;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;"><?= e($lang === 'es' ? 'Vista general del sistema' : 'System overview') ?></p>
</div>

<div class="nb-grid-4" style="margin-bottom:2rem;">
    <div class="nb-stat">
        <div class="nb-stat-value"><?= formatNumber($totalUsers) ?></div>
        <div class="nb-stat-label"><?= e($lang === 'es' ? 'Usuarios' : 'Users') ?></div>
        <div class="nb-stat-change up">+<?= $todayUsers ?> <?= e($lang === 'es' ? 'hoy' : 'today') ?></div>
    </div>
    <div class="nb-stat">
        <div class="nb-stat-value"><?= formatNumber($totalLinks) ?></div>
        <div class="nb-stat-label"><?= e($lang === 'es' ? 'Enlaces' : 'Links') ?></div>
        <div class="nb-stat-change up">+<?= $todayLinks ?> <?= e($lang === 'es' ? 'hoy' : 'today') ?></div>
    </div>
    <div class="nb-stat">
        <div class="nb-stat-value"><?= formatNumber($totalClicks) ?></div>
        <div class="nb-stat-label"><?= e($lang === 'es' ? 'Clics Totales' : 'Total Clicks') ?></div>
        <div class="nb-stat-change up">+<?= $todayClicks ?> <?= e($lang === 'es' ? 'hoy' : 'today') ?></div>
    </div>
    <div class="nb-stat">
        <div class="nb-stat-value"><?= formatNumber($totalActiveLinks) ?></div>
        <div class="nb-stat-label"><?= e($lang === 'es' ? 'Enlaces Activos' : 'Active Links') ?></div>
    </div>
</div>

<div class="nb-grid-2" style="margin-bottom:1.5rem;">
    <div class="nb-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
            <h3 style="font-size:0.75rem;font-weight:700;text-transform:uppercase;"><?= e($lang === 'es' ? 'Usuarios Recientes' : 'Recent Users') ?></h3>
            <a href="users.php" class="nb-btn nb-btn-ghost nb-btn-xs"><?= e($lang === 'es' ? 'Ver Todos' : 'View All') ?> <i class="fas fa-arrow-right" style="font-size:0.5rem;margin-left:0.25rem;"></i></a>
        </div>
        <?php if (empty($recentUsers)): ?>
        <div class="nb-empty" style="padding:1rem;"><div class="nb-empty-sub"><?= e($lang === 'es' ? 'Sin usuarios' : 'No users') ?></div></div>
        <?php else: ?>
        <div class="nb-table-wrap" style="border:none;">
            <table class="nb-table">
                <thead>
                    <tr>
                        <th><?= e($lang === 'es' ? 'Email' : 'Email') ?></th>
                        <th><?= e($lang === 'es' ? 'Rol' : 'Role') ?></th>
                        <th><?= e($lang === 'es' ? 'Plan' : 'Plan') ?></th>
                        <th><?= e($lang === 'es' ? 'Registro' : 'Joined') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentUsers as $u): ?>
                    <tr>
                        <td style="font-size:0.75rem;"><?= e($u['email']) ?></td>
                        <td><span class="nb-badge <?= ($u['role'] ?? 'user') === 'admin' ? 'nb-badge-filled' : 'nb-badge-muted' ?>" style="font-size:0.5rem;"><?= e(strtoupper($u['role'] ?? 'user')) ?></span></td>
                        <td><span class="nb-badge nb-badge-muted" style="font-size:0.5rem;"><?= e(strtoupper($u['plan_status'])) ?></span></td>
                        <td style="font-size:0.7rem;color:#999;"><?= timeAgo($u['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="nb-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
            <h3 style="font-size:0.75rem;font-weight:700;text-transform:uppercase;"><?= e($lang === 'es' ? 'Enlaces Top' : 'Top Links') ?></h3>
            <a href="links.php" class="nb-btn nb-btn-ghost nb-btn-xs"><?= e($lang === 'es' ? 'Ver Todos' : 'View All') ?> <i class="fas fa-arrow-right" style="font-size:0.5rem;margin-left:0.25rem;"></i></a>
        </div>
        <?php if (empty($topLinks)): ?>
        <div class="nb-empty" style="padding:1rem;"><div class="nb-empty-sub"><?= e($lang === 'es' ? 'Sin enlaces' : 'No links') ?></div></div>
        <?php else: ?>
        <div class="nb-table-wrap" style="border:none;">
            <table class="nb-table">
                <thead>
                    <tr>
                        <th><?= e($lang === 'es' ? 'Codigo' : 'Code') ?></th>
                        <th><?= e($lang === 'es' ? 'Usuario' : 'User') ?></th>
                        <th style="text-align:center;"><?= e($lang === 'es' ? 'Clics' : 'Clicks') ?></th>
                        <th><?= e($lang === 'es' ? 'Creado' : 'Created') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topLinks as $l): ?>
                    <tr>
                        <td class="text-mono" style="font-size:0.8rem;"><?= e($l['short_code']) ?></td>
                        <td style="font-size:0.7rem;color:#666;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($l['email']) ?></td>
                        <td style="text-align:center;font-weight:700;"><?= formatNumber($l['click_count']) ?></td>
                        <td style="font-size:0.7rem;color:#999;"><?= timeAgo($l['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($dailyClicks)): ?>
<div class="nb-card">
    <h3 style="font-size:0.75rem;font-weight:700;text-transform:uppercase;margin-bottom:1rem;"><?= e($lang === 'es' ? 'Clics por Dia (30 Dias)' : 'Clicks per Day (30 Days)') ?></h3>
    <div style="position:relative;height:200px;"><canvas id="adminClicksChart"></canvas></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var daily = <?= json_encode($dailyClicks, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    if (daily.length > 0 && document.getElementById('adminClicksChart')) {
        new Chart(document.getElementById('adminClicksChart'), {
            type: 'bar',
            data: {
                labels: daily.map(function(d) { return d.date ? d.date.substring(5) : ''; }),
                datasets: [{ label: 'Clicks', data: daily.map(function(d) { return parseInt(d.count) || 0; }), backgroundColor: '#000', barPercentage: 0.8 }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#F3F4F6' } }, x: { grid: { display: false }, ticks: { font: { size: 9 } } } } }
        });
    }
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/../views/layout_footer.php'; ?>
