<?php
require_once __DIR__ . '/../includes/functions.php';

unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);

header("Location: /admin/login.php");
exit;
