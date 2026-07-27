<?php
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/config.php';
requireLogin();
$user = getCurrentUser($tekupdo);

$range = (int)($_GET['range'] ?? 30);
if (!in_array($range, [7, 30, 90])) $range = 30;
$format = $_GET['format'] ?? 'csv';
if (!in_array($format, ['csv', 'json'])) $format = 'csv';

$dailyClicks = $tekupdo->prepare("SELECT DATE(lm.clicked_at) as date, COUNT(*) as clicks FROM link_metrics lm JOIN shortened_urls su ON lm.url_id = su.id WHERE su.user_id = ? AND lm.clicked_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY DATE(lm.clicked_at) ORDER BY date");
$dailyClicks->execute([$user['id'], $range]);
$daily = $dailyClicks->fetchAll();

$byCountry = $tekupdo->prepare("SELECT lm.country_code, lm.country_name, COUNT(*) as qty FROM link_metrics lm JOIN shortened_urls su ON lm.url_id = su.id WHERE su.user_id = ? AND lm.clicked_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY lm.country_code, lm.country_name ORDER BY qty DESC");
$byCountry->execute([$user['id'], $range]);
$countries = $byCountry->fetchAll();

$byDevice = $tekupdo->prepare("SELECT device_type, COUNT(*) as qty FROM link_metrics lm JOIN shortened_urls su ON lm.url_id = su.id WHERE su.user_id = ? AND lm.clicked_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY device_type");
$byDevice->execute([$user['id'], $range]);
$devices = $byDevice->fetchAll();

$byBrowser = $tekupdo->prepare("SELECT browser, COUNT(*) as qty FROM link_metrics lm JOIN shortened_urls su ON lm.url_id = su.id WHERE su.user_id = ? AND lm.clicked_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY browser ORDER BY qty DESC");
$byBrowser->execute([$user['id'], $range]);
$browsers = $byBrowser->fetchAll();

$byReferrer = $tekupdo->prepare("SELECT referrer_domain, COUNT(*) as qty FROM link_metrics lm JOIN shortened_urls su ON lm.url_id = su.id WHERE su.user_id = ? AND lm.clicked_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY referrer_domain ORDER BY qty DESC");
$byReferrer->execute([$user['id'], $range]);
$referrers = $byReferrer->fetchAll();

$exportData = [
    'export_date' => date('Y-m-d H:i:s'),
    'period_days' => $range,
    'total_clicks' => array_sum(array_column($daily, 'clicks')),
    'daily_clicks' => $daily,
    'by_country' => $countries,
    'by_device' => $devices,
    'by_browser' => $browsers,
    'by_referrer' => $referrers,
];

if ($format === 'json') {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="tekuurl-analytics-' . $range . 'd.json"');
    echo json_encode($exportData, JSON_PRETTY_PRINT);
} else {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="tekuurl-analytics-' . $range . 'd.csv"');
    $output = fopen('php://output', 'w');

    fputcsv($output, ['TekuURL Analytics Export - ' . $range . ' Days']);
    fputcsv($output, ['Generated', date('Y-m-d H:i:s')]);
    fputcsv($output, []);

    fputcsv($output, ['DAILY CLICKS']);
    fputcsv($output, ['Date', 'Clicks']);
    foreach ($daily as $row) {
        fputcsv($output, [$row['date'], $row['clicks']]);
    }
    fputcsv($output, []);

    fputcsv($output, ['CLICKS BY COUNTRY']);
    fputcsv($output, ['Country Code', 'Country Name', 'Clicks']);
    foreach ($countries as $row) {
        fputcsv($output, [$row['country_code'], $row['country_name'], $row['qty']]);
    }
    fputcsv($output, []);

    fputcsv($output, ['CLICKS BY DEVICE']);
    fputcsv($output, ['Device', 'Clicks']);
    foreach ($devices as $row) {
        fputcsv($output, [$row['device_type'], $row['qty']]);
    }
    fputcsv($output, []);

    fputcsv($output, ['CLICKS BY BROWSER']);
    fputcsv($output, ['Browser', 'Clicks']);
    foreach ($browsers as $row) {
        fputcsv($output, [$row['browser'], $row['qty']]);
    }
    fputcsv($output, []);

    fputcsv($output, ['CLICKS BY REFERRER']);
    fputcsv($output, ['Domain', 'Clicks']);
    foreach ($referrers as $row) {
        fputcsv($output, [$row['referrer_domain'], $row['qty']]);
    }

    fclose($output);
}
exit;
