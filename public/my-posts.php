<?php
session_start();
include(__DIR__ . '/../config/database.php');

// Pagination settings
$items_per_page = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $items_per_page;

if (empty($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

$ten_trang = 'TheApartment';
$user = $_SESSION['user'];
$userId = (int)($user['id'] ?? 0);
$isAdmin = (int)($user['role'] ?? 0) === 1;

// Get success/error messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Filter tab
$filterTab = $_GET['tab'] ?? 'all';

// Build filter condition
$filterConditions = ["p.user_id = {$userId}"];
switch ($filterTab) {
    case 'sale':
        $filterConditions[] = "p.type = 'Chuyển nhượng'";
        break;
    case 'rent':
        $filterConditions[] = "p.type = 'Cho thuê'";
        break;
    case 'active':
        $filterConditions[] = "p.status = 1";
        break;
    case 'hidden':
        $filterConditions[] = "p.status <> 1";
        break;
}

$whereClause = implode(' AND ', $filterConditions);

// Get total count
$totalPosts = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM post WHERE {$whereClause}"))[0];
$totalPages = ceil($totalPosts / $items_per_page);

// Get posts with pagination using prepared statement
$sql = "SELECT p.id, p.title, p.price, p.area, p.type, p.status, p.created_at, p.updated_at, p.bedroom, p.bathroom, p.furniture,
           pr.name AS project_name, pr.district, pr.province,
           img.image_url
    FROM post p
    LEFT JOIN projects pr ON p.project_id = pr.id
    LEFT JOIN images img ON p.id = img.post_id AND img.is_thumbnail = 1
    WHERE {$whereClause}
    ORDER BY p.created_at DESC, p.id DESC
    LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("ii", $items_per_page, $offset);
    $stmt->execute();
    $myPosts = $stmt->get_result();
    $stmt->close();
} else {
    $myPosts = false;
}

// Stats
$allPostsCount = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM post WHERE user_id = {$userId}"))[0];
$livePosts = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM post WHERE user_id = {$userId} AND status = 1"))[0];
$hiddenPosts = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM post WHERE user_id = {$userId} AND status <> 1"))[0];

function format_money_local($amount): string {
    $amount = (float)($amount ?? 0);
    if ($amount >= 1000000000) return number_format($amount / 1000000000, 1, ',', '.') . ' tỷ';
    if ($amount >= 1000000) return number_format($amount / 1000000, 0, ',', '.') . ' triệu';
    return number_format($amount, 0, ',', '.') . ' đ';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý tin đăng - <?php echo $ten_trang; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/posting.css">
    <style>
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
        }
        .alert.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .post-actions button, .post-actions a {
            cursor: pointer;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            border: none;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .post-actions .primary {
            background: #1e40af;
            color: white;
        }
        .post-actions .primary:hover {
            background: #1e3a8a;
        }
        .post-actions .warning {
            background: #f59e0b;
            color: white;
        }
        .post-actions .warning:hover {
            background: #d97706;
        }
        .post-actions .danger {
            background: #dc2626;
            color: white;
        }
        .post-actions .danger:hover {
            background: #b91c1c;
        }
        .post-actions .neutral {
            background: #e5e7eb;
            color: #374151;
        }
        .post-actions .neutral:hover {
            background: #d1d5db;
        }
        .confirm-dialog {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .confirm-dialog.active {
            display: flex;
        }
        .confirm-box {
            background: white;
            padding: 24px;
            border-radius: 12px;
            max-width: 400px;
            width: 90%;
            text-align: center;
        }
        .confirm-box h3 {
            margin-bottom: 12px;
            color: #1f2937;
        }
        .confirm-box p {
            margin-bottom: 20px;
            color: #6b7280;
            font-size: 14px;
        }
        .confirm-box .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        .confirm-box .actions button {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            cursor: pointer;
        }
        .confirm-box .actions .confirm-yes {
            background: #dc2626;
            color: white;
        }
        .confirm-box .actions .confirm-no {
            background: #e5e7eb;
            color: #374151;
        }
        .pagination {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-top: 24px;
            flex-wrap: wrap;
        }
        .pagination a {
            padding: 8px 14px;
            border-radius: 6px;
            background: #e5e7eb;
            color: #374151;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s;
        }
        .pagination a:hover,
        .pagination a.active {
            background: #1e40af;
            color: white;
        }
        .existing-images {
            margin-bottom: 16px;
        }
        .existing-images-grid {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 8px;
        }
        .existing-image-item {
            position: relative;
            width: 80px;
            height: 60px;
            border-radius: 8px;
            overflow: hidden;
        }
        .existing-image-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .existing-image-item .thumb-badge {
            position: absolute;
            bottom: 2px;
            left: 2px;
            background: rgba(0,0,0,0.7);
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
<nav class="navbar">
    <a href="index.php" class="btn-link-reset"><div class="navbar-logo"><span>The</span>Apartment</div></a>
    <ul class="navbar-menu">
        <li><a href="index.php">Mua bán</a></li>
        <li><a href="index.php">Cho thuê</a></li>
        <li><a href="post-create.php">Đăng tin</a></li>
        <li><a href="my-posts.php">Tin của tôi</a></li>
    </ul>
    <div class="navbar-actions user-area">
        <a href="post-create.php">
            <button class="btn-dang-tin">Đăng tin mới</button>
        </a>

        <div class="user-menu">
            <?php
            $avatar = !empty($_SESSION['user']['avatar'])
                ? "../uploads/avatar/" . $_SESSION['user']['avatar']
                : "https://via.placeholder.com/40";
            ?>

            <div class="user-btn">
                <img src="<?= $avatar ?>" class="nav-avatar">
                <?php echo htmlspecialchars($_SESSION['user']['name']); ?> ▼
            </div>

            <div class="dropdown">
                <a href="profile.php">Trang cá nhân</a>
                <a href="my-posts.php">Tin của tôi</a>
                <?php if ($isAdmin): ?>
                    <a href="../admin/dashboard.php">Quay lại trang admin</a>
                <?php endif; ?>
                <a href="../modules/auth/logout.php">Đăng xuất</a>
            </div>
        </div>
    </div>
</nav>

<!-- Confirm Dialog -->
<div class="confirm-dialog" id="confirmDialog">
    <div class="confirm-box">
        <h3 id="confirmTitle">Xác nhận</h3>
        <p id="confirmMessage">Bạn có chắc chắn muốn thực hiện thao tác này?</p>
        <div class="actions">
            <button class="confirm-no" onclick="closeConfirm()">Hủy</button>
            <button class="confirm-yes" id="confirmYesBtn">Xác nhận</button>
        </div>
    </div>
</div>

<div class="page-shell">
    <div class="list-head">
        <div>
            <h2>Quản lý tin đăng của tôi</h2>
            <p class="badge-muted">Quản lý tất cả tin đăng của bạn tại đây. Bạn có thể chỉnh sửa, ẩn/hiện hoặc xóa tin đăng.</p>
        </div>
        <div class="head-actions">
            <a href="post-create.php" class="dark-btn">+ Tạo tin mới</a>
            <a href="index.php" class="outline-btn">Xem trang chủ</a>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="mini-stats">
        <div class="mini-stat">
            <strong><?php echo $allPostsCount; ?></strong>
            <div>Tổng tin đã tạo</div>
            <div class="stat-note">Bao gồm tin đang hiển thị và tin ẩn</div>
        </div>
        <div class="mini-stat">
            <strong><?php echo $livePosts; ?></strong>
            <div>Đang hiển thị</div>
            <div class="stat-note">Những tin người xem đang thấy ngoài trang tìm kiếm</div>
        </div>
        <div class="mini-stat">
            <strong><?php echo $hiddenPosts; ?></strong>
            <div>Đang ẩn / chờ xử lý</div>
            <div class="stat-note">Có thể do admin ẩn hoặc chưa bật hiển thị</div>
        </div>
    </div>

    <div class="quick-tabs">
        <a href="?tab=all" class="option-chip <?php echo $filterTab === 'all' ? 'active' : ''; ?>">Tất cả (<?php echo $allPostsCount; ?>)</a>
        <a href="?tab=sale" class="option-chip <?php echo $filterTab === 'sale' ? 'active' : ''; ?>">Chuyển nhượng</a>
        <a href="?tab=rent" class="option-chip <?php echo $filterTab === 'rent' ? 'active' : ''; ?>">Cho thuê</a>
        <a href="?tab=active" class="option-chip <?php echo $filterTab === 'active' ? 'active' : ''; ?>">Đang hiển thị</a>
        <a href="?tab=hidden" class="option-chip <?php echo $filterTab === 'hidden' ? 'active' : ''; ?>">Đang ẩn</a>
    </div>

    <div class="post-list">
        <?php if ($myPosts && mysqli_num_rows($myPosts) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($myPosts)): ?>
                <?php
                    $img = !empty($row['image_url']) ? '../uploads/' . $row['image_url'] : '';
                    $thumbStyle = !empty($img)
                        ? "background-image: linear-gradient(rgba(17,24,39,.14), rgba(17,24,39,.18)), url('" . htmlspecialchars($row['image_url'], ENT_QUOTES, 'UTF-8') . "');"
                        : 'background: #e5e7eb;';
                ?>
                <article class="post-card">
                    <div class="post-thumb <?php echo $row['type'] === 'Cho thuê' ? 'rent' : 'sale'; ?>" style="<?php echo $thumbStyle; ?>">
                        <div class="thumb-top">
                            <span class="thumb-badge"><?php echo htmlspecialchars($row['type']); ?></span>
                            <span class="thumb-badge <?php echo ((int)$row['status'] === 1) ? 'status-active' : 'status-hidden'; ?>">
                                <?php echo ((int)$row['status'] === 1) ? 'Hiển thị' : 'Đang ẩn'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="post-main">
                        <div class="post-title"><?php echo htmlspecialchars($row['title']); ?></div>
                        <div class="post-price"><?php echo htmlspecialchars(format_money_local($row['price'])); ?></div>
                        <div class="post-meta">
                            <span class="info-chip"><?php echo htmlspecialchars(($row['district'] ?? 'Chưa rõ khu vực') . (!empty($row['province']) ? ', ' . $row['province'] : '')); ?></span>
                            <span class="info-chip"><?php echo (float)$row['area']; ?> m²</span>
                            <span class="info-chip"><?php echo (int)$row['bedroom']; ?> PN</span>
                            <span class="info-chip"><?php echo (int)$row['bathroom']; ?> WC</span>
                        </div>
                        <div class="post-submeta">
                            <span>Dự án: <?php echo htmlspecialchars($row['project_name'] ?: 'Chưa gán dự án'); ?></span>
                            <span>•</span>
                            <span>Cập nhật: <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($row['updated_at'] ?? $row['created_at']))); ?></span>
                        </div>
                        <div class="post-actions">
                            <a class="primary" href="detail.php?id=<?php echo (int)$row['id']; ?>" target="_blank">Xem</a>
                            <a class="warning" href="post-action.php?action=edit&post_id=<?php echo (int)$row['id']; ?>">Chỉnh sửa</a>
                            <button type="button" class="warning" onclick="toggleStatus(<?php echo (int)$row['id']; ?>, <?php echo (int)$row['status']; ?>)">
                                <?php echo ((int)$row['status'] === 1) ? 'Ẩn tin' : 'Hiện tin'; ?>
                            </button>
                            <button type="button" class="danger" onclick="deletePost(<?php echo (int)$row['id']; ?>)">Xóa</button>
                        </div>
                    </div>
                </article>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-card">
                <h3>Bạn chưa có tin đăng nào</h3>
                <p class="badge-muted">Hãy tạo tin đăng đầu tiên để bắt đầu kết nối với khách hàng tiềm năng.</p>
                <div class="head-actions" style="justify-content:center; margin-top:18px;">
                    <a href="post-create.php" class="dark-btn">Tạo tin đầu tiên</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">← Trước</a>
        <?php endif; ?>

        <?php
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);
        
        if ($startPage > 1) {
            echo '<a href="?'.http_build_query(array_merge($_GET, ['page' => 1])).'"'.($page == 1 ? ' class="active"' : '').'>1</a>';
            if ($startPage > 2) echo '<span>...</span>';
        }
        
        for ($i = $startPage; $i <= $endPage; $i++):
        ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
               class="<?php echo $i === $page ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; 
        
        if ($endPage < $totalPages) {
            if ($endPage < $totalPages - 1) echo '<span>...</span>';
            echo '<a href="?'.http_build_query(array_merge($_GET, ['page' => $totalPages])).'"'.($page == $totalPages ? ' class="active"' : '').'>'.$totalPages.'</a>';
        }
        ?>

        <?php if ($page < $totalPages): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Sau →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="helper-card">
        <h3>Hướng dẫn sử dụng</h3>
        <p><strong>Xem:</strong> Xem chi tiết tin đăng của bạn như người dùng sẽ thấy.<br>
        <strong>Chỉnh sửa:</strong> Cập nhật thông tin, giá cả, hình ảnh của tin đăng.<br>
        <strong>Ẩn/Hiện tin:</strong> Tạm ẩn tin khỏi trang tìm kiếm hoặc hiển thị lại tin đã ẩn.<br>
        <strong>Xóa:</strong> Xóa vĩnh viễn tin đăng (không thể khôi phục).</p>
    </div>
</div>

<script>
let confirmCallback = null;

function showConfirm(title, message, callback) {
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmMessage').textContent = message;
    document.getElementById('confirmDialog').classList.add('active');
    confirmCallback = callback;
}

function closeConfirm() {
    document.getElementById('confirmDialog').classList.remove('active');
    confirmCallback = null;
}

document.getElementById('confirmYesBtn').addEventListener('click', function() {
    if (confirmCallback) {
        confirmCallback();
    }
    closeConfirm();
});

function toggleStatus(postId, currentStatus) {
    const newStatus = currentStatus === 1 ? 'ẩn' : 'hiện';
    showConfirm(
        'Xác nhận ' + (currentStatus === 1 ? 'ẩn' : 'hiện') + ' tin',
        'Bạn có chắc chắn muốn ' + newStatus + ' tin đăng này?',
        function() {
            window.location.href = 'post-action.php?action=toggle_status&post_id=' + postId;
        }
    );
}

function deletePost(postId) {
    showConfirm(
        'Xác nhận xóa tin',
        'Bạn có chắc chắn muốn xóa tin đăng này? Hành động này không thể hoàn tác.',
        function() {
            window.location.href = 'post-action.php?action=delete&post_id=' + postId;
        }
    );
}

// Close confirm dialog when clicking outside
document.getElementById('confirmDialog').addEventListener('click', function(e) {
    if (e.target === this) {
        closeConfirm();
    }
});
</script>

</body>
</html>