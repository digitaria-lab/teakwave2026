<div class="sidebar-overlay"></div>
<aside class="sidebar">
    <button class="sidebar-mobile-close d-lg-none" type="button" aria-label="Close sidebar">
        <i class="bi bi-x-lg"></i>
    </button>

    <a href="index.php" class="sidebar-brand">
        <span class="sidebar-brand-logo">
            <i class="bi bi-lightning-charge-fill"></i>
        </span>
        <span class="sidebar-brand-text">
            <?php echo e(function_exists('get_website_setting') ? get_website_setting('site_name', 'Digitaria') : 'Digitaria'); ?>
        </span>
    </a>

    <a href="content-add.php" class="new-project-btn">
        <i class="bi bi-plus-lg"></i>
        <span>New Content</span>
    </a>

    <nav class="side-menu">
        <?php if(has_permission('dashboard')): ?>
        <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>
        <?php endif; ?>

        <?php if(has_permission('contents-list') || has_permission('contents-add') || has_permission('contents-edit')): ?>
        <div class="menu-group <?php echo in_array(basename($_SERVER['PHP_SELF']), ['contents.php','content-add.php','content-edit.php']) ? 'open' : ''; ?>">
            <button class="menu-parent" type="button">
                <span><i class="bi bi-file-earmark-text-fill"></i> Content</span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="submenu">
                <?php if(has_permission('contents-list')): ?>
                <a href="contents.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'contents.php' ? 'active' : ''; ?>">
                    <i class="bi bi-list-ul"></i> List Content
                </a>
                <?php endif; ?>

                <?php if(has_permission('contents-add')): ?>
                <a href="content-add.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'content-add.php' ? 'active' : ''; ?>">
                    <i class="bi bi-plus-circle-fill"></i> Add Content
                </a>
                <?php endif; ?>

                <?php if(has_permission('contents-edit')): ?>
                <a href="content-edit.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'content-edit.php' ? 'active' : ''; ?>">
                    <i class="bi bi-pencil-square"></i> Edit Content
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if(has_permission('videos-list') || has_permission('videos-add') || has_permission('videos-edit')): ?>
        <div class="menu-group <?php echo in_array(basename($_SERVER['PHP_SELF']), ['videos.php','video-add.php','video-edit.php']) ? 'open' : ''; ?>">
            <button class="menu-parent" type="button">
                <span><i class="bi bi-play-btn-fill"></i> Video</span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="submenu">
                <?php if(has_permission('videos-list')): ?>
                <a href="videos.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'videos.php' ? 'active' : ''; ?>">
                    <i class="bi bi-list-ul"></i> List Video
                </a>
                <?php endif; ?>

                <?php if(has_permission('videos-add')): ?>
                <a href="video-add.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'video-add.php' ? 'active' : ''; ?>">
                    <i class="bi bi-plus-circle-fill"></i> Add Video
                </a>
                <?php endif; ?>

                <?php if(has_permission('videos-edit')): ?>
                <a href="video-edit.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'video-edit.php' ? 'active' : ''; ?>">
                    <i class="bi bi-pencil-square"></i> Edit Video
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if(has_permission('products-list') || has_permission('products-add') || has_permission('products-edit')): ?>
        <div class="menu-group <?php echo in_array(basename($_SERVER['PHP_SELF']), ['products.php','product-add.php','product-edit.php']) ? 'open' : ''; ?>">
            <button class="menu-parent" type="button">
                <span><i class="bi bi-box-seam-fill"></i> Products</span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="submenu">
                <?php if(has_permission('products-list')): ?>
                <a href="products.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'products.php' ? 'active' : ''; ?>">
                    <i class="bi bi-list-ul"></i> List Product
                </a>
                <?php endif; ?>

                <?php if(has_permission('products-add')): ?>
                <a href="product-add.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'product-add.php' ? 'active' : ''; ?>">
                    <i class="bi bi-plus-circle-fill"></i> Add Product
                </a>
                <?php endif; ?>

                <?php if(has_permission('products-edit')): ?>
                <a href="product-edit.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'product-edit.php' ? 'active' : ''; ?>">
                    <i class="bi bi-pencil-square"></i> Edit Product
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if(has_permission('brands')): ?>
        <a href="brands.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'brands.php' ? 'active' : ''; ?>">
            <i class="bi bi-patch-check-fill"></i> Brands
        </a>
        <?php endif; ?>

        <?php if(has_permission('categories')): ?>
        <a href="categories.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : ''; ?>">
            <i class="bi bi-tags-fill"></i> Categories
        </a>
        <?php endif; ?>

        <?php if(has_permission('users')): ?>
        <a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>">
            <i class="bi bi-people-fill"></i> User Management
        </a>
        <?php endif; ?>

        <?php if(has_permission('roles')): ?>
        <a href="roles.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'roles.php' ? 'active' : ''; ?>">
            <i class="bi bi-shield-lock-fill"></i> User Level
        </a>
        <?php endif; ?>

        <?php if(has_permission('files')): ?>
        <a href="files.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'files.php' ? 'active' : ''; ?>">
            <i class="bi bi-image-fill"></i> Files / Images
        </a>
        <?php endif; ?>

        <?php if(has_permission('banners')): ?>
        <a href="banners.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'banners.php' ? 'active' : ''; ?>">
            <i class="bi bi-badge-ad-fill"></i> Banner Ads
        </a>
        <?php endif; ?>

        <?php if(has_permission('website-settings')): ?>
        <a href="website-settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'website-settings.php' ? 'active' : ''; ?>">
            <i class="bi bi-gear-fill"></i> Website Settings
        </a>
        <?php endif; ?>

        <?php if(has_permission('logs')): ?>
        <a href="logs.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'logs.php' ? 'active' : ''; ?>">
            <i class="bi bi-clock-history"></i> Activity Logs
        </a>
        <?php endif; ?>

        <?php if(has_permission('backup-restore')): ?>
        <a href="backup-restore.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'backup-restore.php' ? 'active' : ''; ?>">
            <i class="bi bi-database-fill-gear"></i> Backup & Restore
        </a>
        <?php endif; ?>
    </nav>

    <a href="logout.php" class="logout-btn"><i class="bi bi-box-arrow-right"></i> Logout</a>
</aside>
