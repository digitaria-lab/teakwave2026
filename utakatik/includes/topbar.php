<div class="topbar">
    <div class="topbar-left">
        <button class="btn sidebar-toggle d-lg-none" type="button" aria-label="Open sidebar">
            <i class="bi bi-list"></i>
        </button>

        <button class="btn sidebar-collapse-toggle d-none d-lg-inline-flex" type="button" aria-label="Minimise sidebar">
            <i class="bi bi-list"></i>
        </button>

        <h4 class="mb-0 fw-bold"><?php echo e($page_title); ?></h4>
    </div>

    <div class="topbar-right">
        <form class="topbar-search d-none d-md-flex" method="get" action="search.php">
            <i class="bi bi-search"></i>
            <input type="text" name="q" placeholder="Search here..." value="<?php echo e($_GET['q'] ?? ''); ?>">
        </form>

        <div class="topbar-profile dropdown">
            <button class="topbar-profile-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="topbar-avatar">
                    <?php if(!empty($_SESSION['user']['avatar'])): ?>
                        <img src="<?php echo e($_SESSION['user']['avatar']); ?>" alt="<?php echo e(current_user_name()); ?>">
                    <?php else: ?>
                        <?php echo strtoupper(substr(current_user_name(), 0, 1)); ?>
                    <?php endif; ?>
                </span>
                <span class="topbar-user-info">
                    <strong><?php echo e(current_user_name()); ?></strong>
                    <small><?php echo e(current_user_role()); ?></small>
                </span>
            </button>

            <ul class="dropdown-menu dropdown-menu-end profile-dropdown">
                <li>
                    <a class="dropdown-item" href="profile.php">
                        <i class="bi bi-person-circle"></i> Edit Profile
                    </a>
                </li>
                <?php if(function_exists('has_permission') && has_permission('website-settings')): ?>
                <li>
                    <a class="dropdown-item" href="website-settings.php">
                        <i class="bi bi-gear"></i> Website Settings
                    </a>
                </li>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-danger" href="logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
