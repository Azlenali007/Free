<?php
require_once __DIR__ . '/auth.php';

log_api_call($auth_account['id'], 200);

// Fetch fresh balance
$stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
$stmt->execute([$auth_account['user_id']]);
$bal = (float)$stmt->fetchColumn();

echo json_encode([
    'status' => 'success',
    'user_id' => (int)$auth_account['user_id'],
    'username' => $auth_account['username'],
    'wallet_balance' => $bal,
    'currency' => defined('CURRENCY_CODE') ? CURRENCY_CODE : 'INR',
    'reseller_level' => $auth_account['reseller_level'] ?: 'main'
], JSON_PRETTY_PRINT);
