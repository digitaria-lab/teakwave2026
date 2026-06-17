<?php
// Contoh penggunaan setting website untuk halaman frontend.
// Include file ini di <head> website front jika memakai dashboard ini sebagai CMS.

$siteName = get_website_setting('site_name', 'Digitaria');
$metaTitle = get_website_setting('meta_title', $siteName);
$metaDescription = get_website_setting('meta_description', '');
$metaKeywords = get_website_setting('meta_keywords', '');
$favicon = get_website_setting('favicon', '');

?>
<title><?php echo e($metaTitle); ?></title>
<meta name="description" content="<?php echo e($metaDescription); ?>">
<meta name="keywords" content="<?php echo e($metaKeywords); ?>">
<meta name="author" content="<?php echo e($siteName); ?>">
<?php if($favicon): ?>
<link rel="icon" href="<?php echo e($favicon); ?>">
<?php endif; ?>
