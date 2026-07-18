<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
secure_session_start();
send_security_headers();
require_login();

$currentAdminPage = basename($_SERVER['PHP_SELF'] ?? '');
if (in_array($currentAdminPage, ['videos.php', 'video-add.php', 'video-edit.php'], true)) {
    ensure_video_schema();
}
if (in_array($currentAdminPage, ['index.php', 'page-views.php'], true)) {
    ensure_page_view_schema();
}

require_permission();
?>
