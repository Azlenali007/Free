<?php
// Database Connection via PDO with Prepared Statements
if (!file_exists(__DIR__ . '/config.php')) {
    header("Location: /install/index.php");
    exit;
}

require_once __DIR__ . '/config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // If database does not exist or connection fails, redirect to installer if not already there
    $current_script = basename($_SERVER['PHP_SELF'] ?? '');
    if ($current_script !== 'index.php' || strpos($_SERVER['REQUEST_URI'] ?? '', '/install') === false) {
        if (!file_exists(__DIR__ . '/installed.lock')) {
            header("Location: /install/index.php");
            exit;
        }
    }
    die("<div style='background:#111;color:#ff4444;font-family:sans-serif;padding:30px;text-align:center;'>
        <h2>Database Connection Error</h2>
        <p>" . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>
        <p><a href='/install/index.php' style='color:#fff;background:#dc2626;padding:8px 16px;text-decoration:none;border-radius:4px;'>Go to Installer</a></p>
    </div>");
}
