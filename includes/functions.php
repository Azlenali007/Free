<?php
// Core Helper Functions & Security Utilities

if (session_status() === PHP_SESSION_NONE) {
    // Secure session options
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

require_once __DIR__ . '/../config/db.php';

// Safe HTML escaping
function e($string) {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

// Global cached settings
$GLOBALS['app_settings'] = null;

function load_all_settings() {
    global $pdo;
    if ($GLOBALS['app_settings'] !== null) {
        return $GLOBALS['app_settings'];
    }
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        $GLOBALS['app_settings'] = $settings;
        return $settings;
    } catch (Exception $e) {
        return [];
    }
}

function get_setting($key, $default = '') {
    $settings = load_all_settings();
    return $settings[$key] ?? $default;
}

function update_setting($key, $value) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute([$key, $value, $value]);
    if ($GLOBALS['app_settings'] !== null) {
        $GLOBALS['app_settings'][$key] = $value;
    }
    return true;
}

// Central Global Currency Configuration - 100% INR
if (!defined('CURRENCY_CODE')) {
    define('CURRENCY_CODE', 'INR');
}
if (!defined('CURRENCY_SYMBOL')) {
    define('CURRENCY_SYMBOL', '₹');
}
if (!defined('CURRENCY_NAME')) {
    define('CURRENCY_NAME', 'Indian Rupee');
}

function get_currency_code() {
    return CURRENCY_CODE;
}

function get_currency_symbol() {
    return CURRENCY_SYMBOL;
}

function get_currency_name() {
    return CURRENCY_NAME;
}

function format_currency($amount, $show_code = false) {
    $formatted = CURRENCY_SYMBOL . number_format((float)$amount, 2);
    if ($show_code) {
        $formatted .= ' ' . CURRENCY_CODE;
    }
    return $formatted;
}

// CSRF Protection
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

function csrf_validate() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            die("<div style='background:#111;color:#ff3333;font-family:sans-serif;padding:30px;text-align:center;'>
                <h2>403 - CSRF Token Invalid or Expired</h2>
                <p>Please refresh the page and try again.</p>
                <p><a href='javascript:history.back()' style='color:#fff;background:#dc2626;padding:8px 16px;text-decoration:none;border-radius:4px;'>Go Back</a></p>
            </div>");
        }
    }
}

// Flash Messages
function set_flash($type, $message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = ['type' => $type, 'message' => $message];
}

function get_flash() {
    if (empty($_SESSION['flash_messages'])) {
        return [];
    }
    $msgs = $_SESSION['flash_messages'];
    unset($_SESSION['flash_messages']);
    return $msgs;
}

// Authentication Helpers
function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

function current_user() {
    global $pdo;
    if (!is_logged_in()) {
        return null;
    }
    static $user = null;
    if ($user !== null) {
        return $user;
    }
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user || $user['status'] === 'banned') {
        unset($_SESSION['user_id']);
        return null;
    }
    return $user;
}

function require_login() {
    if (!is_logged_in()) {
        $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '/dashboard.php');
        header("Location: /login.php?redirect=" . $redirect);
        exit;
    }
}

function is_admin_logged_in() {
    return !empty($_SESSION['admin_id']);
}

function current_admin() {
    global $pdo;
    if (!is_admin_logged_in()) {
        return null;
    }
    static $admin = null;
    if ($admin !== null) {
        return $admin;
    }
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();
    return $admin;
}

function require_admin() {
    if (!is_admin_logged_in()) {
        header("Location: /admin/login.php");
        exit;
    }
}

// ID Generators
function generate_order_id() {
    return 'FF-' . strtoupper(substr(uniqid(), -6)) . '-' . mt_rand(100, 999);
}

function generate_ticket_id() {
    return 'TKT-' . date('ym') . '-' . mt_rand(1000, 9999);
}

function generate_transaction_id() {
    return 'TXN-' . strtoupper(bin2hex(random_bytes(4)));
}

function generate_payment_id() {
    return 'PAY-' . strtoupper(bin2hex(random_bytes(5)));
}

function generate_refund_id() {
    return 'RFD-' . date('ym') . '-' . mt_rand(1000, 9999);
}

// Clean Input
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return trim((string)$input);
}

// Get Client IP safely
function get_client_ip() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

// Logging Helpers
function log_admin_activity($action, $details = null) {
    global $pdo;
    try {
        $admin = current_admin();
        $admin_id = $admin['id'] ?? null;
        $admin_username = $admin['username'] ?? 'System';
        $ip = get_client_ip();
        
        $stmt = $pdo->prepare("INSERT INTO admin_activity_logs (admin_id, admin_username, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$admin_id, $admin_username, $action, is_array($details) ? json_encode($details) : (string)$details, $ip]);
    } catch (Exception $e) {
        // Silently prevent logging failures from breaking transactions
    }
}

function log_security_event($event_type, $details = null, $severity = 'info', $user_id = null) {
    global $pdo;
    try {
        $ip = get_client_ip();
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        if ($user_id === null && is_logged_in()) {
            $user_id = $_SESSION['user_id'];
        }
        $stmt = $pdo->prepare("INSERT INTO security_logs (event_type, ip_address, user_agent, user_id, details, severity) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$event_type, $ip, $ua, $user_id, is_array($details) ? json_encode($details) : (string)$details, $severity]);
    } catch (Exception $e) {
        // Silence log error
    }
}

// User In-Site Notifications
function create_notification($user_id, $title, $message, $type = 'order', $link = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $message, $type, $link]);
    } catch (Exception $e) {
        // Silence
    }
}

function get_unread_notifications_count($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0");
        $stmt->execute([$user_id]);
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

// Brute Force & Login Protection
function is_login_locked($identifier) {
    global $pdo;
    $ip = get_client_ip();
    $stmt = $pdo->prepare("SELECT attempts, locked_until FROM login_attempts WHERE identifier = ? AND ip_address = ?");
    $stmt->execute([$identifier, $ip]);
    $row = $stmt->fetch();
    if ($row && !empty($row['locked_until'])) {
        if (strtotime($row['locked_until']) > time()) {
            $remaining = ceil((strtotime($row['locked_until']) - time()) / 60);
            return $remaining;
        }
    }
    return false;
}

function record_failed_login($identifier) {
    global $pdo;
    $ip = get_client_ip();
    $max_attempts = (int)get_setting('max_login_attempts', 5);
    $lock_mins = (int)get_setting('lockout_duration_minutes', 15);
    
    $stmt = $pdo->prepare("SELECT attempts FROM login_attempts WHERE identifier = ? AND ip_address = ?");
    $stmt->execute([$identifier, $ip]);
    $row = $stmt->fetch();
    
    if ($row) {
        $new_attempts = $row['attempts'] + 1;
        $locked_until = ($new_attempts >= $max_attempts) ? date('Y-m-d H:i:s', time() + ($lock_mins * 60)) : null;
        $stmt = $pdo->prepare("UPDATE login_attempts SET attempts = ?, locked_until = ?, last_attempt = NOW() WHERE identifier = ? AND ip_address = ?");
        $stmt->execute([$new_attempts, $locked_until, $identifier, $ip]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO login_attempts (identifier, ip_address, attempts) VALUES (?, ?, 1)");
        $stmt->execute([$identifier, $ip]);
    }
    log_security_event('failed_login', "Failed attempt for identifier: {$identifier}", 'warning');
}

function clear_login_attempts($identifier) {
    global $pdo;
    $ip = get_client_ip();
    $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE identifier = ? AND ip_address = ?");
    $stmt->execute([$identifier, $ip]);
}

// Generic Rate Limiting
function check_rate_limit($action, $max_attempts = 30, $window_seconds = 60) {
    $key = 'rate_' . $action . '_' . get_client_ip();
    $now = time();
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 1, 'start' => $now];
        return true;
    }
    if ($now - $_SESSION[$key]['start'] > $window_seconds) {
        $_SESSION[$key] = ['count' => 1, 'start' => $now];
        return true;
    }
    $_SESSION[$key]['count']++;
    if ($_SESSION[$key]['count'] > $max_attempts) {
        log_security_event('rate_limit_exceeded', "Rate limit hit for action: {$action}", 'warning');
        return false;
    }
    return true;
}

// Flash Sale Server-Side Pricing
function get_effective_product_price($product) {
    $now = time();
    $is_sale = !empty($product['flash_sale_enabled']) && 
               !empty($product['flash_sale_price']) && 
               ($product['flash_sale_price'] > 0);
               
    if ($is_sale) {
        if (!empty($product['flash_sale_start']) && strtotime($product['flash_sale_start']) > $now) {
            $is_sale = false;
        }
        if (!empty($product['flash_sale_end']) && strtotime($product['flash_sale_end']) < $now) {
            $is_sale = false;
        }
    }
    
    if ($is_sale) {
        return [
            'is_sale' => true,
            'price' => (float)$product['flash_sale_price'],
            'original_price' => (float)$product['price'],
            'discount_percent' => round((($product['price'] - $product['flash_sale_price']) / $product['price']) * 100),
            'end_time' => $product['flash_sale_end'] ?? null
        ];
    }
    
    return [
        'is_sale' => false,
        'price' => (float)$product['price'],
        'original_price' => (float)($product['original_price'] ?? $product['price']),
        'discount_percent' => 0,
        'end_time' => null
    ];
}

// Server-side Coupon Validator
function validate_coupon($code, $subtotal, $user_id = null) {
    global $pdo;
    $code = strtoupper(trim((string)$code));
    if (empty($code)) {
        return ['valid' => false, 'message' => 'Please enter a coupon code.'];
    }
    
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'active'");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();
    
    if (!$coupon) {
        return ['valid' => false, 'message' => 'Invalid or expired coupon code.'];
    }
    
    $now = time();
    if (!empty($coupon['start_date']) && strtotime($coupon['start_date']) > $now) {
        return ['valid' => false, 'message' => 'Coupon is not yet active.'];
    }
    if (!empty($coupon['expiry_date']) && strtotime($coupon['expiry_date']) < $now) {
        return ['valid' => false, 'message' => 'Coupon has expired.'];
    }
    
    if ($coupon['min_order_amount'] > 0 && $subtotal < (float)$coupon['min_order_amount']) {
        return [
            'valid' => false, 
            'message' => 'Minimum order amount for this coupon is ' . format_currency($coupon['min_order_amount']) . '.'
        ];
    }
    
    if ($coupon['usage_limit'] > 0 && $coupon['used_count'] >= $coupon['usage_limit']) {
        return ['valid' => false, 'message' => 'Coupon usage limit has been reached.'];
    }
    
    if ($user_id) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM coupon_usage WHERE coupon_id = ? AND user_id = ?");
        $stmt->execute([$coupon['id'], $user_id]);
        $user_used = (int)$stmt->fetchColumn();
        if ($coupon['per_user_limit'] > 0 && $user_used >= $coupon['per_user_limit']) {
            return ['valid' => false, 'message' => 'You have already used this coupon maximum allowed times.'];
        }
    }
    
    // Calculate discount
    $discount = 0;
    if ($coupon['discount_type'] === 'percentage') {
        $discount = round(($subtotal * (float)$coupon['discount_value']) / 100, 2);
        if (!empty($coupon['max_discount']) && $coupon['max_discount'] > 0 && $discount > (float)$coupon['max_discount']) {
            $discount = (float)$coupon['max_discount'];
        }
    } else {
        $discount = (float)$coupon['discount_value'];
    }
    
    if ($discount > $subtotal) {
        $discount = $subtotal;
    }
    
    return [
        'valid' => true,
        'coupon' => $coupon,
        'discount' => $discount,
        'final_total' => max(0, $subtotal - $discount),
        'message' => 'Coupon applied! You saved ' . format_currency($discount)
    ];
}

// Transaction-Safe Wallet Balance Change
function adjust_user_wallet($user_id, $type, $amount, $payment_method, $notes = '', $reference_no = null, $admin_id = null) {
    global $pdo;
    $amount = round((float)$amount, 2);
    if ($amount <= 0) {
        return ['success' => false, 'message' => 'Invalid amount.'];
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    try {
        // Lock user row
        $stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$user_id]);
        $current_bal = $stmt->fetchColumn();
        
        if ($current_bal === false) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'User not found.'];
        }
        
        $current_bal = (float)$current_bal;
        if ($type === 'debit' && $current_bal < $amount) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Insufficient wallet balance.'];
        }
        
        $new_bal = ($type === 'credit') ? ($current_bal + $amount) : ($current_bal - $amount);
        $new_bal = max(0, round($new_bal, 2));
        
        // Update user balance
        $stmt = $pdo->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
        $stmt->execute([$new_bal, $user_id]);
        
        // Create transaction record
        $txn_id = generate_transaction_id();
        $stmt = $pdo->prepare("INSERT INTO wallet_transactions (transaction_id, user_id, type, amount, previous_balance, new_balance, payment_method, reference_no, status, notes, admin_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'completed', ?, ?)");
        $stmt->execute([$txn_id, $user_id, $type, $amount, $current_bal, $new_bal, $payment_method, $reference_no, $notes, $admin_id]);
        
        $pdo->commit();
        return [
            'success' => true,
            'transaction_id' => $txn_id,
            'previous_balance' => $current_bal,
            'new_balance' => $new_bal
        ];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

// Reseller Helpers
function is_reseller($user) {
    return !empty($user['is_reseller']);
}

function get_reseller_product_price($product_id, $variant_id = null, $level = 'main') {
    global $pdo;
    try {
        if ($variant_id) {
            $stmt = $pdo->prepare("SELECT reseller_price FROM reseller_pricing WHERE product_id = ? AND variant_id = ? AND reseller_level = ?");
            $stmt->execute([$product_id, $variant_id, $level]);
            $price = $stmt->fetchColumn();
            if ($price !== false) return (float)$price;
        }
        
        $stmt = $pdo->prepare("SELECT reseller_price FROM reseller_pricing WHERE product_id = ? AND (variant_id IS NULL OR variant_id = 0) AND reseller_level = ?");
        $stmt->execute([$product_id, $level]);
        $price = $stmt->fetchColumn();
        if ($price !== false) return (float)$price;
        
        // Fallback default discount
        $discount_pct = (float)get_setting('reseller_default_discount', 10);
        if ($variant_id) {
            $stmt = $pdo->prepare("SELECT price FROM product_variants WHERE id = ?");
            $stmt->execute([$variant_id]);
            $base_price = (float)$stmt->fetchColumn();
        } else {
            $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $base_price = (float)$stmt->fetchColumn();
        }
        return max(0, round($base_price * (1 - ($discount_pct / 100)), 2));
    } catch (Exception $e) {
        return null;
    }
}
