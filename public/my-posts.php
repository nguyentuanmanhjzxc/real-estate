<?php
session_start();
include(__DIR__ . '/../config/database.php');

if (empty($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

$ten_trang = 'TheApartment';
$user = $_SESSION['user'];
$userId = (int)($user['id'] ?? 0);

$myPosts = mysqli_query($conn, "
    SELECT p.id, p.title, p.price, p.area, p.type, p.status, p.created_at, p.bedroom, p.bathroom,
           pr.name AS project_name, pr.district, pr.province,
           img.image_url
    FROM post p
    LEFT JOIN projects pr ON p.project_id = pr.id
    LEFT JOIN images img ON p.id = img.post_id AND img.is_thumbnail = 1
    WHERE p.user_id = {$userId}
    ORDER BY p.created_at DESC, p.id DESC
");

$totalPosts = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM post WHERE user_id = {$userId}"))[0];
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
    <title>Tin của tôi - <?php echo $ten_trang; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/posting.css">
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
        <span style="color:#fff; font-weight:600;">Xin chào, <b><?php echo htmlspecialchars($user['name']); ?></b></span>
        <a href="post-create.php"><button class="btn-dang-tin">Đăng tin mới</button></a>
        <a href="logout.php"><button class="btn-dang-nhap">Đăng xuất</button></a>
    </div>
</nav>

<div class="page-shell">
    <div class="list-head">
        <div>
            <h2>Quản lý tin đăng của tôi</h2>
            <p class="badge-muted">Khách hàng và môi giới đều dùng cùng một khu quản lý. Admin sẽ kiểm tra và vận hành các bài đăng từ khu vực quản trị.</p>
        </div>
        <div class="head-actions">
            <a href="post-create.php" class="dark-btn">+ Tạo tin mới</a>
            <a href="index.php" class="outline-btn">Xem trang chủ</a>
        </div>
    </div>

    <div class="mini-stats">
        <div class="mini-stat">
            <strong><?php echo $totalPosts; ?></strong>
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
        <span class="option-chip active">Tất cả</span>
        <span class="option-chip">Chuyển nhượng</span>
        <span class="option-chip">Cho thuê</span>
        <span class="option-chip">Tin đang hiển thị</span>
        <span class="option-chip">Tin ẩn</span>
    </div>

    <div class="post-list">
        <?php if ($myPosts && mysqli_num_rows($myPosts) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($myPosts)): ?>
                <?php
                    $img = !empty($row['image_url']) ? '../uploads/' . $row['image_url'] : '';
                    $thumbStyle = !empty($img)
                        ? "background-image: linear-gradient(rgba(17,24,39,.14), rgba(17,24,39,.18)), url('" . htmlspecialchars($img, ENT_QUOTES, 'UTF-8') . "');"
                        : '';
                ?>
                <article class="post-card">
                    <div class="post-thumb <?php echo $row['type'] === 'Cho thuê' ? 'rent' : 'sale'; ?>" style="<?php echo $thumbStyle; ?>">
                        <div class="thumb-top">
                            <span class="thumb-badge"><?php echo htmlspecialchars($row['type']); ?></span>
                            <span class="thumb-badge"><?php echo ((int)$row['status'] === 1) ? 'Hiển thị' : 'Đang ẩn'; ?></span>
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
                            <span>Cập nhật: <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($row['created_at']))); ?></span>
                        </div>
                        <div class="post-actions">
                            <a class="primary" href="detail.php?id=<?php echo (int)$row['id']; ?>">Xem tin</a>
                            <button type="button">Chỉnh sửa UI</button>
                            <button type="button">Ẩn / hiện</button>
                            <button type="button">Xem liên hệ</button>
                        </div>
                    </div>
                </article>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-card">
                <h3>Bạn chưa có tin đăng nào</h3>
                <p class="badge-muted">Trang này đã được chuẩn bị sẵn để sau khi đăng nhập, khách hàng hoặc môi giới đều có thể xem lại và quản lý bài đăng của mình.</p>
                <div class="head-actions" style="justify-content:center; margin-top:18px;">
                    <a href="post-create.php" class="dark-btn">Tạo tin đầu tiên</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="helper-card">
        <h3>Luồng nên áp dụng cho website của bạn</h3>
        <p>Người dùng đăng nhập → tạo bài đăng → admin kiểm tra nội dung / hình ảnh → bật hiển thị ngoài trang tìm kiếm → người mua hoặc người thuê bấm vào xem chi tiết và liên hệ.</p>
    </div>
</div>
</body>
</html>
