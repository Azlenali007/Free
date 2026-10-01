<?php
// Reseller API Authentication & Rate Limiting Middleware
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// Read headers
$headers = getallheaders();
$api_key = $headers['X-API-Key'] ?? $headers['x-api-key'] ?? '';
$api_secret = $headers['X-API-Secret'] ?? $headers['x-api-secret'] ?? '';

if (empty($api_key) || empty($api_secret)) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'code' => 'MISSING_API_CREDENTIALS',
        'message' => 'Please provide X-API-Key and X-API-Secret HTTP headers.'
    ]);
    exit;
}

// Authenticate key in MySQL
$stmt = $pdo->prepare("
    SELECT k.*, u.id as user_id, u.username, u.wallet_balance, u.is_reseller, u.reseller_level, u.status as user_status
    FROM api_keys k
    JOIN users u ON k.user_id = u.id
    WHERE k.api_key = ? AND k.api_secret = ? AND k.status = 'active'
");
$stmt->execute([$api_key, $api_secret]);
$auth_account = $stmt->fetch();

if (!$auth_account || $auth_account['user_status'] === 'banned') {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'code' => 'INVALID_API_KEY',
        'message' => 'The provided API Key or Secret is invalid or inactive.'
    ]);
    exit;
}

// Update last used timestamp
$pdo->prepare("UPDATE api_keys SET last_used_at = NOW() WHERE id = ?")->execute([$auth_account['id']]);

// Rate limit per minute
$stmt_rate = $pdo->prepare("
    SELECT COUNT(*) FROM api_logs 
    WHERE api_key_id = ? AND created_at >= NOW() - INTERVAL 1 MINUTE
");
$stmt_rate->execute([$auth_account['id']]);
$req_count = (int)$stmt_rate->fetchColumn();

if ($req_count >= (int)$auth_account['rate_limit_per_minute']) {
    http_response_code(429);
    echo json_encode([
        'status' => 'error',
        'code' => 'RATE_LIMIT_EXCEEDED',
        'message' => 'API rate limit exceeded. Max ' . $auth_account['rate_limit_per_minute'] . ' requests per minute.'
    ]);
    exit;
}

// Function to log API call
function log_api_call($auth_id, $code = 200, $body = null) {
    global $pdo;
    try {
        $endpoint = $_SERVER['REQUEST_URI'] ?? '/api';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $ip = get_client_ip();
        $stmt = $pdo->prepare("INSERT INTO api_logs (api_key_id, endpoint, request_method, ip_address, request_body, response_code) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$auth_id, $endpoint, $method, $ip, is_array($body) ? json_encode($body) : (string)$body, $code]);
    } catch (Exception $e) {
        // Silence log error
    }
}
