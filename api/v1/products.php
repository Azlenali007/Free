<?php
require_once __DIR__ . '/auth.php';

log_api_call($auth_account['id'], 200);

$stmt = $pdo->query("
    SELECT p.*, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.status = 'active'
    ORDER BY p.diamonds_amount ASC, p.price ASC
");
$products = $stmt->fetchAll();

$output = [];
foreach ($products as $p) {
    $reseller_pr = get_reseller_product_price($p['id'], null, $auth_account['reseller_level'] ?: 'main') ?: (float)$p['price'];

    // Variants
    $stmt_var = $pdo->prepare("SELECT id, name, price, diamonds_amount, bonus_diamonds FROM product_variants WHERE product_id = ? AND status = 'active' ORDER BY sort_order ASC");
    $stmt_var->execute([$p['id']]);
    $vars = $stmt_var->fetchAll();

    $var_list = [];
    foreach ($vars as $v) {
        $var_reseller_pr = get_reseller_product_price($p['id'], $v['id'], $auth_account['reseller_level'] ?: 'main') ?: (float)$v['price'];
        $var_list[] = [
            'id' => (int)$v['id'],
            'name' => $v['name'],
            'diamonds_amount' => (int)$v['diamonds_amount'] + (int)$v['bonus_diamonds'],
            'standard_price' => (float)$v['price'],
            'reseller_price' => (float)$var_reseller_pr
        ];
    }

    $output[] = [
        'id' => (int)$p['id'],
        'name' => $p['name'],
        'category' => $p['category_name'],
        'diamonds_amount' => (int)$p['diamonds_amount'] + (int)$p['bonus_diamonds'],
        'standard_price' => (float)$p['price'],
        'reseller_price' => (float)$reseller_pr,
        'variants' => $var_list
    ];
}

echo json_encode([
    'status' => 'success',
    'total_products' => count($output),
    'currency' => 'USD',
    'data' => $output
], JSON_PRETTY_PRINT);
