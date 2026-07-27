<?php
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/config.php';
requireLogin();
$user = getCurrentUser($tekupdo);

$urlId = (int)($_GET['id'] ?? 0);
if (!$urlId) { header("Location: links.php"); exit; }

$stmt = $tekupdo->prepare("SELECT su.*, (SELECT COUNT(*) FROM link_metrics lm WHERE lm.url_id = su.id) as click_count FROM shortened_urls su WHERE su.id = ? AND su.user_id = ?");
$stmt->execute([$urlId, $user['id']]);
$link = $stmt->fetch();
if (!$link) { header("Location: links.php"); exit; }

$range = (int)($_GET['range'] ?? 30);
if (!in_array($range, [7, 30, 90])) $range = 30;

$dailyClicks = $tekupdo->prepare("SELECT DATE(clicked_at) as date, COUNT(*) as clicks FROM link_metrics WHERE url_id = ? AND clicked_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY DATE(clicked_at) ORDER BY date");
$dailyClicks->execute([$urlId, $range]);
$daily = $dailyClicks->fetchAll();

$byCountry = $tekupdo->prepare("SELECT country_code, country_name, COUNT(*) as qty FROM link_metrics WHERE url_id = ? AND clicked_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY country_code, country_name ORDER BY qty DESC LIMIT 10");
$byCountry->execute([$urlId, $range]);
$countries = $byCountry->fetchAll();

$byDevice = $tekupdo->prepare("SELECT device_type, COUNT(*) as qty FROM link_metrics WHERE url_id = ? AND clicked_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY device_type");
$byDevice->execute([$urlId, $range]);
$devices = $byDevice->fetchAll();

$byBrowser = $tekupdo->prepare("SELECT browser, COUNT(*) as qty FROM link_metrics WHERE url_id = ? AND clicked_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY browser ORDER BY qty DESC LIMIT 10");
$byBrowser->execute([$urlId, $range]);
$browsers = $byBrowser->fetchAll();

$byReferrer = $tekupdo->prepare("SELECT referrer_domain, COUNT(*) as qty FROM link_metrics WHERE url_id = ? AND clicked_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY referrer_domain ORDER BY qty DESC LIMIT 10");
$byReferrer->execute([$urlId, $range]);
$referrers = $byReferrer->fetchAll();

$todayClicks = $tekupdo->prepare("SELECT COUNT(*) FROM link_metrics WHERE url_id = ? AND DATE(clicked_at) = CURDATE()");
$todayClicks->execute([$urlId]);
$todayCount = $todayClicks->fetchColumn();

include __DIR__ . '/views/layout_header.php';
?>

<div style="margin-bottom:1.5rem;">
    <a href="links.php" style="font-size:0.7rem;font-weight:700;text-transform:uppercase;color:#999;display:inline-flex;align-items:center;gap:0.35rem;margin-bottom:0.75rem;"><i class="fas fa-arrow-left"></i> <?= e($lang === 'es' ? 'Volver a Enlaces' : 'Back to Links') ?></a>
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div>
            <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.25rem;">
                <h1 style="font-size:1.5rem;font-weight:700;text-transform:uppercase;margin:0;"><?= e($link['short_code']) ?></h1>
                <span class="nb-badge <?= $link['is_active'] ? 'nb-badge-success' : 'nb-badge-muted' ?>"><?= $link['is_active'] ? e($t['links_active']) : e($t['links_inactive']) ?></span>
            </div>
            <p style="font-size:0.75rem;color:#666;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:500px;"><?= e($link['original_url']) ?></p>
        </div>
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
            <button onclick="copyLink('<?= e($link['short_code']) ?>')" class="nb-btn nb-btn-sm"><i class="fas fa-copy"></i> <?= e($t['links_copy']) ?></button>
            <?php if ($link['qr_code_path'] && file_exists($link['qr_code_path'])): ?>
            <a href="<?= e($link['qr_code_path']) ?>" target="_blank" class="nb-btn nb-btn-sm"><i class="fas fa-qrcode"></i> <?= e($t['qr_download']) ?></a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="nb-grid-4" style="margin-bottom:1.5rem;">
    <div class="nb-stat">
        <div class="nb-stat-value"><?= formatNumber($link['click_count']) ?></div>
        <div class="nb-stat-label"><?= e($t['dash_total_clicks']) ?></div>
    </div>
    <div class="nb-stat">
        <div class="nb-stat-value"><?= formatNumber($todayCount) ?></div>
        <div class="nb-stat-label"><?= e($lang === 'es' ? 'Hoy' : 'Today') ?></div>
    </div>
    <div class="nb-stat">
        <div class="nb-stat-value"><?= $countries ? e($countries[0]['country_code']) : '—' ?></div>
        <div class="nb-stat-label"><?= e($t['dash_top_country']) ?></div>
    </div>
    <div class="nb-stat">
        <div class="nb-stat-value"><?= $devices ? e($devices[0]['device_type']) : '—' ?></div>
        <div class="nb-stat-label"><?= e($lang === 'es' ? 'Top Dispositivo' : 'Top Device') ?></div>
    </div>
</div>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;">
    <h2 style="font-size:1rem;font-weight:700;text-transform:uppercase;"><?= e($t['analytics_title']) ?></h2>
    <div style="display:flex;gap:0.35rem;">
        <a href="?id=<?= $urlId ?>&range=7" class="nb-btn nb-btn-xs <?= $range === 7 ? 'nb-btn-filled' : '' ?>">7D</a>
        <a href="?id=<?= $urlId ?>&range=30" class="nb-btn nb-btn-xs <?= $range === 30 ? 'nb-btn-filled' : '' ?>">30D</a>
        <a href="?id=<?= $urlId ?>&range=90" class="nb-btn nb-btn-xs <?= $range === 90 ? 'nb-btn-filled' : '' ?>">90D</a>
    </div>
</div>

<?php $hasMetrics = ($link['click_count'] > 0); ?>

<?php if (!$hasMetrics): ?>
<div class="nb-card mb-3">
    <div class="nb-empty">
        <div class="nb-empty-icon"><i class="fas fa-chart-bar"></i></div>
        <div class="nb-empty-text"><?= e($lang === 'es' ? 'Sin datos de analiticas' : 'No analytics data yet') ?></div>
        <div class="nb-empty-sub"><?= e($lang === 'es' ? 'Comparte tu enlace para ver estadisticas aqui.' : 'Share your link to see statistics here.') ?></div>
    </div>
</div>
<?php else: ?>

<div class="nb-card mb-2">
    <h3 style="font-size:0.75rem;font-weight:700;text-transform:uppercase;margin-bottom:1rem;"><?= e($t['analytics_over_time']) ?></h3>
    <div style="position:relative;height:250px;"><canvas id="linkClicksChart"></canvas></div>
</div>

<div class="nb-grid-2" style="margin-bottom:1.5rem;">
    <div class="nb-card">
        <h3 style="font-size:0.75rem;font-weight:700;text-transform:uppercase;margin-bottom:1rem;"><?= e($t['analytics_by_country']) ?></h3>
        <?php if (empty($countries)): ?>
        <div class="nb-empty" style="padding:2rem;"><div class="nb-empty-sub"><?= e($lang === 'es' ? 'Sin datos' : 'No data') ?></div></div>
        <?php else: ?>
        <div style="position:relative;height:200px;"><canvas id="linkCountryChart"></canvas></div>
        <?php endif; ?>
    </div>
    <div class="nb-card">
        <h3 style="font-size:0.75rem;font-weight:700;text-transform:uppercase;margin-bottom:1rem;"><?= e($t['analytics_by_device']) ?></h3>
        <?php if (empty($devices)): ?>
        <div class="nb-empty" style="padding:2rem;"><div class="nb-empty-sub"><?= e($lang === 'es' ? 'Sin datos' : 'No data') ?></div></div>
        <?php else: ?>
        <div style="position:relative;height:200px;"><canvas id="linkDeviceChart"></canvas></div>
        <?php endif; ?>
    </div>
</div>

<div class="nb-grid-2">
    <div class="nb-card">
        <h3 style="font-size:0.75rem;font-weight:700;text-transform:uppercase;margin-bottom:1rem;"><?= e($t['analytics_by_browser']) ?></h3>
        <?php if (empty($browsers)): ?>
        <div class="nb-empty" style="padding:2rem;"><div class="nb-empty-sub"><?= e($lang === 'es' ? 'Sin datos' : 'No data') ?></div></div>
        <?php else: ?>
        <div style="position:relative;height:200px;"><canvas id="linkBrowserChart"></canvas></div>
        <?php endif; ?>
    </div>
    <div class="nb-card">
        <h3 style="font-size:0.75rem;font-weight:700;text-transform:uppercase;margin-bottom:1rem;"><?= e($t['analytics_by_referrer']) ?></h3>
        <?php if (empty($referrers)): ?>
        <div class="nb-empty" style="padding:1.5rem;"><div class="nb-empty-sub"><?= e($lang === 'es' ? 'Sin datos' : 'No data') ?></div></div>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;">
            <?php foreach ($referrers as $i => $r): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:0.6rem 0;<?= $i < count($referrers) - 1 ? 'border-bottom:2px solid #F3F4F6;' : '' ?>">
                <span style="font-size:0.75rem;font-weight:600;"><?= e($r['referrer_domain']) ?></span>
                <span class="nb-badge nb-badge-muted"><?= formatNumber($r['qty']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var chartDefaults = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } };
    var daily = <?= json_encode($daily, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    if (daily.length > 0 && document.getElementById('linkClicksChart')) {
        new Chart(document.getElementById('linkClicksChart'), {
            type: 'bar',
            data: { labels: daily.map(function(d) { return d.date ? d.date.substring(5) : ''; }), datasets: [{ label: 'Clicks', data: daily.map(function(d) { return parseInt(d.clicks) || 0; }), backgroundColor: '#000', barPercentage: 0.8 }] },
            options: Object.assign({}, chartDefaults, { scales: { y: { beginAtZero: true, grid: { color: '#F3F4F6' } }, x: { grid: { display: false }, ticks: { font: { size: 9 } } } } })
        });
    }
    var countries = <?= json_encode($countries, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    if (countries.length > 0 && document.getElementById('linkCountryChart')) {
        new Chart(document.getElementById('linkCountryChart'), {
            type: 'bar',
            data: { labels: countries.map(function(c) { return c.country_name || c.country_code; }), datasets: [{ label: 'Clicks', data: countries.map(function(c) { return parseInt(c.qty); }), backgroundColor: '#000', barPercentage: 0.7 }] },
            options: Object.assign({}, chartDefaults, { indexAxis: 'y', scales: { x: { beginAtZero: true, grid: { color: '#F3F4F6' } }, y: { grid: { display: false } } } })
        });
    }
    var devs = <?= json_encode($devices, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    if (devs.length > 0 && document.getElementById('linkDeviceChart')) {
        new Chart(document.getElementById('linkDeviceChart'), {
            type: 'doughnut',
            data: { labels: devs.map(function(d) { return d.device_type; }), datasets: [{ data: devs.map(function(d) { return parseInt(d.qty); }), backgroundColor: ['#000','#999','#CCC','#E5E7EB'], borderWidth: 3, borderColor: '#FFF' }] },
            options: Object.assign({}, chartDefaults, { plugins: { legend: { position: 'bottom', labels: { font: { size: 10, weight: 'bold' } } } }, cutout: '60%' })
        });
    }
    var brows = <?= json_encode($browsers, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    if (brows.length > 0 && document.getElementById('linkBrowserChart')) {
        new Chart(document.getElementById('linkBrowserChart'), {
            type: 'doughnut',
            data: { labels: brows.map(function(b) { return b.browser; }), datasets: [{ data: brows.map(function(b) { return parseInt(b.qty); }), backgroundColor: ['#000','#666','#999','#CCC','#E5E7EB'], borderWidth: 3, borderColor: '#FFF' }] },
            options: Object.assign({}, chartDefaults, { plugins: { legend: { position: 'bottom', labels: { font: { size: 10, weight: 'bold' } } } }, cutout: '60%' })
        });
    }
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/views/layout_footer.php'; ?>
