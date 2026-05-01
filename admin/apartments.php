<?php
require_once(__DIR__ . '/includes/bootstrap.php');
require_admin();

$pageTitle = 'Quản lý tin đăng';
$activeMenu = 'posts';
$pageHeading = 'Quản lý tin đăng';
$pageDescription = 'Admin theo dõi toàn bộ bài đăng của khách hàng và môi giới tại đây.';

// Get success/error messages
$adminSuccess = $_SESSION['admin_success'] ?? '';
$adminError = $_SESSION['admin_error'] ?? '';
unset($_SESSION['admin_success'], $_SESSION['admin_error']);

$posts = mysqli_query($conn, "
    SELECT p.id, p.title, p.price, p.area, p.type, p.status, p.is_vip, p.created_at,
           p.bedroom, p.bathroom,
           pr.name AS project_name, pr.district, pr.province,
           u.name AS owner_name, u.email AS owner_email,
           r.name AS role_name
    FROM post p
    LEFT JOIN projects pr ON p.project_id = pr.id
    LEFT JOIN users u ON p.user_id = u.id
    LEFT JOIN roles r ON u.role_id = r.id
    ORDER BY p.created_at DESC, p.id DESC
");

$totalPosts = (int) get_single_value($conn, 'SELECT COUNT(*) FROM post');
$activePosts = (int) get_single_value($conn, 'SELECT COUNT(*) FROM post WHERE status = 1');
$hiddenPosts = (int) get_single_value($conn, 'SELECT COUNT(*) FROM post WHERE status <> 1');
$rentPosts = (int) get_single_value($conn, "SELECT COUNT(*) FROM post WHERE type = 'Cho thuê'");
$salePosts = (int) get_single_value($conn, "SELECT COUNT(*) FROM post WHERE type = 'Chuyển nhượng'");
$vipPosts = (int) get_single_value($conn, 'SELECT COUNT(*) FROM post WHERE is_vip = 1');

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

<div class="stats-grid four">
    <article class="stat-card">
        <div class="stat-label">Tổng bài đăng</div>
        <div class="stat-value"><?php echo $totalPosts; ?></div>
        <div class="stat-meta">Tất cả tin do khách hàng và môi giới đã tạo</div>
    </article>
    <article class="stat-card">
        <div class="stat-label">Đang hiển thị</div>
        <div class="stat-value"><?php echo $activePosts; ?></div>
        <div class="stat-meta">Những tin đang xuất hiện ngoài website</div>
    </article>
    <article class="stat-card">
        <div class="stat-label">Đang ẩn</div>
        <div class="stat-value"><?php echo $hiddenPosts; ?></div>
        <div class="stat-meta">Tin cần kiểm tra lại hoặc chưa mở hiển thị</div>
    </article>
    <article class="stat-card">
        <div class="stat-label">Tin VIP</div>
        <div class="stat-value"><?php echo $vipPosts; ?></div>
        <div class="stat-meta">Nhóm bài được ưu tiên hiển thị</div>
    </article>
</div>

<section class="panel-card">
    <div class="panel-head">
        <div>
            <h3>Danh sách bài đăng</h3>
            <p>Admin có thể xem nhanh thông tin người đăng, loại tin và trạng thái hiển thị.</p>
        </div>
        <div class="panel-actions">
            <span class="status-badge primary">Chuyển nhượng: <?php echo $salePosts; ?></span>
            <span class="status-badge warning">Cho thuê: <?php echo $rentPosts; ?></span>
        </div>
    </div>

    <div class="table-wrap">
        <table class="admin-table enhanced-post-table">
            <thead>
                <tr>
                    <th>Bài đăng</th>
                    <th>Người đăng</th>
                    <th>Loại tin</th>
                    <th>Thông số</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($posts && mysqli_num_rows($posts) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($posts)): ?>
                    <tr>
                        <td>
                            <strong><?php echo h($row['title']); ?></strong>
                            <div class="cell-sub"><?php echo h(($row['project_name'] ?: 'Chưa gán dự án') . (!empty($row['district']) ? ' - ' . $row['district'] : '') . (!empty($row['province']) ? ', ' . $row['province'] : '')); ?></div>
                            <div class="table-tags">
                                <span class="table-pill"><?php echo h(format_money($row['price'])); ?></span>
                                <span class="table-pill"><?php echo h($row['area']); ?> m²</span>
                                <?php if ((int)$row['is_vip'] === 1): ?>
                                    <span class="table-pill accent">VIP</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <strong><?php echo h($row['owner_name'] ?: 'Không rõ'); ?></strong>
                            <div class="cell-sub"><?php echo h($row['owner_email'] ?: 'Chưa có email'); ?></div>
                            <div class="cell-sub"><?php echo h($row['role_name'] ?: 'Chưa phân vai trò'); ?></div>
                        </td>
                        <td>
                            <span class="status-badge <?php echo $row['type'] === 'Cho thuê' ? 'warning' : 'primary'; ?>">
                                <?php echo h($row['type']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="cell-sub">Phòng ngủ: <strong><?php echo (int)$row['bedroom']; ?></strong></div>
                            <div class="cell-sub">Phòng tắm: <strong><?php echo (int)$row['bathroom']; ?></strong></div>
                            <div class="cell-sub">Tạo lúc: <?php echo h(date('d/m/Y H:i', strtotime($row['created_at']))); ?></div>
                        </td>
                        <td>
                            <span class="status-badge <?php echo ((int) $row['status'] === 1) ? 'success' : 'neutral'; ?>">
                                <?php echo ((int) $row['status'] === 1) ? 'Đang hiển thị' : 'Đang ẩn'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a class="mini-btn dark" href="../public/detail.php?id=<?php echo (int)$row['id']; ?>" target="_blank">Xem</a>
                                <a class="mini-btn" href="post-action.php?action=toggle_status&post_id=<?php echo (int)$row['id']; ?>" onclick="return confirm('Xác nhận <?php echo (int)$row['status'] === 1 ? 'ẩn' : 'bật'; ?> tin đăng này?')"><?php echo ((int)$row['status'] === 1) ? 'Ẩn tin' : 'Bật tin'; ?></a>
                                <?php if ((int)$row['status'] !== 1): ?>
                                    <a class="mini-btn" href="post-action.php?action=approve&post_id=<?php echo (int)$row['id']; ?>" style="background:#10b981;color:white;">Duyệt</a>
                                <?php endif; ?>
                                <a class="mini-btn" href="post-action.php?action=reject&post_id=<?php echo (int)$row['id']; ?>" style="background:#f59e0b;color:white;" onclick="return confirm('Xác nhận từ chối tin đăng này?')">Từ chối</a>
                                <a class="mini-btn" href="post-action.php?action=delete&post_id=<?php echo (int)$row['id']; ?>" style="background:#dc2626;color:white;" onclick="return confirm('Xác nhận xóa tin đăng này? Hành động này không thể hoàn tác.')">Xóa</a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6"><div class="empty-state small">Chưa có dữ liệu bài đăng.</div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include(__DIR__ . '/includes/footer.php'); ?>
