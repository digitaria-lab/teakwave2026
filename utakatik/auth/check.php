<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
secure_session_start();
send_security_headers();
require_login();
require_permission();
?>
