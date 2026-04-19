<header class="admin-topbar">
    <div>
        <div class="topbar-title"><?php echo h($pageHeading ?? 'Trang quản trị'); ?></div>
        <div class="topbar-subtitle"><?php echo h($pageDescription ?? 'Theo dõi và vận hành hệ thống bất động sản.'); ?></div>
    </div>

    <div class="topbar-actions">
        <a class="ghost-btn" href="../public/index.php" target="_blank">Xem website</a>
        <a class="primary-btn" href="logout.php">Đăng xuất</a>
    </div>
</header>
