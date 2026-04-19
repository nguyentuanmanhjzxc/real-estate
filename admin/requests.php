<?php
require_once(__DIR__ . '/includes/bootstrap.php');
require_admin();

$pageTitle = 'Yêu cầu liên hệ';
$activeMenu = 'requests';
$pageHeading = 'Yêu cầu liên hệ';
$pageDescription = 'Danh sách các tin nhắn quan tâm căn hộ được gửi qua hệ thống.';

$requests = mysqli_query($conn, "
    SELECT c.id, c.message, c.status, c.created_at,
           sender.name AS sender_name, sender.phone AS sender_phone, sender.email AS sender_email,
           receiver.name AS receiver_name,
           p.title AS post_title
    FROM contacts c
    LEFT JOIN users sender ON c.sender_id = sender.id
    LEFT JOIN users receiver ON c.receiver_id = receiver.id
    LEFT JOIN post p ON c.post_id = p.id
    ORDER BY c.created_at DESC, c.id DESC
");

include(__DIR__ . '/includes/header.php');
?>
<div class="stats-grid three">
    <article class="stat-card">
        <div class="stat-label">Tổng yêu cầu</div>
        <div class="stat-value"><?php echo (int) get_single_value($conn, 'SELECT COUNT(*) FROM contacts'); ?></div>
        <div class="stat-meta">Toàn bộ yêu cầu tư vấn / liên hệ</div>
    </article>
    <article class="stat-card">
        <div class="stat-label">Chưa đọc</div>
        <div class="stat-value"><?php echo (int) get_single_value($conn, "SELECT COUNT(*) FROM contacts WHERE status = 'unread'"); ?></div>
        <div class="stat-meta">Cần ưu tiên xử lý sớm</div>
    </article>
    <article class="stat-card">
        <div class="stat-label">Đã đọc</div>
        <div class="stat-value"><?php echo (int) get_single_value($conn, "SELECT COUNT(*) FROM contacts WHERE status = 'read'"); ?></div>
        <div class="stat-meta">Các yêu cầu đã được xem qua</div>
    </article>
</div>

<section class="panel-card">
    <div class="panel-head">
        <div>
            <h3>Lịch sử liên hệ</h3>
            <p>Bạn có thể mở rộng từ bảng này sang chức năng đánh dấu đã xử lý hoặc phản hồi khách hàng.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Người gửi</th>
                    <th>Người nhận</th>
                    <th>Tin đăng</th>
                    <th>Nội dung</th>
                    <th>Trạng thái</th>
                    <th>Thời gian</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($requests && mysqli_num_rows($requests) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($requests)): ?>
                    <tr>
                        <td>
                            <strong><?php echo h($row['sender_name'] ?: 'Không rõ'); ?></strong>
                            <div class="cell-sub"><?php echo h($row['sender_phone'] ?: ($row['sender_email'] ?: 'Chưa có thông tin')); ?></div>
                        </td>
                        <td><?php echo h($row['receiver_name'] ?: 'Không rõ'); ?></td>
                        <td><?php echo h($row['post_title'] ?: 'Tin đăng không xác định'); ?></td>
                        <td><?php echo h($row['message'] ?: 'Không có nội dung'); ?></td>
                        <td>
                            <span class="status-badge <?php echo ($row['status'] ?? '') === 'unread' ? 'warning' : 'success'; ?>">
                                <?php echo ($row['status'] ?? '') === 'unread' ? 'Chưa đọc' : 'Đã đọc'; ?>
                            </span>
                        </td>
                        <td><?php echo h(date('d/m/Y H:i', strtotime($row['created_at']))); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6"><div class="empty-state small">Hiện chưa có yêu cầu liên hệ trong hệ thống.</div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include(__DIR__ . '/includes/footer.php'); ?>
