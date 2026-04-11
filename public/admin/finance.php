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
    <div class="panel-head">
        <div>
            <h3>Gợi ý mở rộng nghiệp vụ</h3>
            <p>Hiện cơ sở dữ liệu chưa có bảng giao dịch hoặc hợp đồng hoàn tất, nên trang này đang tổng hợp theo giá trị tin đăng.</p>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <h4>Muốn tính doanh thu thật</h4>
            <p>Bạn nên bổ sung bảng giao dịch hoặc hợp đồng để lưu số tiền chốt thực tế, phí môi giới, ngày thanh toán và trạng thái.</p>
        </div>
        <div class="info-box">
            <h4>Muốn quản lý hoa hồng</h4>
            <p>Có thể thêm cột hoa_hong hoặc bảng commissions gắn với từng môi giới, từng tin và từng giao dịch.</p>
        </div>
        <div class="info-box">
            <h4>Muốn theo dõi lịch thu chi</h4>
            <p>Thêm bảng payments hoặc transactions để tạo dashboard tài chính sát nghiệp vụ hơn.</p>
        </div>
    </div>
</section>
<?php include(__DIR__ . '/includes/footer.php'); ?>
