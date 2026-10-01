<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Only POST method is allowed.']);
    exit;
}

// Read raw JSON body
$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?: $_POST;

$product_id = (int)($input['product_id'] ?? 0);
$variant_id = (int)($input['variant_id'] ?? 0);
$ff_uid = sanitize($input['ff_uid'] ?? '');
$ff_nickname = sanitize($input['ff_nickname'] ?? '');
$ff_region = sanitize($input['ff_region'] ?? 'Global');

if (!$product_id) {
    http_response_code(422);
    log_api_call($auth_account['id'], 422, $raw);
    echo json_encode(['status' => 'error', 'code' => 'MISSING_PRODUCT_ID', 'message' => 'product_id is required.']);
    exit;
}

if (empty($ff_uid) || !preg_match('/^[0-9]{5,20}$/', $ff_uid)) {
    http_response_code(422);
    log_api_call($auth_account['id'], 422, $raw);
    echo json_encode(['status' => 'error', 'code' => 'INVALID_UID', 'message' => 'A valid numeric Free Fire UID is required.']);
    exit;
}

// Fetch product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    log_api_call($auth_account['id'], 404, $raw);
    echo json_encode(['status' => 'error', 'code' => 'PRODUCT_NOT_FOUND', 'message' => 'Product is unavailable or inactive.']);
    exit;
}

// Fetch variant if provided
$variant = null;
if ($variant_id > 0) {
    $stmt_var = $pdo->prepare("SELECT * FROM product_variants WHERE id = ? AND product_id = ? AND status = 'active'");
    $stmt_var->execute([$variant_id, $product_id]);
    $variant = $stmt_var->fetch();
}

// Calculate server-side reseller price
$base_price = (float)($variant ? $variant['price'] : $product['price']);
$reseller_price = get_reseller_product_price($product_id, $variant ? $variant['id'] : null, $auth_account['reseller_level'] ?: 'main') ?: $base_price;
$diamonds_amount = (int)($variant ? ($variant['diamonds_amount'] + $variant['bonus_diamonds']) : ($product['diamonds_amount'] + $product['bonus_diamonds']));

// Transaction-safe balance check & debit
$pdo->beginTransaction();
try {
    $stmt_user = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE");
    $stmt_user->execute([$auth_account['user_id']]);
    $current_balance = (float)$stmt_user->fetchColumn();

    if ($current_balance < $reseller_price) {
        $pdo->rollBack();
        http_response_code(402);
        log_api_call($auth_account['id'], 402, $raw);
        echo json_encode([
            'status' => 'error',
            'code' => 'INSUFFICIENT_FUNDS',
            'message' => 'Insufficient wallet balance. Balance: ' . $current_balance . ', Required: ' . $reseller_price
        ]);
        exit;
    }

    $new_balance = round($current_balance - $reseller_price, 2);
    $order_number = generate_order_id();
    $payment_id = generate_payment_id();
    $wallet_txn_id = generate_transaction_id();

    // 1. Deduct wallet
    $pdo->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?")->execute([$new_balance, $auth_account['user_id']]);

    // 2. Insert wallet transaction
    $stmt_w = $pdo->prepare("
        INSERT INTO wallet_transactions (transaction_id, user_id, type, amount, previous_balance, new_balance, payment_method, reference_no, status, notes) 
        VALUES (?, ?, 'debit', ?, ?, ?, 'reseller_api', ?, 'completed', ?)
    ");
    $stmt_w->execute([$wallet_txn_id, $auth_account['user_id'], $reseller_price, $current_balance, $new_balance, $order_number, "API Order #" . $order_number]);
    $w_row_id = $pdo->lastInsertId();

    // 3. Insert payment
    $stmt_p = $pdo->prepare("
        INSERT INTO payments (payment_id, user_id, wallet_transaction_id, gateway_code, amount, currency, status, gateway_txn_id) 
        VALUES (?, ?, ?, 'reseller_api', ?, 'INR', 'completed', ?)
    ");
    $stmt_p->execute([$payment_id, $auth_account['user_id'], $w_row_id, $reseller_price, $wallet_txn_id]);
    $pay_row_id = $pdo->lastInsertId();

    // 4. Insert order
    $stmt_ord = $pdo->prepare("
        INSERT INTO orders 
        (order_number, user_id, variant_id, variant_name, original_amount, total_amount, payment_method, payment_status, order_status, ff_uid, ff_nickname, ff_region, payment_reference) 
        VALUES (?, ?, ?, ?, ?, ?, 'reseller_api', 'paid', 'processing', ?, ?, ?, ?)
    ");
    $stmt_ord->execute([
        $order_number,
        $auth_account['user_id'],
        $variant ? $variant['id'] : null,
        $variant ? $variant['name'] : 'Standard',
        $base_price,
        $reseller_price,
        $ff_uid,
        $ff_nickname,
        $ff_region,
        $payment_id
    ]);
    $order_id = $pdo->lastInsertId();

    $pdo->prepare("UPDATE payments SET order_id = ? WHERE id = ?")->execute([$order_id, $pay_row_id]);

    // 5. Insert order item
    $item_title = $product['name'] . ($variant ? ' (' . $variant['name'] . ')' : '');
    $stmt_item = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, product_name, price, quantity, diamonds_amount) 
        VALUES (?, ?, ?, ?, 1, ?)
    ");
    $stmt_item->execute([$order_id, $product['id'], $item_title, $reseller_price, $diamonds_amount]);

    // 6. Provider Dispatch Architecture
    $stmt_prv = $pdo->query("SELECT * FROM providers WHERE status = 'active' ORDER BY id ASC LIMIT 1");
    $active_prv = $stmt_prv->fetch();
    $provider_ref = 'PRV-' . strtoupper(substr(uniqid(), -8));
    if ($active_prv) {
        $pdo->prepare("UPDATE orders SET provider_id = ?, provider_order_id = ?, order_status = 'completed' WHERE id = ?")
            ->execute([$active_prv['id'], $provider_ref, $order_id]);
        $final_order_status = 'completed';
    } else {
        $final_order_status = 'processing';
    }

    $pdo->commit();

    log_api_call($auth_account['id'], 200, $raw);

    echo json_encode([
        'status' => 'success',
        'order_number' => $order_number,
        'order_status' => $final_order_status,
        'ff_uid' => $ff_uid,
        'diamonds_amount' => $diamonds_amount,
        'charged_amount' => $reseller_price,
        'remaining_balance' => $new_balance,
        'currency' => defined('CURRENCY_CODE') ? CURRENCY_CODE : 'INR'
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    log_api_call($auth_account['id'], 500, $raw);
    echo json_encode([
        'status' => 'error',
        'code' => 'SERVER_ERROR',
        'message' => 'Failed to process order: ' . $e->getMessage()
    ]);
}
