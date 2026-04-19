<?php
require_once(__DIR__ . '/includes/bootstrap.php');
require_admin();

$pageTitle = 'Tài chính';
$activeMenu = 'finance';
$pageHeading = 'Tài chính';
$pageDescription = 'Tổng hợp nhanh giá trị các tin đăng hiện có để người quản lý theo dõi.';

$totalSaleValue = (float) get_single_value($conn, "SELECT COALESCE(SUM(price), 0) FROM post WHERE type = 'Chuyển nhượng' AND status = 1");
$totalRentValue = (float) get_single_value($conn, "SELECT COALESCE(SUM(price), 0) FROM post WHERE type = 'Cho thuê' AND status = 1");
$avgSaleValue = (float) get_single_value($conn, "SELECT COALESCE(AVG(price), 0) FROM post WHERE type = 'Chuyển nhượng'");
$avgRentValue = (float) get_single_value($conn, "SELECT COALESCE(AVG(price), 0) FROM post WHERE type = 'Cho thuê'");
$vipCount = (int) get_single_value($conn, 'SELECT COUNT(*) FROM post WHERE is_vip = 1');

include(__DIR__ . '/includes/header.php');
?>
<div class="stats-grid four">
    <article class="stat-card">
        <div class="stat-label">Tổng giá trị tin bán</div>
        <div class="stat-value small"><?php echo h(format_money($totalSaleValue)); ?></div>
        <div class="stat-meta">Cộng dồn các tin chuyển nhượng đang hiển thị</div>
    </article>
    <article class="stat-card">
        <div class="stat-label">Tổng giá thuê</div>
        <div class="stat-value small"><?php echo h(format_money($totalRentValue)); ?></div>
        <div class="stat-meta">Cộng dồn các tin cho thuê đang hiển thị</div>
    </article>
    <article class="stat-card">
        <div class="stat-label">Giá bán trung bình</div>
        <div class="stat-value small"><?php echo h(format_money($avgSaleValue)); ?></div>
        <div class="stat-meta">Tham chiếu mặt bằng giá hiện tại</div>
    </article>
    <article class="stat-card">
        <div class="stat-label">Số tin VIP</div>
        <div class="stat-value"><?php echo $vipCount; ?></div>
        <div class="stat-meta">Nguồn tin được ưu tiên hiển thị</div>
    </article>
</div>

<section class="panel-card">
    <div class="panel-head solo">
        <div>
            <h3>Tóm tắt tài chính theo tin đăng</h3>
            <p>Dữ liệu bên dưới được tổng hợp trực tiếp từ các tin đang có trên hệ thống.</p>
        </div>
    </div>

    <div class="detail-list">
        <div class="detail-row"><span>Tổng giá trị tin chuyển nhượng đang hiển thị</span><strong><?php echo h(format_money($totalSaleValue)); ?></strong></div>
        <div class="detail-row"><span>Tổng giá trị tin cho thuê đang hiển thị</span><strong><?php echo h(format_money($totalRentValue)); ?></strong></div>
        <div class="detail-row"><span>Giá bán trung bình</span><strong><?php echo h(format_money($avgSaleValue)); ?></strong></div>
        <div class="detail-row"><span>Giá thuê trung bình</span><strong><?php echo h(format_money($avgRentValue)); ?></strong></div>
        <div class="detail-row"><span>Số tin VIP hiện có</span><strong><?php echo $vipCount; ?></strong></div>
    </div>
</section>
<?php include(__DIR__ . '/includes/footer.php'); ?>
