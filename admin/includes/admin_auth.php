<?php
require_once __DIR__ . '/../../includes/functions.php';

// Strict admin session validation
if (!is_admin_logged_in()) {
    header("Location: /admin/login.php");
    exit;
}

$admin = current_admin();
if (!$admin) {
    unset($_SESSION['admin_id']);
    header("Location: /admin/login.php");
    exit;
}
