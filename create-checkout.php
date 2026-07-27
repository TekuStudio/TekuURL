<?php
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/config.php';
requireLogin();

$user = getCurrentUser($tekupdo);

$plan = $_GET['plan'] ?? 'pro';
if (!in_array($plan, ['pro', 'enterprise'])) $plan = 'pro';

$priceId = $plan === 'enterprise' ? 'price_tu_price_id_enterprise' : 'price_tu_price_id_pro';

require_once __DIR__ . '/vendor/autoload.php';
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

$checkout = \Stripe\Checkout\Session::create([
    'payment_method_types' => ['card'],
    'line_items' => [[
        'price' => $priceId,
        'quantity' => 1,
    ]],
    'mode' => 'subscription',
    'success_url' => BASE_URL . '/dashboard.php?upgraded=1',
    'cancel_url' => BASE_URL . '/pricing.php',
    'client_reference_id' => $_SESSION['user_id'],
    'metadata' => ['plan' => $plan],
]);

header("Location: " . $checkout->url);
exit;
