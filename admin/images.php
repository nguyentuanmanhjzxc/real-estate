<?php
require_once(__DIR__ . '/includes/bootstrap.php');
require_admin();

global $conn;

$pageTitle = 'Quản lý hình ảnh';
$activeMenu = 'images';
$pageHeading = 'Quản lý hình ảnh căn hộ';
$pageDescription = 'Kiểm soát toàn bộ hình ảnh được tải lên hệ thống.';

// Get success/error messages
$adminSuccess = $_SESSION['admin_success'] ?? '';
$adminError = $_SESSION['admin_error'] ?? '';
unset($_SESSION['admin_success'], $_SESSION['admin_error']);

// Handle delete image
if (isset($_GET['delete'])) {
    $imageId = (int)$_GET['delete'];
    $stmt = $conn->prepare("SELECT image_url, post_id FROM images WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $imageId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($image = $result->fetch_assoc()) {
            $filePath = __DIR__ . '/../uploads/' . $image['image_url'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $stmt->close();
            
            $stmt = $conn->prepare("DELETE FROM images WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $imageId);
                $stmt->execute();
                $stmt->close();
                $_SESSION['admin_success'] = 'Đã xóa hình ảnh thành công.';
            } else {
                $_SESSION['admin_error'] = 'Có lỗi xảy ra khi xóa hình ảnh.';
            }
        } else {
            $stmt->close();
            $_SESSION['admin_error'] = 'Hình ảnh không tồn tại.';
        }
    }
    header('Location: images.php');
    exit();
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all'; // all, thumbnail, regular
$searchQuery = trim($_GET['search'] ?? '');

// Build query
$whereClauses = [];
if ($statusFilter === 'thumbnail') {
    $whereClauses[] = "i.is_thumbnail = 1";
} elseif ($statusFilter === 'regular') {
    $whereClauses[] = "i.is_thumbnail = 0";
}

if (!empty($searchQuery)) {
    $searchEscaped = mysqli_real_escape_string($conn, $searchQuery);
    $whereClauses[] = "(p.title LIKE '%{$searchEscaped}%' OR i.image_url LIKE '%{$searchEscaped}%')";
}

$whereClause = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// Get images with pagination
$limit = 24;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$imagesQuery = "
    SELECT i.id, i.image_url, i.is_thumbnail, 
           NOW() AS uploaded_at,
           p.id AS post_id, p.title AS post_title, p.status AS post_status,
           u.name AS user_name
    FROM images i
    LEFT JOIN post p ON i.post_id = p.id
    LEFT JOIN users u ON p.user_id = u.id
    {$whereClause}
    ORDER BY i.id DESC
    LIMIT {$limit} OFFSET {$offset}
";

$images = mysqli_query($conn, $imagesQuery);

// Get total count
$countQuery = "
    SELECT COUNT(*) FROM images i
    LEFT JOIN post p ON i.post_id = p.id
    {$whereClause}
";
$totalImages = (int) get_single_value($conn, $countQuery);
$totalPages = ceil($totalImages / $limit);

// Statistics
$totalImageCount = (int) get_single_value($conn, 'SELECT COUNT(*) FROM images');
$thumbnailCount = (int) get_single_value($conn, 'SELECT COUNT(*) FROM images WHERE is_thumbnail = 1');
$orphanedCount = (int) get_single_value($conn, 'SELECT COUNT(*) FROM images i LEFT JOIN post p ON i.post_id = p.id WHERE p.id IS NULL');

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
        <div class="stat-label">Tổng hình ảnh</div>
        <div class="stat-value"><?php echo $totalImageCount; ?></div>
        <div class="stat-meta">Tất cả hình ảnh trong hệ thống</div>
    </article>
    <article class="stat-card">
        <div class="stat-label">Ảnh bìa</div>
        <div class="stat-value"><?php echo $thumbnailCount; ?></div>
        <div class="stat-meta">Ảnh đại diện cho các bài đăng</div>
    </article>
    <article class="stat-card">
        <div class="stat-label">Ảnh mồ côi</div>
        <div class="stat-value"><?php echo $orphanedCount; ?></div>
        <div class="stat-meta">Ảnh không thuộc bài đăng nào</div>
    </article>
</div>

<!-- Filter and Search -->
<section class="panel-card">
    <div class="panel-head solo">
        <div>
            <h3>Bộ lọc và tìm kiếm</h3>
            <p>Tìm kiếm và lọc hình ảnh theo các tiêu chí.</p>
        </div>
    </div>
    
    <form method="GET" action="images.php" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 12px; align-items: end;">
        <div class="field">
            <label>Tìm kiếm</label>
            <input type="text" name="search" placeholder="Tìm theo tiêu đề bài hoặc tên ảnh..." value="<?php echo h($searchQuery); ?>">
        </div>
        <div class="field">
            <label>Trạng thái</label>
            <select name="status">
                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>Tất cả</option>
                <option value="thumbnail" <?php echo $statusFilter === 'thumbnail' ? 'selected' : ''; ?>>Ảnh bìa</option>
                <option value="regular" <?php echo $statusFilter === 'regular' ? 'selected' : ''; ?>>Ảnh thường</option>
            </select>
        </div>
        <button type="submit" class="dark-btn" style="height: 40px; padding: 0 20px;">Lọc</button>
    </form>
</section>

<!-- Images Grid -->
<section class="panel-card">
    <div class="panel-head solo">
        <div>
            <h3>Thư viện hình ảnh</h3>
            <p>Hiển thị <?php echo min($limit, $totalImages - $offset); ?> trong tổng số <?php echo $totalImages; ?> hình ảnh.</p>
        </div>
    </div>

    <div class="image-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px;">
        <?php if ($images && mysqli_num_rows($images) > 0): ?>
            <?php while ($img = mysqli_fetch_assoc($images)): ?>
                <div class="image-card" style="background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb; position: relative;">
                    <div style="position: relative; padding-top: 75%; background: #f3f4f6;">
                        <img src="../uploads/<?php echo h($img['image_url']); ?>" 
                             alt="<?php echo h($img['post_title'] ?? 'Image'); ?>" 
                             style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                        <?php if ((int)$img['is_thumbnail'] === 1): ?>
                            <span style="position: absolute; top: 8px; left: 8px; background: rgba(16, 185, 129, 0.9); color: white; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 700;">BÌA</span>
                        <?php endif; ?>
                    </div>
                    <div style="padding: 12px;">
                        <div style="font-size: 13px; font-weight: 600; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <?php echo h($img['post_title'] ?? 'Không có bài đăng'); ?>
                        </div>
                        <div style="font-size: 11px; color: #6b7280; margin-bottom: 8px;">
                            <?php echo h($img['user_name'] ?? 'Không rõ'); ?> • <?php echo h(date('d/m/Y', strtotime($img['uploaded_at']))); ?>
                        </div>
                        <div style="display: flex; gap: 6px;">
                            <a href="../uploads/<?php echo h($img['image_url']); ?>" target="_blank" class="mini-btn" style="flex: 1; font-size: 11px;">Xem</a>
                            <a href="post-action.php?action=delete_image&image_id=<?php echo (int)$img['id']; ?>" 
                               class="mini-btn" style="flex: 1; font-size: 11px; background: #dc2626; color: white;"
                               onclick="return confirm('Xác nhận xóa hình ảnh này?')">Xóa</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 48px;">
                <div class="empty-state small">Không tìm thấy hình ảnh nào.</div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div style="display: flex; justify-content: center; gap: 8px; margin-top: 24px; flex-wrap: wrap;">
            <?php if ($page > 1): ?>
                <a href="images.php?page=<?php echo $page - 1; ?>&status=<?php echo h($statusFilter); ?>&search=<?php echo urlencode($searchQuery); ?>" 
                   class="mini-btn">Trước</a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="images.php?page=<?php echo $i; ?>&status=<?php echo h($statusFilter); ?>&search=<?php echo urlencode($searchQuery); ?>" 
                   class="mini-btn" style="background: <?php echo $i === $page ? 'var(--primary)' : ''; ?>; color: <?php echo $i === $page ? 'white' : ''; ?>;">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="images.php?page=<?php echo $page + 1; ?>&status=<?php echo h($statusFilter); ?>&search=<?php echo urlencode($searchQuery); ?>" 
                   class="mini-btn">Sau</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<style>
.image-grid .image-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
    transition: all 0.2s ease;
}
</style>

<?php include(__DIR__ . '/includes/footer.php'); ?>