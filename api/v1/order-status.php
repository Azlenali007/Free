<?php
require_once __DIR__ . '/auth.php';

$order_number = sanitize($_GET['order_number'] ?? '');

if (empty($order_number)) {
    http_response_code(422);
    log_api_call($auth_account['id'], 422);
    echo json_encode(['status' => 'error', 'message' => 'order_number query parameter is required.']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT o.order_number, o.order_status, o.payment_status, o.total_amount, o.ff_uid, o.ff_nickname, o.ff_region,
           o.provider_order_id, o.created_at, oi.product_name, oi.diamonds_amount
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.order_number = ? AND o.user_id = ?
");
$stmt->execute([$order_number, $auth_account['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    log_api_call($auth_account['id'], 404);
    echo json_encode(['status' => 'error', 'message' => 'Order not found or unauthorized.']);
    exit;
}

log_api_call($auth_account['id'], 200);

echo json_encode([
    'status' => 'success',
    'order_number' => $order['order_number'],
    'order_status' => $order['order_status'],
    'payment_status' => $order['payment_status'],
    'ff_uid' => $order['ff_uid'],
    'product_name' => $order['product_name'],
    'diamonds_amount' => (int)$order['diamonds_amount'],
    'total_amount' => (float)$order['total_amount'],
    'provider_order_id' => $order['provider_order_id'],
    'created_at' => $order['created_at']
], JSON_PRETTY_PRINT);
