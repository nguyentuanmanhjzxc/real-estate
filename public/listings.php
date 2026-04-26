<?php
session_start();
include(__DIR__ . '/../config/database.php');

// Pagination settings
$items_per_page = 12;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $items_per_page;

// Sorting
$sort = $_GET['sort'] ?? 'newest';
$sortOptions = [
    'newest' => 'ORDER BY p.created_at DESC, p.id DESC',
    'price-asc' => 'ORDER BY p.price ASC, p.id DESC',
    'price-desc' => 'ORDER BY p.price DESC, p.id DESC',
];
$sortClause = $sortOptions[$sort] ?? $sortOptions['newest'];

// Filter parameters
$keyword = trim($_GET['keyword'] ?? '');
$filterLocation = trim($_GET['location'] ?? '');
$filterType = trim($_GET['type_filter'] ?? '');
$filterArea = trim($_GET['area_filter'] ?? '');
$filterPrice = trim($_GET['price_filter'] ?? '');

// Build WHERE conditions
$conditions = ["p.status = 1"];
$typeMap = [
    'mua-ban' => 'Chuyển nhượng',
    'cho-thue' => 'Cho thuê',
];

if ($keyword !== '') {
    $kw = mysqli_real_escape_string($conn, $keyword);
    $conditions[] = "(p.title LIKE '%{$kw}%' OR COALESCE(p.description, '') LIKE '%{$kw}%' OR COALESCE(pr.name, '') LIKE '%{$kw}%' OR COALESCE(pr.district, '') LIKE '%{$kw}%' OR COALESCE(pr.province, '') LIKE '%{$kw}%')";
}

if ($filterLocation !== '') {
    $loc = mysqli_real_escape_string($conn, $filterLocation);
    $conditions[] = "(COALESCE(pr.district, '') LIKE '%{$loc}%' OR COALESCE(pr.province, '') LIKE '%{$loc}%' OR CONCAT(COALESCE(pr.district, ''), ', ', COALESCE(pr.province, '')) LIKE '%{$loc}%')";
}

if (isset($typeMap[$filterType])) {
    $typeValue = mysqli_real_escape_string($conn, $typeMap[$filterType]);
    $conditions[] = "p.type = '{$typeValue}'";
}

switch ($filterArea) {
    case 'duoi-50':
        $conditions[] = 'p.area < 50';
        break;
    case '50-80':
        $conditions[] = 'p.area >= 50 AND p.area <= 80';
        break;
    case '80-120':
        $conditions[] = 'p.area > 80 AND p.area <= 120';
        break;
    case 'tren-120':
        $conditions[] = 'p.area > 120';
        break;
}

switch ($filterPrice) {
    case 'duoi-2-ty':
        $conditions[] = 'p.price < 2000000000';
        break;
    case '2-5-ty':
        $conditions[] = 'p.price >= 2000000000 AND p.price <= 5000000000';
        break;
    case '5-10-ty':
        $conditions[] = 'p.price > 5000000000 AND p.price <= 10000000000';
        break;
    case 'tren-10-ty':
        $conditions[] = 'p.price > 10000000000';
        break;
    case 'duoi-10-trieu':
        $conditions[] = 'p.price < 10000000';
        break;
    case '10-20-trieu':
        $conditions[] = 'p.price >= 10000000 AND p.price <= 20000000';
        break;
    case 'tren-20-trieu':
        $conditions[] = 'p.price > 20000000';
        break;
}

$whereClause = implode(' AND ', $conditions);

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM post p 
             LEFT JOIN projects pr ON p.project_id = pr.id 
             WHERE {$whereClause}";
$countResult = mysqli_query($conn, $countSql);
$totalItems = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalItems / $items_per_page);

// Get apartments with pagination using prepared statement for LIMIT/OFFSET
$sql = "SELECT p.*, img.image_url, pr.province, pr.district, pr.name as project_name
        FROM post p
        LEFT JOIN images img ON p.id = img.post_id AND img.is_thumbnail = 1
        LEFT JOIN projects pr ON p.project_id = pr.id
        WHERE {$whereClause}
        {$sortClause}
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("ii", $items_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $result = false;
}

// Get location options for filter
$locationOptions = [];
$locationQuery = mysqli_query($conn, "SELECT DISTINCT district, province FROM projects WHERE (district IS NOT NULL AND district <> '') OR (province IS NOT NULL AND province <> '') ORDER BY province ASC, district ASC");
if ($locationQuery) {
    while ($loc = mysqli_fetch_assoc($locationQuery)) {
        $label = trim(($loc['district'] ?? '') . (($loc['district'] && $loc['province']) ? ', ' : '') . ($loc['province'] ?? ''));
        if ($label !== '') {
            $locationOptions[$label] = $label;
        }
    }
}

$ten_trang = "TheApartment";
$isAdmin = !empty($_SESSION['user']) && (int)($_SESSION['user']['role'] ?? 0) === 1;

function formatPrice($price) {
    $price = (float)$price;
    if ($price >= 1000000000) {
        return number_format($price / 1000000000, 1) . " Tỷ";
    } elseif ($price >= 1000000) {
        return number_format($price / 1000000, 1) . " Triệu";
    }
    return number_format($price, 0, ',', '.') . " đ";
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách căn hộ - <?php echo $ten_trang; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <a href="index.php" class="btn-link-reset"><div class="navbar-logo"><span>The</span>Apartment</div></a>
    <ul class="navbar-menu">
        <li><a href="listings.php?type_filter=mua-ban">Mua bán</a></li>
        <li><a href="listings.php?type_filter=cho-thue">Cho thuê</a></li>
        <li><a href="listings.php">Danh sách căn hộ</a></li>
        <?php if (isset($_SESSION['user'])): ?>
            <li><a href="my-posts.php">Tin của tôi</a></li>
        <?php endif; ?>
        <li><a href="#">Dự án</a></li>
        <li><a href="#">Tin tức</a></li>
    </ul>
    <div class="navbar-actions">
        <?php if (isset($_SESSION['user'])): ?>
            <a href="post-create.php" class="btn-link-reset">
                <button class="btn-dang-tin">Đăng tin</button>
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

        <?php else: ?>
            <button class="btn-dang-nhap" onclick="location.href='index.php?open_login=1'">Đăng nhập</button>
            <button class="btn-dang-tin" onclick="location.href='index.php?open_login=1'">Đăng tin</button>
        <?php endif; ?>
        <a href="../admin/dashboard.php" class="admin-link-nav">Admin</a>
    </div>
</nav>

<!-- Main Content -->
<main class="listings-page">
    <div class="listings-container">
        <!-- Sidebar Filter -->
        <aside class="filter-sidebar">
            <div class="filter-header">
                <h3>Bộ lọc tìm kiếm</h3>
                <a href="listings.php" class="reset-link">Đặt lại</a>
            </div>

            <form method="get" action="listings.php" class="filter-form">
                <div class="filter-group">
                    <label>Từ khóa</label>
                    <input type="text" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Tìm kiếm...">
                </div>

                <div class="filter-group">
                    <label>Khu vực</label>
                    <select name="location">
                        <option value="">Tất cả khu vực</option>
                        <?php foreach ($locationOptions as $locationLabel): ?>
                            <option value="<?php echo htmlspecialchars($locationLabel); ?>" <?php echo $filterLocation === $locationLabel ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($locationLabel); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Loại tin</label>
                    <select name="type_filter">
                        <option value="">Tất cả</option>
                        <option value="mua-ban" <?php echo $filterType === 'mua-ban' ? 'selected' : ''; ?>>Mua bán</option>
                        <option value="cho-thue" <?php echo $filterType === 'cho-thue' ? 'selected' : ''; ?>>Cho thuê</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Diện tích</label>
                    <select name="area_filter">
                        <option value="">Tất cả diện tích</option>
                        <option value="duoi-50" <?php echo $filterArea === 'duoi-50' ? 'selected' : ''; ?>>Dưới 50m²</option>
                        <option value="50-80" <?php echo $filterArea === '50-80' ? 'selected' : ''; ?>>50 - 80m²</option>
                        <option value="80-120" <?php echo $filterArea === '80-120' ? 'selected' : ''; ?>>80 - 120m²</option>
                        <option value="tren-120" <?php echo $filterArea === 'tren-120' ? 'selected' : ''; ?>>Trên 120m²</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Giá</label>
                    <select name="price_filter">
                        <option value="">Tất cả mức giá</option>
                        <option value="duoi-2-ty" <?php echo $filterPrice === 'duoi-2-ty' ? 'selected' : ''; ?>>Dưới 2 tỷ</option>
                        <option value="2-5-ty" <?php echo $filterPrice === '2-5-ty' ? 'selected' : ''; ?>>2 - 5 tỷ</option>
                        <option value="5-10-ty" <?php echo $filterPrice === '5-10-ty' ? 'selected' : ''; ?>>5 - 10 tỷ</option>
                        <option value="tren-10-ty" <?php echo $filterPrice === 'tren-10-ty' ? 'selected' : ''; ?>>Trên 10 tỷ</option>
                        <option value="duoi-10-trieu" <?php echo $filterPrice === 'duoi-10-trieu' ? 'selected' : ''; ?>>Dưới 10 triệu/tháng</option>
                        <option value="10-20-trieu" <?php echo $filterPrice === '10-20-trieu' ? 'selected' : ''; ?>>10 - 20 triệu/tháng</option>
                        <option value="tren-20-trieu" <?php echo $filterPrice === 'tren-20-trieu' ? 'selected' : ''; ?>>Trên 20 triệu/tháng</option>
                    </select>
                </div>

                <button type="submit" class="filter-submit-btn">Áp dụng bộ lọc</button>
            </form>
        </aside>

        <!-- Listings Content -->
        <div class="listings-content">
            <!-- Sort and Result Info Bar -->
            <div class="listings-bar">
                <div class="result-info">
                    <strong><?php echo number_format($totalItems); ?></strong> kết quả tìm thấy
                    <?php if ($keyword || $filterLocation || $filterType || $filterArea || $filterPrice): ?>
                        <span class="filter-summary">
                            <?php if ($keyword): ?>với từ khóa "<strong><?php echo htmlspecialchars($keyword); ?></strong>"<?php endif; ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="sort-controls">
                    <label>Sắp xếp theo:</label>
                    <select onchange="changeSort(this.value)" class="sort-select">
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Mới nhất</option>
                        <option value="price-asc" <?php echo $sort === 'price-asc' ? 'selected' : ''; ?>>Giá tăng dần</option>
                        <option value="price-desc" <?php echo $sort === 'price-desc' ? 'selected' : ''; ?>>Giá giảm dần</option>
                    </select>
                </div>
            </div>

            <!-- Active Filters Tags -->
            <?php if ($keyword || $filterLocation || $filterType || $filterArea || $filterPrice): ?>
            <div class="active-filters">
                <span class="filter-tag">
                    Bộ lọc đang áp dụng
                    <a href="listings.php" class="clear-all">✕ Xóa tất cả</a>
                </span>
                <?php if ($keyword): ?>
                    <span class="filter-tag">
                        Từ khóa: <?php echo htmlspecialchars($keyword); ?>
                        <a href="?<?php echo http_build_query(array_filter(['keyword' => '', 'location' => $filterLocation, 'type_filter' => $filterType, 'area_filter' => $filterArea, 'price_filter' => $filterPrice, 'sort' => $sort])); ?>" class="remove-tag">✕</a>
                    </span>
                <?php endif; ?>
                <?php if ($filterLocation): ?>
                    <span class="filter-tag">
                        <?php echo htmlspecialchars($filterLocation); ?>
                        <a href="?<?php echo http_build_query(array_filter(['keyword' => $keyword, 'location' => '', 'type_filter' => $filterType, 'area_filter' => $filterArea, 'price_filter' => $filterPrice, 'sort' => $sort])); ?>" class="remove-tag">✕</a>
                    </span>
                <?php endif; ?>
                <?php if ($filterType): ?>
                    <span class="filter-tag">
                        <?php echo $typeMap[$filterType] ?? $filterType; ?>
                        <a href="?<?php echo http_build_query(array_filter(['keyword' => $keyword, 'location' => $filterLocation, 'type_filter' => '', 'area_filter' => $filterArea, 'price_filter' => $filterPrice, 'sort' => $sort])); ?>" class="remove-tag">✕</a>
                    </span>
                <?php endif; ?>
                <?php if ($filterArea): ?>
                    <span class="filter-tag">
                        Diện tích: <?php echo htmlspecialchars($filterArea); ?>
                        <a href="?<?php echo http_build_query(array_filter(['keyword' => $keyword, 'location' => $filterLocation, 'type_filter' => $filterType, 'area_filter' => '', 'price_filter' => $filterPrice, 'sort' => $sort])); ?>" class="remove-tag">✕</a>
                    </span>
                <?php endif; ?>
                <?php if ($filterPrice): ?>
                    <span class="filter-tag">
                        Giá: <?php echo htmlspecialchars($filterPrice); ?>
                        <a href="?<?php echo http_build_query(array_filter(['keyword' => $keyword, 'location' => $filterLocation, 'type_filter' => $filterType, 'area_filter' => $filterArea, 'price_filter' => '', 'sort' => $sort])); ?>" class="remove-tag">✕</a>
                    </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Apartment Grid -->
            <div class="listings-grid">
                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <?php while($item = mysqli_fetch_assoc($result)): ?>
                        <a href="detail.php?id=<?php echo $item['id']; ?>" class="listing-card-link">
                            <div class="listing-card">
                                <div class="listing-card-img">
                                    <?php
                                    $thumb = !empty($item['image_url']) ? "../uploads/" . $item['image_url'] : 'https://via.placeholder.com/400x250?text=No+Image';
                                    ?>
                                    <img src="<?php echo $thumb; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                    <span class="listing-badge <?php echo $item['type'] == 'Cho thuê' ? 'badge-rent' : 'badge-sell'; ?>">
                                        <?php echo $item['type'] == 'Cho thuê' ? 'THUÊ' : 'BÁN'; ?>
                                    </span>
                                </div>
                                <div class="listing-card-body">
                                    <div class="listing-price">
                                        <?php echo formatPrice($item['price']); ?>
                                    </div>
                                    <div class="listing-title"><?php echo htmlspecialchars($item['title']); ?></div>
                                    <div class="listing-address">
                                        📍 <?php echo htmlspecialchars(($item['district'] ?? 'N/A') . ', ' . ($item['province'] ?? 'TP. HCM')); ?>
                                    </div>
                                    <div class="listing-meta">
                                        <span><b><?php echo $item['area']; ?></b> m²</span>
                                        <span><b><?php echo $item['bedroom']; ?></b> PN</span>
                                        <span><b><?php echo $item['bathroom']; ?></b> WC</span>
                                    </div>
                                    <div class="listing-meta-sub">
                                        <span><?php echo htmlspecialchars($item['furniture'] ?? 'Cơ bản'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-results">
                        <div class="no-results-icon">🏠</div>
                        <h3>Không tìm thấy căn hộ nào</h3>
                        <p>Không có kết quả phù hợp with bộ lọc của bạn. Hãy thử điều chỉnh bộ lọc.</p>
                        <a href="listings.php" class="dark-btn">Xem tất cả căn hộ</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="pagination-btn">
                        ← Trước
                    </a>
                <?php endif; ?>

                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                if ($startPage > 1) {
                    echo '<a href="?'.http_build_query(array_merge($_GET, ['page' => 1])).'" class="pagination-btn">1</a>';
                    if ($startPage > 2) echo '<span class="pagination-ellipsis">...</span>';
                }
                
                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                       class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; 
                
                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) echo '<span class="pagination-ellipsis">...</span>';
                    echo '<a href="?'.http_build_query(array_merge($_GET, ['page' => $totalPages])).'" class="pagination-btn">'.$totalPages.'</a>';
                }
                ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="pagination-btn">
                        Sau →
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- FOOTER -->
<footer class="footer">
    <div class="footer-grid">
        <div>
            <h4><?php echo $ten_trang; ?></h4>
            <p class="footer-desc"><?php echo $ten_trang; ?> là nền tảng chuyên đăng tin và tìm kiếm căn hộ, hỗ trợ người dùng nhanh chóng kết nối nhu cầu cho thuê và chuyển nhượng.</p>
        </div>
        <div>
            <h4>Liên kết nhanh</h4>
            <ul>
                <li><a href="index.php">Trang chủ</a></li>
                <li><a href="listings.php">Danh sách căn hộ</a></li>
                <li><a href="listings.php?type_filter=cho-thue">Cho thuê</a></li>
                <li><a href="listings.php?type_filter=mua-ban">Chuyển nhượng</a></li>
            </ul>
        </div>
        <div>
            <h4>Hỗ trợ</h4>
            <ul>
                <li><a href="post-create.php">Đăng tin</a></li>
                <li><a href="#">Hướng dẫn sử dụng</a></li>
                <li><a href="#">Chính sách bảo mật</a></li>
                <li><a href="#">Điều khoản sử dụng</a></li>
            </ul>
        </div>
        <div>
            <h4>Liên hệ</h4>
            <ul>
                <li>📍 TP. Hồ Chí Minh</li>
                <li>📞 0901 234 567</li>
                <li>📧 contact@theapartment.vn</li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?php echo date("Y"); ?> <b><?php echo $ten_trang; ?></b>. All rights reserved.</p>
    </div>
</footer>

<script>
function changeSort(sortValue) {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', sortValue);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}
</script>

</body>
</html>