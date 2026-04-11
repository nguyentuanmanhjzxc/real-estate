<?php
require_once(__DIR__ . '/includes/bootstrap.php');
require_admin();

$pageTitle = 'Cài đặt';
$activeMenu = 'settings';
$pageHeading = 'Cài đặt hệ thống';
$pageDescription = 'Khu vực hiển thị thông tin tài khoản quản trị và định hướng bảo mật.';

$adminId = (int) ($_SESSION['admin']['id'] ?? 0);
$stmt = $conn->prepare('SELECT u.id, u.name, u.email, u.created_at, r.name AS role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = ? LIMIT 1');
$stmt->bind_param('i', $adminId);
$stmt->execute();
$adminInfo = $stmt->get_result()->fetch_assoc();
$stmt->close();

include(__DIR__ . '/includes/header.php');
?>
<div class="info-grid two">
    <section class="panel-card">
        <div class="panel-head solo">
            <div>
                <h3>Thông tin tài khoản quản trị</h3>
                <p>Tài khoản này là cổng truy cập duy nhất vào khu vực admin.</p>
            </div>
        </div>

        <div class="detail-list">
            <div class="detail-row"><span>Họ tên</span><strong><?php echo h($adminInfo['name'] ?? 'Admin'); ?></strong></div>
            <div class="detail-row"><span>Email</span><strong><?php echo h($adminInfo['email'] ?? 'Chưa có'); ?></strong></div>
            <div class="detail-row"><span>Vai trò</span><strong><?php echo h($adminInfo['role_name'] ?? 'Admin'); ?></strong></div>
            <div class="detail-row"><span>Ngày tạo</span><strong><?php echo !empty($adminInfo['created_at']) ? h(date('d/m/Y H:i', strtotime($adminInfo['created_at']))) : 'N/A'; ?></strong></div>
        </div>
    </section>

    <section class="panel-card">
        <div class="panel-head solo">
            <div>
                <h3>Khuyến nghị bảo mật</h3>
                <p>Để đúng yêu cầu chỉ một mình người quản lý được vào, nên giữ các nguyên tắc này.</p>
            </div>
        </div>

        <div class="security-list">
            <div class="security-item">Không tạo chức năng đăng ký Admin ở phía giao diện người dùng.</div>
            <div class="security-item">Chỉ cho tài khoản có <strong>role_id = 1</strong> truy cập thư mục <strong>/admin</strong>.</div>
            <div class="security-item">Đổi mật khẩu mặc định của tài khoản admin ngay sau khi import database.</div>
            <div class="security-item">Khi triển khai thật, nên thêm CSRF token và giới hạn số lần đăng nhập sai.</div>
        </div>
    </section>
</div>
<?php include(__DIR__ . '/includes/footer.php'); ?>
