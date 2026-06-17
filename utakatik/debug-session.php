<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

secure_session_start();

header('Content-Type: text/plain');

echo "Session name: " . session_name() . PHP_EOL;
echo "Session ID: " . session_id() . PHP_EOL;
echo "App URL login: " . app_url('login.php') . PHP_EOL;
echo "Script name: " . ($_SERVER['SCRIPT_NAME'] ?? '') . PHP_EOL;
echo "Session data:" . PHP_EOL;
print_r($_SESSION);
echo "Cookies:" . PHP_EOL;
print_r($_COOKIE);
?>
