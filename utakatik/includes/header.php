<?php
$page_title = $page_title ?? 'Dashboard';
$admin_site_name = get_website_setting('site_name', 'Teakwave');
$admin_favicon = normalize_upload_storage_path(get_website_setting('favicon', ''));
$admin_favicon_url = '';
if ($admin_favicon !== '') {
    if (preg_match('#^https?://#i', $admin_favicon)) {
        $admin_favicon_url = $admin_favicon;
    } else {
        $admin_favicon_file = upload_storage_filesystem_path($admin_favicon);
        $admin_favicon_version = is_file($admin_favicon_file) ? (string) filemtime($admin_favicon_file) : (string) time();
        $admin_favicon_url = $admin_favicon . '?v=' . rawurlencode($admin_favicon_version);
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title><?php echo e($page_title); ?> - <?php echo e($admin_site_name); ?> Admin</title>
    <?php if ($admin_favicon_url !== ''): ?>
    <link rel="icon" href="<?php echo e($admin_favicon_url); ?>">
    <?php endif; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <?php if (in_array(basename($_SERVER['PHP_SELF']), ['products.php','contents.php','videos.php'])): ?>
    <link href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <?php endif; ?>
    <?php if (in_array(basename($_SERVER['PHP_SELF']), ['product-add.php','product-edit.php','content-add.php','content-edit.php'])): ?>
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
    <?php endif; ?>
    <link href="<?php echo e(admin_asset_url('assets/css/admin.css')); ?>" rel="stylesheet">
</head>
<body>
<div class="admin-shell">
