<?php
require_once __DIR__ . '/includes/functions.php';

// Unset user session
unset($_SESSION['user_id']);
unset($_SESSION['username']);

set_flash('info', 'You have been logged out successfully.');
header("Location: /login.php");
exit;
