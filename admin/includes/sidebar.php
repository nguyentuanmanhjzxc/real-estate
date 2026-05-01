<?php $admin = current_admin(); ?>
<aside class="admin-sidebar">
    <div class="brand-block">
        <div class="brand-icon">TA</div>
        <div>
            <div class="brand-name">T.ADMIN</div>
            <div class="brand-sub">Khu quản trị bất động sản</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a class="nav-item <?php echo is_active_menu('dashboard', $activeMenu ?? ''); ?>" href="dashboard.php">
            <span>Tổng quan</span>
        </a>
        <a class="nav-item <?php echo is_active_menu('posts', $activeMenu ?? ''); ?>" href="apartments.php">
            <span>Quản lý tin đăng</span>
        </a>
        <a class="nav-item <?php echo is_active_menu('requests', $activeMenu ?? ''); ?>" href="requests.php">
            <span>Yêu cầu liên hệ</span>
        </a>
        <a class="nav-item <?php echo is_active_menu('projects', $activeMenu ?? ''); ?>" href="projects.php">
            <span>Quản lý dự án</span>
        </a>
        <a class="nav-item <?php echo is_active_menu('customers', $activeMenu ?? ''); ?>" href="customers.php">
            <span>Khách hàng</span>
        </a>
        <a class="nav-item <?php echo is_active_menu('images', $activeMenu ?? ''); ?>" href="images.php">
            <span>Quản lý hình ảnh</span>
        </a>
        <a class="nav-item <?php echo is_active_menu('finance', $activeMenu ?? ''); ?>" href="finance.php">
            <span>Tài chính</span>
        </a>
        <a class="nav-item <?php echo is_active_menu('settings', $activeMenu ?? ''); ?>" href="settings.php">
            <span>Cài đặt</span>
        </a>
    </nav>

    <div class="sidebar-user">
        <div class="avatar-circle">
            <?php echo strtoupper(substr($admin['name'] ?? 'A', 0, 1)); ?>
        </div>
        <div>
            <div class="user-name"><?php echo h($admin['name'] ?? 'Admin'); ?></div>
            <div class="user-role">Quản trị viên hệ thống</div>
        </div>
    </div>
</aside>
