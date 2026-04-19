<?php
require_once(__DIR__ . '/includes/bootstrap.php');
require_admin();

$pageTitle = 'Admin Dashboard';
$activeMenu = 'dashboard';
$pageHeading = 'Bảng điều khiển';
$pageDescription = 'Theo dõi nhanh tình trạng tin đăng, người dùng và liên hệ trên hệ thống.';

$totalPosts = (int) get_single_value($conn, 'SELECT COUNT(*) FROM post');
$activePosts = (int) get_single_value($conn, 'SELECT COUNT(*) FROM post WHERE status = 1');
$totalContacts = (int) get_single_value($conn, 'SELECT COUNT(*) FROM contacts');
$vipPosts = (int) get_single_value($conn, 'SELECT COUNT(*) FROM post WHERE is_vip = 1');
$totalProjects = (int) get_single_value($conn, 'SELECT COUNT(*) FROM projects');
$totalUsers = (int) get_single_value($conn, 'SELECT COUNT(*) FROM users WHERE role_id <> 1 OR role_id IS NULL');

$recentRequests = mysqli_query($conn, "
    SELECT c.id, c.message, c.status, c.created_at,
           sender.name AS sender_name, sender.phone AS sender_phone, sender.email AS sender_email,
           p.title AS post_title,
           pr.name AS project_name, pr.district, pr.province
    FROM contacts c
    LEFT JOIN users sender ON c.sender_id = sender.id
    LEFT JOIN post p ON c.post_id = p.id
    LEFT JOIN projects pr ON p.project_id = pr.id
    ORDER BY c.created_at DESC
    LIMIT 6
");


include(__DIR__ . '/includes/header.php');
?>
<div class="stats-grid four">
    <article class="stat-card">
        <div class="stat-label">Tổng căn hộ</div>
        <div class="stat-value"><?php echo $totalPosts; ?></div>
        <div class="stat-meta">Toàn bộ tin đăng trên hệ thống</div>
    </article>

    <article class="stat-card">
        <div class="stat-label">Đang hiển thị</div>
        <div class="stat-value"><?php echo $activePosts; ?></div>
        <div class="stat-meta">Tin đang mở cho khách xem</div>
    </article>

    <article class="stat-card">
        <div class="stat-label">Yêu cầu liên hệ</div>
        <div class="stat-value"><?php echo $totalContacts; ?></div>
        <div class="stat-meta">Tổng yêu cầu tư vấn đã ghi nhận</div>
    </article>

    <article class="stat-card">
        <div class="stat-label">Tin VIP</div>
        <div class="stat-value"><?php echo $vipPosts; ?></div>
        <div class="stat-meta">Bài đăng đang được ưu tiên hiển thị</div>
    </article>
</div>

<div class="dashboard-grid">
    <section class="panel-card">
        <div class="panel-head">
            <div>
                <h3>Yêu cầu liên hệ gần đây</h3>
                <p>Theo dõi người dùng đang quan tâm tới căn hộ</p>
            </div>
            <a href="requests.php" class="panel-link">Xem tất cả</a>
        </div>

        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Khách hàng</th>
                        <th>Căn hộ quan tâm</th>
                        <th>Trạng thái</th>
                        <th>Thời gian</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($recentRequests && mysqli_num_rows($recentRequests) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($recentRequests)): ?>
                        <tr>
                            <td>
                                <strong><?php echo h($row['sender_name'] ?: 'Khách chưa rõ tên'); ?></strong>
                                <div class="cell-sub"><?php echo h($row['sender_phone'] ?: ($row['sender_email'] ?: 'Chưa có liên hệ')); ?></div>
                            </td>
                            <td>
                                <strong><?php echo h($row['post_title'] ?: 'Tin đăng không xác định'); ?></strong>
                                <div class="cell-sub"><?php echo h(trim(($row['project_name'] ?? '') . ' ' . ($row['district'] ?? '') . ' ' . ($row['province'] ?? ''))); ?></div>
                            </td>
                            <td>
                                <span class="status-badge <?php echo ($row['status'] ?? '') === 'unread' ? 'warning' : 'success'; ?>">
                                    <?php echo ($row['status'] ?? '') === 'unread' ? 'Chưa đọc' : 'Đã đọc'; ?>
                                </span>
                            </td>
                            <td><?php echo h(date('d/m/Y H:i', strtotime($row['created_at']))); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">
                            <div class="empty-state small">Hiện chưa có yêu cầu liên hệ nào trong cơ sở dữ liệu.</div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <aside class="side-stack">
        <section class="panel-card compact">
            <div class="panel-head solo">
                <div>
                    <h3>Thông tin nhanh</h3>
                    <p>Tóm tắt dữ liệu hiện có</p>
                </div>
            </div>

            <div class="mini-list">
                <div class="mini-item">
                    <span>Tổng dự án</span>
                    <strong><?php echo $totalProjects; ?></strong>
                </div>
                <div class="mini-item">
                    <span>Tài khoản người dùng</span>
                    <strong><?php echo $totalUsers; ?></strong>
                </div>
                <div class="mini-item">
                    <span>Tin chuyển nhượng</span>
                    <strong><?php echo (int) get_single_value($conn, "SELECT COUNT(*) FROM post WHERE type = 'Chuyển nhượng'"); ?></strong>
                </div>
                <div class="mini-item">
                    <span>Tin cho thuê</span>
                    <strong><?php echo (int) get_single_value($conn, "SELECT COUNT(*) FROM post WHERE type = 'Cho thuê'"); ?></strong>
                </div>
            </div>
        </section>


    </aside>
</div>
<?php include(__DIR__ . '/includes/footer.php'); ?>
