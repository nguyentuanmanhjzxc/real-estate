<?php
require_once(__DIR__ . '/includes/bootstrap.php');
require_admin();

$pageTitle = 'Cài đặt';
$activeMenu = 'settings';
$pageHeading = 'Cài đặt hệ thống';
$pageDescription = 'Thông tin tài khoản quản trị đang đăng nhập.';

$admin = current_admin();
$adminId = (int) ($admin['id'] ?? 0);
$stmt = $conn->prepare('SELECT u.id, u.name, u.email, u.created_at, r.name AS role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = ? LIMIT 1');
$stmt->bind_param('i', $adminId);
$stmt->execute();
$adminInfo = $stmt->get_result()->fetch_assoc();
$stmt->close();

include(__DIR__ . '/includes/header.php');
?>
<section class="panel-card">
    <div class="panel-head solo">
        <div>
            <h3>Thông tin tài khoản quản trị</h3>
            <p>Tài khoản này đang được dùng để truy cập khu vực admin.</p>
        </div>
    </div>

    <div class="detail-list">
        <div class="detail-row"><span>Họ tên</span><strong><?php echo h($adminInfo['name'] ?? 'Admin'); ?></strong></div>
        <div class="detail-row"><span>Email</span><strong><?php echo h($adminInfo['email'] ?? 'Chưa có'); ?></strong></div>
        <div class="detail-row"><span>Vai trò</span><strong><?php echo h($adminInfo['role_name'] ?? 'Admin'); ?></strong></div>
        <div class="detail-row"><span>Ngày tạo</span><strong><?php echo !empty($adminInfo['created_at']) ? h(date('d/m/Y H:i', strtotime($adminInfo['created_at']))) : 'N/A'; ?></strong></div>
    </div>
</section>
<?php include(__DIR__ . '/includes/footer.php'); ?>
