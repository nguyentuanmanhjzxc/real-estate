<?php
require_once(__DIR__ . '/includes/bootstrap.php');
require_admin();
 
global $conn;

$pageTitle = 'Khách hàng';
$activeMenu = 'customers';
$pageHeading = 'Khách hàng';
$pageDescription = 'Theo dõi tài khoản đã đăng ký trong hệ thống.';

// Get success/error messages
$adminSuccess = $_SESSION['admin_success'] ?? '';
$adminError = $_SESSION['admin_error'] ?? '';
unset($_SESSION['admin_success'], $_SESSION['admin_error']);

$customers = mysqli_query($conn, "
    SELECT u.id, u.name, u.email, u.phone, u.created_at, 
           1 AS is_active, r.name AS role_name
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.id
    WHERE u.role_id <> 1 OR u.role_id IS NULL
    ORDER BY u.created_at DESC, u.id DESC
");

include(__DIR__ . '/includes/header.php');

// Alert box for success/error
function adminAlert($type, $message) {
    $bg = $type === 'success' ? '#d1fae5' : '#fee2e2';
    $color = $type === 'success' ? '#065f46' : '#991b1b';
    $border = $type === 'success' ? '#a7f3d0' : '#fecaca';
    echo "<div style='background:{$bg};color:{$color};border:1px solid {$border};padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:14px;'>".h($message)."</div>";
}
?>

<?php if ($adminSuccess): ?>
    <?php adminAlert('success', $adminSuccess); ?>
<?php endif; ?>
<?php if ($adminError): ?>
    <?php adminAlert('error', $adminError); ?>
<?php endif; ?>

<div class="stats-grid three">
    <article class="stat-card">
        <div class="stat-label">Tài khoản môi giới</div>
        <div class="stat-value"><?php echo (int) get_single_value($conn, 'SELECT COUNT(*) FROM users WHERE role_id = 2'); ?></div>
        <div class="stat-meta">Những người có thể đăng tin bất động sản</div>
    </article>
    <article class="stat-card">
        <div class="stat-label">Tài khoản khách hàng</div>
        <div class="stat-value"><?php echo (int) get_single_value($conn, 'SELECT COUNT(*) FROM users WHERE role_id = 3'); ?></div>
        <div class="stat-meta">Người dùng cuối theo role hiện tại</div>
    </article>
    <article class="stat-card">
        <div class="stat-label">Tổng người dùng</div>
        <div class="stat-value"><?php echo (int) get_single_value($conn, 'SELECT COUNT(*) FROM users WHERE role_id <> 1 OR role_id IS NULL'); ?></div>
        <div class="stat-meta">Không tính tài khoản quản trị</div>
    </article>
</div>

<section class="panel-card">
    <div class="panel-head">
        <div>
            <h3>Danh sách tài khoản</h3>
            <p>Bảng này giúp người quản lý kiểm tra nhanh email, số điện thoại và vai trò của từng người dùng.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Họ tên</th>
                    <th>Email</th>
                    <th>Số điện thoại</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($customers && mysqli_num_rows($customers) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($customers)): ?>
                    <tr>
                        <td>#<?php echo (int) $row['id']; ?></td>
                        <td><?php echo h(trim($row['name'])); ?></td>
                        <td><?php echo h($row['email'] ?: 'Chưa có email'); ?></td>
                        <td><?php echo h($row['phone'] ?: 'Chưa có số điện thoại'); ?></td>
                        <td><span class="status-badge neutral"><?php echo h($row['role_name'] ?: 'Chưa phân quyền'); ?></span></td>
                        <td>
                            <span class="status-badge <?php echo ((int)($row['is_active'] ?? 1) === 1) ? 'success' : 'warning'; ?>">
                                <?php echo ((int)($row['is_active'] ?? 1) === 1) ? 'Hoạt động' : 'Bị khóa'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a class="mini-btn" href="user-action.php?action=toggle_status&user_id=<?php echo (int)$row['id']; ?>" onclick="return confirm('Xác nhận ' + (<?php echo (int)($row['is_active'] ?? 1) === 1 ? "'khóa'" : "'mở khóa'"; ?>) + ' tài khoản này?')"><?php echo ((int)($row['is_active'] ?? 1) === 1) ? 'Khóa' : 'Mở khóa'; ?></a>
                                <a class="mini-btn" href="user-action.php?action=delete&user_id=<?php echo (int)$row['id']; ?>" style="background:#dc2626;color:white;" onclick="return confirm('Xác nhận xóa tài khoản này? Tất cả bài đăng của người dùng cũng sẽ bị xóa.')">Xóa</a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6"><div class="empty-state small">Chưa có dữ liệu khách hàng.</div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include(__DIR__ . '/includes/footer.php'); ?>
