<?php
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/config.php';
requireLogin();
$user = getCurrentUser($tekupdo);

$range = (int)($_GET['range'] ?? 30);
if (!in_array($range, [7, 30, 90])) $range = 30;

$totalClicks = $tekupdo->prepare("SELECT COUNT(*) FROM link_metrics lm JOIN shortened_urls su ON lm.url_id = su.id WHERE su.user_id = ? AND lm.clicked_at >= DATE_SUB(NOW(), INTERVAL ? DAY)");
$totalClicks->execute([$user['id'], $range]);
$rangeClicks = $totalClicks->fetchColumn();

$dailyClicks = $tekupdo->prepare("SELECT DATE(lm.clicked_at) as date, COUNT(*) as clicks FROM link_metrics lm JOIN shortened_urls su ON lm.url_id = su.id WHERE su.user_id = ? AND lm.clicked_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY DATE(lm.clicked_at) ORDER BY date");
$dailyClicks->execute([$user['id'], $range]);
$daily = $dailyClicks->fetchAll();

$byCountry = $tekupdo->prepare("SELECT lm.country_code, lm.country_name, COUNT(*) as qty FROM link_metrics lm JOIN shortened_urls su ON lm.url_id = su.id WHERE su.user_id = ? AND lm.clicked_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY lm.country_code, lm.country_name ORDER BY qty DESC LIMIT 10");
$byCountry->execute([$user['id'], $range]);
$countries = $byCountry->fetchAll();

$byDevice = $tekupdo->prepare("SELECT device_type, COUNT(*) as qty FROM link_metrics lm JOIN shortened_urls su ON lm.url_id = su.id WHERE su.user_id = ? AND lm.clicked_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY device_type");
$byDevice->execute([$user['id'], $range]);
$devices = $byDevice->fetchAll();

$byBrowser = $tekupdo->prepare("SELECT browser, COUNT(*) as qty FROM link_metrics lm JOIN shortened_urls su ON lm.url_id = su.id WHERE su.user_id = ? AND lm.clicked_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY browser ORDER BY qty DESC LIMIT 10");
$byBrowser->execute([$user['id'], $range]);
$browsers = $byBrowser->fetchAll();

$byReferrer = $tekupdo->prepare("SELECT referrer_domain, COUNT(*) as qty FROM link_metrics lm JOIN shortened_urls su ON lm.url_id = su.id WHERE su.user_id = ? AND lm.clicked_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY referrer_domain ORDER BY qty DESC LIMIT 10");
$byReferrer->execute([$user['id'], $range]);
$referrers = $byReferrer->fetchAll();

$hasData = ($rangeClicks > 0);

include __DIR__ . '/views/layout_header.php';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2rem;flex-wrap:wrap;gap:1rem;">
    <div>
        <h1 style="font-size:1.75rem;font-weight:700;text-transform:uppercase;margin-bottom:0.25rem;"><?= e($t['analytics_title']) ?></h1>
        <p style="font-size:0.75rem;color:#999;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;"><?= formatNumber($rangeClicks) ?> <?= e($lang === 'es' ? 'clics en' : 'clicks in') ?> <?= $range ?> <?= e($lang === 'es' ? 'dias' : 'days') ?></p>
    </div>
    <div style="display:flex;gap:0.35rem;align-items:center;">
        <a href="analytics.php?range=7" class="nb-btn nb-btn-sm <?= $range === 7 ? 'nb-btn-filled' : '' ?>">7D</a>
        <a href="analytics.php?range=30" class="nb-btn nb-btn-sm <?= $range === 30 ? 'nb-btn-filled' : '' ?>">30D</a>
        <a href="analytics.php?range=90" class="nb-btn nb-btn-sm <?= $range === 90 ? 'nb-btn-filled' : '' ?>">90D</a>
        <span style="width:1px;height:20px;background:#DDD;margin:0 0.25rem;"></span>
        <a href="export_analytics.php?range=<?= $range ?>&format=csv" class="nb-btn nb-btn-sm nb-btn-ghost" title="CSV"><i class="fas fa-file-csv" style="margin-right:0.25rem;"></i> CSV</a>
        <a href="export_analytics.php?range=<?= $range ?>&format=json" class="nb-btn nb-btn-sm nb-btn-ghost" title="JSON"><i class="fas fa-file-code" style="margin-right:0.25rem;"></i> JSON</a>
    </div>
</div>

<?php if (!$hasData): ?>
<div class="nb-card mb-3">
    <div class="nb-empty">
        <div class="nb-empty-icon"><i class="fas fa-chart-bar"></i></div>
        <div class="nb-empty-text"><?= e($lang === 'es' ? 'Sin datos para este periodo' : 'No data for this period') ?></div>
        <div class="nb-empty-sub"><?= e($lang === 'es' ? 'Intenta otro rango de fechas o comparte tus enlaces.' : 'Try a different date range or share your links.') ?></div>
    </div>
</div>
<?php else: ?>

<div class="nb-card mb-2">
    <h3 style="font-size:0.75rem;font-weight:700;text-transform:uppercase;margin-bottom:1rem;"><?= e($t['analytics_over_time']) ?></h3>
    <div style="position:relative;height:280px;">
        <canvas id="analyticsChart"></canvas>
    </div>
</div>

<div class="nb-grid-2" style="margin-bottom:1.5rem;">
    <div class="nb-card">
        <h3 style="font-size:0.75rem;font-weight:700;text-transform:uppercase;margin-bottom:1rem;"><?= e($t['analytics_by_country']) ?></h3>
        <?php if (empty($countries)): ?>
        <div class="nb-empty" style="padding:2rem;"><div class="nb-empty-icon"><i class="fas fa-globe"></i></div><div class="nb-empty-sub"><?= e($lang === 'es' ? 'Sin datos' : 'No data') ?></div></div>
        <?php else: ?>
        <div style="position:relative;height:220px;">
            <canvas id="countryChart"></canvas>
        </div>
        <?php endif; ?>
    </div>
    <div class="nb-card">
        <h3 style="font-size:0.75rem;font-weight:700;text-transform:uppercase;margin-bottom:1rem;"><?= e($t['analytics_by_device']) ?></h3>
        <?php if (empty($devices)): ?>
        <div class="nb-empty" style="padding:2rem;"><div class="nb-empty-icon"><i class="fas fa-mobile-alt"></i></div><div class="nb-empty-sub"><?= e($lang === 'es' ? 'Sin datos' : 'No data') ?></div></div>
        <?php else: ?>
        <div style="position:relative;height:220px;">
            <canvas id="deviceChart"></canvas>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="nb-grid-2">
    <div class="nb-card">
        <h3 style="font-size:0.75rem;font-weight:700;text-transform:uppercase;margin-bottom:1rem;"><?= e($t['analytics_by_browser']) ?></h3>
        <?php if (empty($browsers)): ?>
        <div class="nb-empty" style="padding:2rem;"><div class="nb-empty-icon"><i class="fas fa-globe"></i></div><div class="nb-empty-sub"><?= e($lang === 'es' ? 'Sin datos' : 'No data') ?></div></div>
        <?php else: ?>
        <div style="position:relative;height:220px;">
            <canvas id="browserChart"></canvas>
        </div>
        <?php endif; ?>
    </div>
    <div class="nb-card">
        <h3 style="font-size:0.75rem;font-weight:700;text-transform:uppercase;margin-bottom:1rem;"><?= e($t['analytics_by_referrer']) ?></h3>
        <?php if (empty($referrers)): ?>
        <div class="nb-empty" style="padding:2rem;"><div class="nb-empty-icon"><i class="fas fa-external-link-alt"></i></div><div class="nb-empty-sub"><?= e($lang === 'es' ? 'Sin datos' : 'No data') ?></div></div>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;">
            <?php foreach ($referrers as $i => $r): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:0.65rem 0;<?= $i < count($referrers) - 1 ? 'border-bottom:2px solid #F3F4F6;' : '' ?>">
                <span style="font-size:0.8rem;font-weight:600;"><?= e($r['referrer_domain']) ?></span>
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
    if (daily.length > 0 && document.getElementById('analyticsChart')) {
        new Chart(document.getElementById('analyticsChart'), {
            type: 'line',
            data: {
                labels: daily.map(function(d) { return d.date ? d.date.substring(5) : ''; }),
                datasets: [{
                    label: 'Clicks',
                    data: daily.map(function(d) { return parseInt(d.clicks) || 0; }),
                    borderColor: '#000',
                    backgroundColor: 'rgba(0,0,0,0.05)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3,
                    pointBackgroundColor: '#000',
                    pointBorderColor: '#FFF',
                    pointBorderWidth: 2,
                    pointHoverRadius: 5
                }]
            },
            options: Object.assign({}, chartDefaults, {
                scales: {
                    y: { beginAtZero: true, grid: { color: '#F3F4F6' } },
                    x: { grid: { display: false }, ticks: { font: { size: 9 }, maxRotation: 0 } }
                },
                interaction: { intersect: false, mode: 'index' }
            })
        });
    }

    var countries = <?= json_encode($countries, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    if (countries.length > 0 && document.getElementById('countryChart')) {
        new Chart(document.getElementById('countryChart'), {
            type: 'bar',
            data: {
                labels: countries.map(function(c) { return c.country_name || c.country_code; }),
                datasets: [{ label: 'Clicks', data: countries.map(function(c) { return parseInt(c.qty); }), backgroundColor: '#000', barPercentage: 0.7 }]
            },
            options: Object.assign({}, chartDefaults, {
                indexAxis: 'y',
                scales: {
                    x: { beginAtZero: true, grid: { color: '#F3F4F6' } },
                    y: { grid: { display: false } }
                }
            })
        });
    }

    var devs = <?= json_encode($devices, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    if (devs.length > 0 && document.getElementById('deviceChart')) {
        new Chart(document.getElementById('deviceChart'), {
            type: 'doughnut',
            data: {
                labels: devs.map(function(d) { return d.device_type; }),
                datasets: [{ data: devs.map(function(d) { return parseInt(d.qty); }), backgroundColor: ['#000', '#999', '#CCC', '#E5E7EB'], borderWidth: 3, borderColor: '#FFF' }]
            },
            options: Object.assign({}, chartDefaults, {
                plugins: { legend: { position: 'bottom', labels: { font: { size: 10, weight: 'bold' } } } },
                cutout: '60%'
            })
        });
    }

    var brows = <?= json_encode($browsers, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    if (brows.length > 0 && document.getElementById('browserChart')) {
        new Chart(document.getElementById('browserChart'), {
            type: 'doughnut',
            data: {
                labels: brows.map(function(b) { return b.browser; }),
                datasets: [{ data: brows.map(function(b) { return parseInt(b.qty); }), backgroundColor: ['#000', '#666', '#999', '#CCC', '#E5E7EB'], borderWidth: 3, borderColor: '#FFF' }]
            },
            options: Object.assign({}, chartDefaults, {
                plugins: { legend: { position: 'bottom', labels: { font: { size: 10, weight: 'bold' } } } },
                cutout: '60%'
            })
        });
    }
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/views/layout_footer.php'; ?>
