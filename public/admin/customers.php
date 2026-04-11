<?php
require_once(__DIR__ . '/includes/bootstrap.php');
require_admin();

$pageTitle = 'Khách hàng';
$activeMenu = 'customers';
$pageHeading = 'Khách hàng';
$pageDescription = 'Theo dõi tài khoản đã đăng ký trong hệ thống.';

$customers = mysqli_query($conn, "
    SELECT u.id, u.name, u.email, u.phone, u.created_at, r.name AS role_name
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.id
    WHERE u.role_id <> 1 OR u.role_id IS NULL
    ORDER BY u.created_at DESC, u.id DESC
");

include(__DIR__ . '/includes/header.php');
?>
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
                    <th>Ngày tạo</th>
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
                        <td><?php echo h(date('d/m/Y H:i', strtotime($row['created_at']))); ?></td>
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
