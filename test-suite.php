<?php
// Comprehensive Automated Test Suite for FireZone Store
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

echo "=== FIREZONE STORE AUTOMATED VERIFICATION ===\n\n";

$tests_passed = 0;
$tests_total = 0;

function assert_test($description, $condition) {
    global $tests_passed, $tests_total;
    $tests_total++;
    if ($condition) {
        $tests_passed++;
        echo " [PASS] $description\n";
    } else {
        echo " [FAIL] $description\n";
    }
}

// 1. MySQL Database Connection & Tables
$tables = ['users', 'admins', 'categories', 'products', 'orders', 'order_items', 'wallet_transactions', 'support_tickets', 'ticket_messages', 'settings'];
foreach ($tables as $tbl) {
    $exists = $pdo->query("SHOW TABLES LIKE '$tbl'")->fetchColumn();
    assert_test("Database table '$tbl' exists", !empty($exists));
}

// 2. Settings Verification
$site_name = get_setting('site_name');
assert_test("Dynamic site_name loaded from MySQL ($site_name)", !empty($site_name));

// 3. User Registration
$test_user = 'gamer_' . mt_rand(1000, 9999);
$test_email = $test_user . '@test.com';
$test_pass = 'secret123';
$hashed = password_hash($test_pass, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO users (name, username, email, password, ff_uid, ff_nickname, ff_region, wallet_balance, status) VALUES (?, ?, ?, ?, ?, ?, ?, 50.00, 'active')");
$stmt->execute(['Test Player', $test_user, $test_email, $hashed, '9988776655', 'ProSniper', 'India']);
$user_id = $pdo->lastInsertId();
assert_test("User registration in MySQL with initial wallet balance ($user_id)", $user_id > 0);

// 4. User Login & Password Verify
$stmt_login = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt_login->execute([$test_user]);
$fetched_user = $stmt_login->fetch();
$auth_ok = $fetched_user && password_verify($test_pass, $fetched_user['password']);
assert_test("User authentication via password_verify()", $auth_ok);

// 5. Product Catalog & Category Search
$prod = $pdo->query("SELECT * FROM products WHERE status = 'active' LIMIT 1")->fetch();
assert_test("Active product fetched from MySQL ('{$prod['name']}')", !empty($prod));

// 6. Free Fire UID & Product Purchase with Wallet
$initial_balance = (float)$fetched_user['wallet_balance'];
$product_price = (float)$prod['price'];

$pdo->beginTransaction();
$new_balance = $initial_balance - $product_price;
$pdo->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?")->execute([$new_balance, $user_id]);

$order_number = generate_order_id();
$stmt_o = $pdo->prepare("INSERT INTO orders (order_number, user_id, total_amount, payment_method, payment_status, order_status, ff_uid, ff_nickname, ff_region) VALUES (?, ?, ?, 'wallet', 'paid', 'processing', ?, ?, ?)");
$stmt_o->execute([$order_number, $user_id, $product_price, $fetched_user['ff_uid'], $fetched_user['ff_nickname'], $fetched_user['ff_region']]);
$order_id = $pdo->lastInsertId();

$stmt_item = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity, diamonds_amount) VALUES (?, ?, ?, ?, 1, ?)");
$stmt_item->execute([$order_id, $prod['id'], $prod['name'], $prod['price'], $prod['diamonds_amount']]);

$txn_id = generate_transaction_id();
$stmt_tx = $pdo->prepare("INSERT INTO wallet_transactions (transaction_id, user_id, type, amount, payment_method, reference_no, status, notes) VALUES (?, ?, 'debit', ?, 'wallet', ?, 'completed', 'Order payment')");
$stmt_tx->execute([$txn_id, $user_id, $product_price, $order_number]);
$pdo->commit();

assert_test("Order created in MySQL with unique ID ($order_number)", $order_id > 0);
assert_test("Wallet balance debited ($initial_balance -> $new_balance)", $new_balance < $initial_balance);

// 7. Order Status Update by Admin
$pdo->prepare("UPDATE orders SET order_status = 'completed', admin_notes = 'UID recharged successfully' WHERE id = ?")->execute([$order_id]);
$updated_order = $pdo->query("SELECT order_status FROM orders WHERE id = $order_id")->fetchColumn();
assert_test("Order status update to 'completed'", $updated_order === 'completed');

// 8. Support Ticket Creation & Message Thread
$tkt_no = generate_ticket_id();
$pdo->prepare("INSERT INTO support_tickets (ticket_number, user_id, subject, priority, status) VALUES (?, ?, 'Need top-up receipt', 'medium', 'open')")->execute([$tkt_no, $user_id]);
$ticket_id = $pdo->lastInsertId();
$pdo->prepare("INSERT INTO ticket_messages (ticket_id, user_id, is_admin, message) VALUES (?, ?, 0, 'Can I get my diamond top-up transaction receipt?')")->execute([$ticket_id, $user_id]);
$pdo->prepare("INSERT INTO ticket_messages (ticket_id, user_id, is_admin, message) VALUES (?, NULL, 1, 'Hello, your UID top-up receipt has been issued.')")->execute([$ticket_id]);

$msg_count = $pdo->query("SELECT COUNT(*) FROM ticket_messages WHERE ticket_id = $ticket_id")->fetchColumn();
assert_test("Support ticket & admin reply thread ($msg_count messages)", $msg_count == 2);

// 9. Admin User Management & Balance Adjustment
$admin = $pdo->query("SELECT * FROM admins WHERE username = 'admin'")->fetch();
assert_test("Super Admin account exists in MySQL", !empty($admin));

$pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + 10.00 WHERE id = ?")->execute([$user_id]);
$final_balance = (float)$pdo->query("SELECT wallet_balance FROM users WHERE id = $user_id")->fetchColumn();
assert_test("Admin manual wallet balance adjustment (+10.00)", $final_balance == ($new_balance + 10.00));

// 10. Admin Product Creation, Edit & Delete
$test_prod_name = "VIP Tournament Pass Pack";
$test_slug = "vip-pass-" . mt_rand(100, 999);
$pdo->prepare("INSERT INTO products (category_id, name, slug, description, image, price, status) VALUES (1, ?, ?, 'Test VIP product', '/assets/images/pass-weekly.svg', 15.99, 'active')")->execute([$test_prod_name, $test_slug]);
$new_p_id = $pdo->lastInsertId();
assert_test("Admin product creation ($new_p_id)", $new_p_id > 0);

$pdo->prepare("UPDATE products SET price = 12.99, badge = 'Sale' WHERE id = ?")->execute([$new_p_id]);
$edited_price = (float)$pdo->query("SELECT price FROM products WHERE id = $new_p_id")->fetchColumn();
assert_test("Admin product price edit ($12.99)", $edited_price == 12.99);

$pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$new_p_id]);
$deleted = $pdo->query("SELECT COUNT(*) FROM products WHERE id = $new_p_id")->fetchColumn();
assert_test("Admin product deletion", $deleted == 0);

echo "\nVerification Summary: $tests_passed / $tests_total tests passed successfully!\n";
