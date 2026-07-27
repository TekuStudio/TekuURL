<?php
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/config.php';
requireLogin();
$user = getCurrentUser($tekupdo);

$totalClicksStmt = $tekupdo->prepare("SELECT COUNT(*) FROM link_metrics lm JOIN shortened_urls su ON lm.url_id = su.id WHERE su.user_id = ?");
$totalClicksStmt->execute([$user['id']]);
$totalClicks = $totalClicksStmt->fetchColumn();

$totalLinksStmt = $tekupdo->prepare("SELECT COUNT(*) FROM shortened_urls WHERE user_id = ?");
$totalLinksStmt->execute([$user['id']]);
$totalLinks = $totalLinksStmt->fetchColumn();

$activeLinksStmt = $tekupdo->prepare("SELECT COUNT(*) FROM shortened_urls WHERE user_id = ? AND is_active = 1");
$activeLinksStmt->execute([$user['id']]);
$activeLinks = $activeLinksStmt->fetchColumn();

$todayClicksStmt = $tekupdo->prepare("SELECT COUNT(*) FROM link_metrics lm JOIN shortened_urls su ON lm.url_id = su.id WHERE su.user_id = ? AND DATE(lm.clicked_at) = CURDATE()");
$todayClicksStmt->execute([$user['id']]);
$todayClicks = $todayClicksStmt->fetchColumn();

$yesterdayClicksStmt = $tekupdo->prepare("SELECT COUNT(*) FROM link_metrics lm JOIN shortened_urls su ON lm.url_id = su.id WHERE su.user_id = ? AND DATE(lm.clicked_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
$yesterdayClicksStmt->execute([$user['id']]);
$yesterdayClicks = $yesterdayClicksStmt->fetchColumn();

$weekClicksStmt = $tekupdo->prepare("SELECT COUNT(*) FROM link_metrics lm JOIN shortened_urls su ON lm.url_id = su.id WHERE su.user_id = ? AND lm.clicked_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$weekClicksStmt->execute([$user['id']]);
$weekClicks = $weekClicksStmt->fetchColumn();

$prevWeekClicksStmt = $tekupdo->prepare("SELECT COUNT(*) FROM link_metrics lm JOIN shortened_urls su ON lm.url_id = su.id WHERE su.user_id = ? AND lm.clicked_at >= DATE_SUB(NOW(), INTERVAL 14 DAY) AND lm.clicked_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
$prevWeekClicksStmt->execute([$user['id']]);
$prevWeekClicks = $prevWeekClicksStmt->fetchColumn();

$topCountryStmt = $tekupdo->prepare("SELECT lm.country_code, lm.country_name, COUNT(*) as qty FROM link_metrics lm JOIN shortened_urls su ON lm.url_id = su.id WHERE su.user_id = ? GROUP BY lm.country_code, lm.country_name ORDER BY qty DESC LIMIT 1");
$topCountryStmt->execute([$user['id']]);
$topCountry = $topCountryStmt->fetch();

$dailyClicksStmt = $tekupdo->prepare("SELECT DATE(lm.clicked_at) as date, COUNT(*) as clicks FROM link_metrics lm JOIN shortened_urls su ON lm.url_id = su.id WHERE su.user_id = ? AND lm.clicked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(lm.clicked_at) ORDER BY date");
$dailyClicksStmt->execute([$user['id']]);
$dailyClicks = $dailyClicksStmt->fetchAll();

$deviceStmt = $tekupdo->prepare("SELECT device_type, COUNT(*) as qty FROM link_metrics lm JOIN shortened_urls su ON lm.url_id = su.id WHERE su.user_id = ? GROUP BY device_type");
$deviceStmt->execute([$user['id']]);
$devices = $deviceStmt->fetchAll();

$browserStmt = $tekupdo->prepare("SELECT browser, COUNT(*) as qty FROM link_metrics lm JOIN shortened_urls su ON lm.url_id = su.id WHERE su.user_id = ? GROUP BY browser ORDER BY qty DESC LIMIT 5");
$browserStmt->execute([$user['id']]);
$browsers = $browserStmt->fetchAll();

$referrerStmt = $tekupdo->prepare("SELECT referrer_domain, COUNT(*) as qty FROM link_metrics lm JOIN shortened_urls su ON lm.url_id = su.id WHERE su.user_id = ? GROUP BY referrer_domain ORDER BY qty DESC LIMIT 5");
$referrerStmt->execute([$user['id']]);
$referrers = $referrerStmt->fetchAll();

$countryStmt = $tekupdo->prepare("SELECT lm.country_code, lm.country_name, COUNT(*) as qty FROM link_metrics lm JOIN shortened_urls su ON lm.url_id = su.id WHERE su.user_id = ? GROUP BY lm.country_code, lm.country_name ORDER BY qty DESC LIMIT 5");
$countryStmt->execute([$user['id']]);
$countries = $countryStmt->fetchAll();

$recentLinksStmt = $tekupdo->prepare("SELECT su.*, (SELECT COUNT(*) FROM link_metrics lm WHERE lm.url_id = su.id) as click_count FROM shortened_urls su WHERE su.user_id = ? ORDER BY su.created_at DESC LIMIT 5");
$recentLinksStmt->execute([$user['id']]);
$recentLinks = $recentLinksStmt->fetchAll();

$recentClicksStmt = $tekupdo->prepare("SELECT lm.*, su.short_code, su.original_url FROM link_metrics lm JOIN shortened_urls su ON lm.url_id = su.id WHERE su.user_id = ? ORDER BY lm.clicked_at DESC LIMIT 8");
$recentClicksStmt->execute([$user['id']]);
$recentClicks = $recentClicksStmt->fetchAll();

$hasData = ($totalClicks > 0 || $totalLinks > 0);
$linkLimit = getLinkLimit($user['plan_status']);
$usagePercent = $linkLimit > 0 ? min(100, round(($totalLinks / $linkLimit) * 100)) : 0;

function calcDelta($current, $previous) {
    if ($previous == 0) return $current > 0 ? 100 : 0;
    return round((($current - $previous) / $previous) * 100);
}

include __DIR__ . '/views/layout_header.php';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2rem;flex-wrap:wrap;gap:1rem;">
    <div>
        <h1 style="font-size:1.75rem;font-weight:700;text-transform:uppercase;margin-bottom:0.25rem;"><?= e($t['dash_title']) ?></h1>
        <p style="font-size:0.75rem;color:#999;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;"><?= date('d/m/Y') ?> &middot; <?= e($lang === 'es' ? 'Resumen de tu cuenta' : 'Your account overview') ?></p>
    </div>
    <a href="links.php" class="nb-btn nb-btn-filled nb-btn-sm"><i class="fas fa-plus"></i> <?= e($t['links_create']) ?></a>
</div>

<div class="nb-grid-4" style="margin-bottom:1.5rem;">
    <div class="nb-stat">
        <div class="nb-stat-value"><?= formatNumber($totalClicks) ?></div>
        <div class="nb-stat-label"><?= e($t['dash_total_clicks']) ?></div>
        <div class="nb-stat-change <?= calcDelta($weekClicks, $prevWeekClicks) >= 0 ? 'up' : 'down' ?>"><?= calcDelta($weekClicks, $prevWeekClicks) >= 0 ? '+' : '' ?><?= calcDelta($weekClicks, $prevWeekClicks) ?>% <?= e($lang === 'es' ? 'vs semana ant.' : 'vs last wk') ?></div>
    </div>
    <div class="nb-stat">
        <div class="nb-stat-value"><?= formatNumber($totalLinks) ?></div>
        <div class="nb-stat-label"><?= e($t['dash_total_links']) ?></div>
        <div class="nb-stat-change up"><?= $activeLinks ?> <?= e($lang === 'es' ? 'activos' : 'active') ?></div>
    </div>
    <div class="nb-stat">
        <div class="nb-stat-value"><?= formatNumber($todayClicks) ?></div>
        <div class="nb-stat-label"><?= e($lang === 'es' ? 'Clics Hoy' : "Today's Clicks") ?></div>
        <div class="nb-stat-change <?= $todayClicks >= $yesterdayClicks ? 'up' : 'down' ?>"><?= $todayClicks >= $yesterdayClicks ? '+' : '' ?><?= $yesterdayClicks > 0 ? round((($todayClicks - $yesterdayClicks) / $yesterdayClicks) * 100) : ($todayClicks > 0 ? 100 : 0) ?>% <?= e($lang === 'es' ? 'vs ayer' : 'vs yesterday') ?></div>
    </div>
    <div class="nb-stat">
        <div class="nb-stat-value"><?= $topCountry ? e($topCountry['country_code']) : '—' ?></div>
        <div class="nb-stat-label"><?= e($t['dash_top_country']) ?></div>
        <?php if ($topCountry): ?>
        <div class="nb-stat-change up"><?= formatNumber($topCountry['qty']) ?> <?= e($lang === 'es' ? 'clics' : 'clicks') ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="nb-card" style="margin-bottom:1.5rem;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;">
        <span style="font-size:0.7rem;font-weight:700;text-transform:uppercase;color:#888;"><?= e($lang === 'es' ? 'Uso del Plan' : 'Plan Usage') ?></span>
        <span style="font-size:0.7rem;font-weight:700;"><?= formatNumber($totalLinks) ?> / <?= formatNumber($linkLimit) ?> <?= e($lang === 'es' ? 'enlaces' : 'links') ?></span>
    </div>
    <div class="nb-progress" style="height:8px;"><div class="nb-progress-fill" style="width:<?= $usagePercent ?>%"></div></div>
    <?php if ($usagePercent > 80): ?>
    <p style="font-size:0.65rem;color:#999;margin-top:0.5rem;font-weight:600;text-transform:uppercase;"><i class="fas fa-exclamation-triangle" style="margin-right:0.25rem;"></i> <?= e($lang === 'es' ? 'Cerca del limite. Considera mejorar tu plan.' : 'Near your limit. Consider upgrading your plan.') ?></p>
    <?php endif; ?>
</div>

<?php if (!$hasData): ?>
<div class="nb-card mb-3">
    <div class="nb-empty">
        <div class="nb-empty-icon"><i class="fas fa-hat-wizard"></i></div>
        <div class="nb-empty-text"><?= e($lang === 'es' ? 'Tu primer enlace te espera, mago.' : 'Your first link awaits, wizard.') ?></div>
        <div class="nb-empty-sub"><?= e($lang === 'es' ? 'Lanza tu hechizo... digo, tu URL, aqui abajo.' : 'Cast your spell... I mean, your URL, below.') ?></div>
        <a href="links.php" class="nb-btn nb-btn-filled nb-btn-sm"><i class="fas fa-plus"></i> <?= e($t['links_create']) ?></a>
    </div>
</div>
<?php else: ?>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">
    <div class="nb-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
            <h3 style="font-size:0.75rem;font-weight:700;text-transform:uppercase;"><?= e($t['dash_clicks_trend']) ?></h3>
            <span class="nb-badge nb-badge-muted" style="font-size:0.55rem;">30 <?= e($lang === 'es' ? 'DIAS' : 'DAYS') ?></span>
        </div>
        <div style="position:relative;height:240px;">
            <canvas id="clicksChart"></canvas>
        </div>
    </div>
    <div class="nb-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
            <h3 style="font-size:0.75rem;font-weight:700;text-transform:uppercase;"><?= e($lang === 'es' ? 'Actividad Reciente' : 'Recent Activity') ?></h3>
        </div>
        <?php if (empty($recentClicks)): ?>
        <div class="nb-empty" style="padding:1.5rem;"><div class="nb-empty-sub"><?= e($lang === 'es' ? 'Sin actividad' : 'No activity') ?></div></div>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;max-height:240px;overflow-y:auto;">
            <?php foreach ($recentClicks as $rc): ?>
            <div style="display:flex;align-items:flex-start;gap:0.75rem;padding:0.5rem 0;border-bottom:1px solid #F3F4F6;">
                <div style="width:6px;height:6px;background:#000;border-radius:50%;margin-top:5px;flex-shrink:0;"></div>
                <div style="min-width:0;">
                    <div style="font-size:0.7rem;font-weight:700;"><?= e($rc['short_code']) ?> <span style="color:#999;font-weight:400;"><?= e($lang === 'es' ? 'recibio un clic' : 'got a click') ?></span></div>
                    <div style="font-size:0.6rem;color:#BBB;text-transform:uppercase;"><?= e($rc['country_code']) ?> · <?= e($rc['device_type']) ?> · <?= e($rc['browser']) ?> · <?= timeAgo($rc['clicked_at']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="nb-grid-3" style="margin-bottom:1.5rem;">
    <div class="nb-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
            <h3 style="font-size:0.75rem;font-weight:700;text-transform:uppercase;"><?= e($t['dash_countries']) ?></h3>
        </div>
        <?php if (empty($countries)): ?>
        <div class="nb-empty" style="padding:2rem;"><div class="nb-empty-icon"><i class="fas fa-globe"></i></div><div class="nb-empty-sub"><?= e($lang === 'es' ? 'Sin datos' : 'No data') ?></div></div>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;">
            <?php foreach ($countries as $i => $c): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:0.6rem 0;<?= $i < count($countries) - 1 ? 'border-bottom:2px solid #F3F4F6;' : '' ?>">
                <div style="display:flex;align-items:center;gap:0.5rem;">
                    <span style="font-size:0.85rem;font-weight:700;"><?= e($c['country_code']) ?></span>
                    <span style="font-size:0.7rem;color:#888;"><?= e($c['country_name']) ?></span>
                </div>
                <span class="nb-badge nb-badge-muted"><?= formatNumber($c['qty']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <div class="nb-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
            <h3 style="font-size:0.75rem;font-weight:700;text-transform:uppercase;"><?= e($t['dash_devices']) ?></h3>
        </div>
        <?php if (empty($devices)): ?>
        <div class="nb-empty" style="padding:2rem;"><div class="nb-empty-icon"><i class="fas fa-mobile-alt"></i></div><div class="nb-empty-sub"><?= e($lang === 'es' ? 'Sin datos' : 'No data') ?></div></div>
        <?php else: ?>
        <div style="position:relative;height:200px;"><canvas id="devicesChart"></canvas></div>
        <?php endif; ?>
    </div>
    <div class="nb-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
            <h3 style="font-size:0.75rem;font-weight:700;text-transform:uppercase;"><?= e($t['dash_referrers']) ?></h3>
        </div>
        <?php if (empty($referrers)): ?>
        <div class="nb-empty" style="padding:2rem;"><div class="nb-empty-icon"><i class="fas fa-external-link-alt"></i></div><div class="nb-empty-sub"><?= e($lang === 'es' ? 'Sin datos' : 'No data') ?></div></div>
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

<div class="nb-card mb-3">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
        <h3 style="font-size:0.75rem;font-weight:700;text-transform:uppercase;"><?= e($lang === 'es' ? 'Enlaces Recientes' : 'Recent Links') ?></h3>
        <a href="links.php" class="nb-btn nb-btn-ghost nb-btn-xs"><?= e($lang === 'es' ? 'Ver Todos' : 'View All') ?> <i class="fas fa-arrow-right" style="font-size:0.55rem;margin-left:0.35rem;"></i></a>
    </div>
    <?php if (empty($recentLinks)): ?>
    <div class="nb-empty" style="padding:2rem;"><div class="nb-empty-sub"><?= e($lang === 'es' ? 'No hay enlaces aun' : 'No links yet') ?></div></div>
    <?php else: ?>
    <div class="nb-table-wrap">
        <table class="nb-table">
            <thead>
                <tr>
                    <th><?= e($lang === 'es' ? 'Codigo' : 'Code') ?></th>
                    <th><?= e($lang === 'es' ? 'URL Destino' : 'Destination') ?></th>
                    <th style="text-align:center;"><?= e($t['links_clicks']) ?></th>
                    <th style="text-align:center;"><?= e($t['links_status']) ?></th>
                    <th><?= e($t['links_created']) ?></th>
                    <th style="text-align:center;"><?= e($t['links_actions']) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentLinks as $l): ?>
                <tr>
                    <td><span class="text-mono" style="font-size:0.8rem;"><?= e($l['short_code']) ?></span></td>
                    <td style="max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#666;font-size:0.75rem;" title="<?= e($l['original_url']) ?>"><?= e($l['original_url']) ?></td>
                    <td style="text-align:center;font-weight:700;"><?= formatNumber($l['click_count']) ?></td>
                    <td style="text-align:center;"><span class="nb-badge <?= $l['is_active'] ? 'nb-badge-success' : 'nb-badge-muted' ?>"><?= $l['is_active'] ? e($t['links_active']) : e($t['links_inactive']) ?></span></td>
                    <td style="font-size:0.7rem;color:#999;"><?= timeAgo($l['created_at']) ?></td>
                    <td style="text-align:center;">
                        <div style="display:flex;gap:0.35rem;justify-content:center;">
                            <a href="link_analytics.php?id=<?= $l['id'] ?>" class="nb-btn nb-btn-xs nb-btn-ghost" title="Analytics"><i class="fas fa-chart-line"></i></a>
                            <button onclick="copyLink('<?= e($l['short_code']) ?>')" class="nb-btn nb-btn-xs" title="Copy"><i class="fas fa-copy"></i></button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<?php if ($hasData): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var chartDefaults = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } };

    var daily = <?= json_encode($dailyClicks, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    if (daily.length > 0 && document.getElementById('clicksChart')) {
        new Chart(document.getElementById('clicksChart'), {
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
                    y: { beginAtZero: true, grid: { color: '#F3F4F6' }, ticks: { font: { size: 10 } } },
                    x: { grid: { display: false }, ticks: { font: { size: 9 }, maxRotation: 0 } }
                },
                interaction: { intersect: false, mode: 'index' }
            })
        });
    }

    var devs = <?= json_encode($devices, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    if (devs.length > 0 && document.getElementById('devicesChart')) {
        new Chart(document.getElementById('devicesChart'), {
            type: 'doughnut',
            data: {
                labels: devs.map(function(d) { return d.device_type; }),
                datasets: [{ data: devs.map(function(d) { return parseInt(d.qty); }), backgroundColor: ['#000', '#999', '#CCC', '#E5E7EB'], borderWidth: 3, borderColor: '#FFF' }]
            },
            options: Object.assign({}, chartDefaults, {
                plugins: { legend: { position: 'bottom', labels: { font: { size: 10, weight: 'bold' }, padding: 12 } } },
                cutout: '60%'
            })
        });
    }
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/views/layout_footer.php'; ?>
