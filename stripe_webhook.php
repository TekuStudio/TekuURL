<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

$payload = @file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$event = null;

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, STRIPE_WEBHOOK_SECRET);
} catch (\UnexpectedValueException $e) {
    http_response_code(400); exit;
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400); exit;
}

switch ($event->type) {
    case 'checkout.session.completed':
        $session = $event->data->object;
        $customerId = $session->customer;
        $subscriptionId = $session->subscription;
        $userId = $session->client_reference_id;
        $planType = $session->metadata->plan ?? 'pro';

        $stmt = $tekupdo->prepare("UPDATE users SET stripe_customer_id = ?, stripe_subscription_id = ?, plan_status = ?, plan_expires_at = DATE_ADD(NOW(), INTERVAL 1 MONTH) WHERE id = ?");
        $stmt->execute([$customerId, $subscriptionId, $planType, $userId]);
        break;

    case 'invoice.payment_failed':
    case 'customer.subscription.deleted':
        $subscription = $event->data->object;
        $subscriptionId = $subscription->id;
        $stmt = $tekupdo->prepare("UPDATE users SET plan_status = 'free', plan_expires_at = NULL WHERE stripe_subscription_id = ?");
        $stmt->execute([$subscriptionId]);
        break;
}

http_response_code(200);
