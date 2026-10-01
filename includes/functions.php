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

function format_currency($amount) {
    $symbol = get_setting('currency_symbol', '$');
    return $symbol . number_format((float)$amount, 2);
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

// Clean Input
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return trim((string)$input);
}
