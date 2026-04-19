<?php
include(__DIR__ . "/../config/database.php");
session_start();

// REGISTER
$register_status = $_SESSION['register_status'] ?? '';
$register_msg = $_SESSION['register_msg'] ?? '';
unset($_SESSION['register_status'], $_SESSION['register_msg']);

// LOGIN
$login_status = $_SESSION['login_status'] ?? '';
$login_msg = $_SESSION['login_msg'] ?? '';
unset($_SESSION['login_status'], $_SESSION['login_msg']);

$nam_hien_tai = date("Y");
$ten_trang = "TheApartment";
$isAdmin = !empty($_SESSION['user']) && (int)($_SESSION['user']['role'] ?? 0) === 1;

$keyword = trim($_GET['keyword'] ?? '');
$filterLocation = trim($_GET['location'] ?? '');
$filterType = trim($_GET['type_filter'] ?? '');
$filterArea = trim($_GET['area_filter'] ?? '');
$filterPrice = trim($_GET['price_filter'] ?? '');
$hasSearchFilters = $keyword !== '' || $filterLocation !== '' || $filterType !== '' || $filterArea !== '' || $filterPrice !== '';

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

$searchResult = null;
$searchCount = 0;
if ($hasSearchFilters) {
    $typeMap = [
        'mua-ban' => 'Chuyển nhượng',
        'cho-thue' => 'Cho thuê',
    ];

    $conditions = ["post.status = 1"];

    if ($keyword !== '') {
        $kw = mysqli_real_escape_string($conn, $keyword);
        $conditions[] = "(post.title LIKE '%{$kw}%' OR COALESCE(post.description, '') LIKE '%{$kw}%' OR COALESCE(post.content, '') LIKE '%{$kw}%' OR COALESCE(projects.name, '') LIKE '%{$kw}%' OR COALESCE(projects.district, '') LIKE '%{$kw}%' OR COALESCE(projects.province, '') LIKE '%{$kw}%')";
    }

    if ($filterLocation !== '') {
        $loc = mysqli_real_escape_string($conn, $filterLocation);
        $conditions[] = "(COALESCE(projects.district, '') LIKE '%{$loc}%' OR COALESCE(projects.province, '') LIKE '%{$loc}%' OR CONCAT(COALESCE(projects.district, ''), ', ', COALESCE(projects.province, '')) LIKE '%{$loc}%')";
    }

    if (isset($typeMap[$filterType])) {
        $typeValue = mysqli_real_escape_string($conn, $typeMap[$filterType]);
        $conditions[] = "post.type = '{$typeValue}'";
    }

    switch ($filterArea) {
        case 'duoi-50':
            $conditions[] = 'post.area < 50';
            break;
        case '50-80':
            $conditions[] = 'post.area >= 50 AND post.area <= 80';
            break;
        case '80-120':
            $conditions[] = 'post.area > 80 AND post.area <= 120';
            break;
        case 'tren-120':
            $conditions[] = 'post.area > 120';
            break;
    }

    switch ($filterPrice) {
        case 'duoi-2-ty':
            $conditions[] = 'post.price < 2000000000';
            break;
        case '2-5-ty':
            $conditions[] = 'post.price >= 2000000000 AND post.price <= 5000000000';
            break;
        case '5-10-ty':
            $conditions[] = 'post.price > 5000000000 AND post.price <= 10000000000';
            break;
        case 'tren-10-ty':
            $conditions[] = 'post.price > 10000000000';
            break;
        case 'duoi-10-trieu':
            $conditions[] = 'post.price < 10000000';
            break;
        case '10-20-trieu':
            $conditions[] = 'post.price >= 10000000 AND post.price <= 20000000';
            break;
        case 'tren-20-trieu':
            $conditions[] = 'post.price > 20000000';
            break;
    }

    $searchSql = "SELECT post.*, images.image_url, projects.province, projects.district
                  FROM post
                  LEFT JOIN images ON post.id = images.post_id AND images.is_thumbnail = 1
                  LEFT JOIN projects ON post.project_id = projects.id
                  WHERE " . implode(' AND ', $conditions) . "
                  ORDER BY post.created_at DESC
                  LIMIT 12";
    $searchResult = mysqli_query($conn, $searchSql);
    if ($searchResult) {
        $searchCount = mysqli_num_rows($searchResult);
    }
}

// Căn hộ mới nhất
$sql = "SELECT post.*, images.image_url, projects.province, projects.district
        FROM post 
        LEFT JOIN images 
            ON post.id = images.post_id AND images.is_thumbnail = 1
        LEFT JOIN projects 
            ON post.project_id = projects.id
        WHERE post.status = 1
        ORDER BY post.created_at DESC
        LIMIT 8";
$result = mysqli_query($conn, $sql);
if (!$result) die("Query lỗi: " . mysqli_errno($conn));

// Tin chuyển nhượng (BÁN)
$sql_ban = "SELECT post.*, images.image_url, projects.province, projects.district
            FROM post 
            LEFT JOIN images 
                ON post.id = images.post_id AND images.is_thumbnail = 1
            LEFT JOIN projects 
                ON post.project_id = projects.id
            WHERE post.status = 1 AND post.type = 'Chuyển nhượng'
            ORDER BY post.created_at DESC
            LIMIT 8";
$result_ban = mysqli_query($conn, $sql_ban);

// Tin cho thuê
$sql_thue = "SELECT post.*, images.image_url, projects.province, projects.district
            FROM post 
            LEFT JOIN images 
                ON post.id = images.post_id AND images.is_thumbnail = 1
            LEFT JOIN projects 
                ON post.project_id = projects.id
            WHERE post.status = 1 AND post.type = 'Cho thuê'
            ORDER BY post.created_at DESC
            LIMIT 8";
$result_thue = mysqli_query($conn, $sql_thue);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $ten_trang; ?> - Tìm kiếm căn hộ mơ ước</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>

<body>

<!-- POPUP ĐĂNG NHẬP -->
<div class="login-popup" id="loginPopup">
    <div class="login-container">
        <span class="close-login" onclick="closeLogin()">&times;</span>
        
        <div class="login-left">
            <h2>Đăng nhập</h2>
            <p>Chào mừng bạn đến với <b><?php echo $ten_trang; ?></b></p>
            <br>
            <form method="post" action="../modules/auth/login.php">
                <?php if (!empty($login_msg)): ?>
                    <div class="alert error">
                        <?php echo htmlspecialchars($login_msg); ?>
                    </div>
                <?php endif; ?>
                <div class="form-group">
                    <input type="text" name="email" placeholder="Email hoặc số điện thoại" required>
                    <input type="password" name="password" placeholder="Mật khẩu" required>
                </div>
                <div class="forgot-text">Quên mật khẩu?</div>
                <div class="admin-entry" style="margin-top: 8px;">Nếu là admin, hãy dùng đúng Gmail quản trị và mật khẩu riêng để vào dashboard.</div>
                <button type="submit" class="btn-submit">Đăng nhập</button>
            </form>

            <div class="divider">HOẶC</div>

            <button class="google-login-btn">
                <img src="https://fonts.gstatic.com/s/i/productlogos/googleg/v6/24px.svg" alt="Google" width="18">
                Tiếp tục với Google
            </button>

            <div class="register-link">
                Chưa có tài khoản? <a href="#" onclick="openRegister()">Đăng ký ngay</a><br>
                <span class="admin-entry">Admin cũng đăng nhập ngay tại form này bằng Gmail được cấp quyền.</span>
            </div>
        </div>

        <div class="login-right">
            <div class="overlay-text">
                <h3><?php echo $ten_trang; ?></h3>
                <p>Gia nhập cộng đồng <?php echo $ten_trang; ?> để nhận những ưu đãi bất động sản tốt nhất thị trường.</p>
            </div>
        </div>
    </div>
</div>

<!-- POPUP ĐĂNG KÝ -->
<div class="register-popup" id="registerPopup">
    <div class="login-container">
        <span class="close-login" onclick="closeRegister()">&times;</span>

        <div class="login-right">
            <div class="overlay-text">
                <h3><?php echo $ten_trang; ?></h3>
                <p>Tạo tài khoản tại <?php echo $ten_trang; ?> để bắt đầu hành trình tìm căn hộ lý tưởng.</p>
            </div>
        </div>

        <div class="login-left">
            <h2>Đăng ký</h2>
            <form method="post" action="../modules/auth/register.php">
                <?php if ($register_status == 'success'): ?>
                    <div class="alert success">Đăng ký thành công</div>
                <?php elseif ($register_status == 'error'): ?>
                    <div class="alert error"><?php echo htmlspecialchars($register_msg); ?></div>
                <?php endif; ?>
                <div class="form-group">
                    <input type="text" name="email" placeholder="Email hoặc SĐT">
                    <input type="password" name="password" placeholder="Mật khẩu">
                    <input type="password" name="confirm_password" placeholder="Nhập lại mật khẩu">
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" name="agree" id="agree" required>
                    <label for="agree">Tôi chấp nhận mọi điều kiện</label>
                </div>
                <button type="submit" class="btn-submit">Đăng ký</button>
            </form>

            <div class="divider">HOẶC</div>

            <button class="google-login-btn">
                <img src="https://fonts.gstatic.com/s/i/productlogos/googleg/v6/24px.svg" width="18">
                Đăng ký với Google
            </button>

            <div class="register-link">
                Đã có tài khoản? <a href="#" onclick="switchToLogin()">Đăng nhập</a>
            </div>
        </div>
    </div>
</div>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="navbar-logo"><span>The</span>Apartment</div>
    <ul class="navbar-menu">
        <li><a href="#">Mua bán</a></li>
        <li><a href="#">Cho thuê</a></li>
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
                                    <?php echo $_SESSION['user']['name']; ?> ▼
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
            <button class="btn-dang-nhap" onclick="openLogin()">Đăng nhập</button>
            <button class="btn-dang-tin" onclick="openLogin()">Đăng tin</button>
        <?php endif; ?>
        <a href="../admin/dashboard.php" class="admin-link-nav">Admin</a>
    </div>
</nav>

<!-- BANNER -->
<section class="banner">
    <h1>Hàng nghìn căn hộ đang chờ bạn tại <?php echo $ten_trang; ?></h1>
    <form method="get" action="index.php" class="home-search-form">
        <div class="hop-tim-kiem home-hero-search">
            <input type="text" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Tìm kiếm căn hộ, khu vực, dự án..." />
            <button type="submit" class="btn-tim-kiem">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <span>Tìm kiếm</span>
            </button>
        </div>

        <div class="home-search-panel">
            <div class="home-filter-grid">
                <div class="home-filter-item">
                    <label for="type_filter">Loại</label>
                    <select id="type_filter" name="type_filter" class="home-filter-select">
                        <option value="">Tất cả</option>
                        <option value="mua-ban" <?php echo $filterType === 'mua-ban' ? 'selected' : ''; ?>>Mua bán</option>
                        <option value="cho-thue" <?php echo $filterType === 'cho-thue' ? 'selected' : ''; ?>>Cho thuê</option>
                    </select>
                </div>

                <div class="home-filter-item">
                    <label for="location">Khu vực</label>
                    <select id="location" name="location" class="home-filter-select">
                        <option value="">Tất cả khu vực</option>
                        <?php foreach ($locationOptions as $locationLabel): ?>
                            <option value="<?php echo htmlspecialchars($locationLabel); ?>" <?php echo $filterLocation === $locationLabel ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($locationLabel); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="home-filter-item">
                    <label for="price_filter">Giá</label>
                    <select id="price_filter" name="price_filter" class="home-filter-select">
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

                <div class="home-filter-item">
                    <label for="area_filter">Diện tích</label>
                    <select id="area_filter" name="area_filter" class="home-filter-select">
                        <option value="">Tất cả diện tích</option>
                        <option value="duoi-50" <?php echo $filterArea === 'duoi-50' ? 'selected' : ''; ?>>Dưới 50m²</option>
                        <option value="50-80" <?php echo $filterArea === '50-80' ? 'selected' : ''; ?>>50 - 80m²</option>
                        <option value="80-120" <?php echo $filterArea === '80-120' ? 'selected' : ''; ?>>80 - 120m²</option>
                        <option value="tren-120" <?php echo $filterArea === 'tren-120' ? 'selected' : ''; ?>>Trên 120m²</option>
                    </select>
                </div>

                <div class="home-filter-actions">
                    <button type="submit" class="home-apply-btn">Áp dụng bộ lọc</button>
                    <a href="index.php" class="home-reset-btn">Đặt lại</a>
                </div>
            </div>
        </div>
    </form>
</section>

<!-- NỘI DUNG CHÍNH -->
<main class="noi-dung">

    <?php if ($hasSearchFilters): ?>
    <section class="khoi">
        <h2 class="khoi-tieude">Kết quả tìm kiếm</h2>
        <p class="search-result-note">Tìm thấy <b><?php echo (int)$searchCount; ?></b> tin phù hợp với bộ lọc của bạn.</p>

        <div class="grid-4">
            <?php if ($searchResult && mysqli_num_rows($searchResult) > 0): ?>
                <?php while($item = mysqli_fetch_assoc($searchResult)): ?>
                    <a href="detail.php?id=<?php echo $item['id']; ?>" style="text-decoration:none; color:inherit;">
                        <div class="card">
                            <div class="card-img">
                                <?php
                                    $base_img = "../uploads/";
                                    $thumb = !empty($item['image_url']) ? $base_img . $item['image_url'] : 'https://via.placeholder.com/400x250?text=No+Image';
                                ?>
                                <img src="<?php echo $thumb; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                <span class="badge <?php echo $item['type'] == 'Cho thuê' ? 'badge-rent' : 'badge-sell'; ?>">
                                    <?php echo $item['type'] == 'Cho thuê' ? 'THUÊ' : 'CHUYỂN NHƯỢNG'; ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="price">
                                    <?php
                                        if($item['price'] >= 1000000000) {
                                            echo number_format($item['price'] / 1000000000, 1) . " Tỷ";
                                        } else {
                                            echo number_format($item['price'] / 1000000, 1) . " Triệu";
                                        }
                                    ?>
                                </div>
                                <div class="title"><?php echo htmlspecialchars($item['title']); ?></div>
                                <div class="address">📍 <?php echo ($item['district'] ?? 'N/A') . ", " . ($item['province'] ?? 'TP. HCM'); ?></div>
                                <div class="meta">
                                    <span>Diện Tích: <b><?php echo $item['area']; ?></b> m²</span><br>
                                    <span>Loại Căn Hộ: <b><?php echo $item['bedroom']; ?></b> PN</span>
                                    <span><b><?php echo $item['bathroom']; ?></b> WC</span>
                                </div>
                                <div class="meta-sub">Nội thất: <?php echo $item['furniture']; ?></div>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-search-results">Không tìm thấy tin đăng phù hợp với bộ lọc bạn đã chọn.</div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- SECTION: Căn hộ mới nhất (grid thường, không slider) -->
    <section class="khoi">
        <h2 class="khoi-tieude">Căn hộ mới nhất</h2>
        <div class="grid-4">
            <?php while($item = mysqli_fetch_assoc($result)): ?>
                <a href="detail.php?id=<?php echo $item['id']; ?>" style="text-decoration:none; color:inherit;">
                    <div class="card">
                        <div class="card-img">
                            <?php
                                $base_img = "../uploads/";
                                $thumb = !empty($item['image_url']) ? $base_img . $item['image_url'] : 'https://via.placeholder.com/400x250?text=No+Image';
                            ?>
                            <img src="<?php echo $thumb; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                            <span class="badge <?php echo $item['type'] == 'Cho thuê' ? 'badge-rent' : 'badge-sell'; ?>">
                                <?php echo $item['type'] == 'Cho thuê' ? 'THUÊ' : 'CHUYỂN NHƯỢNG'; ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="price">
                                <?php
                                    if($item['price'] >= 1000000000) {
                                        echo number_format($item['price'] / 1000000000, 1) . " Tỷ";
                                    } else {
                                        echo number_format($item['price'] / 1000000, 1) . " Triệu";
                                    }
                                ?>
                            </div>
                            <div class="title"><?php echo htmlspecialchars($item['title']); ?></div>
                            <div class="address">📍 <?php echo ($item['district'] ?? 'N/A') . ", " . ($item['province'] ?? 'TP. HCM'); ?></div>
                            <div class="meta">
                                <span>Diện Tích: <b><?php echo $item['area']; ?></b> m²</span><br>
                                <span>Loại Căn Hộ: <b><?php echo $item['bedroom']; ?></b> PN</span>
                                <span><b><?php echo $item['bathroom']; ?></b> WC</span>
                            </div>
                            <div class="meta-sub">Nội thất: <?php echo $item['furniture']; ?></div>
                        </div>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>
    </section>

    <!-- SECTION: Tin chuyển nhượng (SLIDER) -->
    <section class="khoi">
        <h2 class="khoi-tieude">Tin chuyển nhượng mới đăng</h2>
        <div class="slider-wrapper">
            <button class="slider-btn left" onclick="scrollSlider('ban', -1)">❮</button>

            <div class="slider-viewport" id="slider-ban">
                <div class="slider-track">
                    <?php while($item = mysqli_fetch_assoc($result_ban)): ?>
                        <a href="detail.php?id=<?php echo $item['id']; ?>" style="text-decoration:none; color:inherit;">
                            <div class="card">
                                <div class="card-img">
                                    <?php
                                        $thumb = !empty($item['image_url']) ? "../uploads/" . $item['image_url'] : 'https://via.placeholder.com/400x250?text=No+Image';
                                    ?>
                                    <img src="<?php echo $thumb; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                    <span class="badge badge-sell">CHUYỂN NHƯỢNG</span>
                                </div>
                                <div class="card-body">
                                    <div class="price">
                                        <?php
                                            if($item['price'] >= 1000000000) {
                                                echo number_format($item['price'] / 1000000000, 1) . " Tỷ";
                                            } else {
                                                echo number_format($item['price'] / 1000000, 1) . " Triệu";
                                            }
                                        ?>
                                    </div>
                                    <div class="title"><?php echo htmlspecialchars($item['title']); ?></div>
                                    <div class="address">📍 <?php echo ($item['district'] ?? 'N/A') . ", " . ($item['province'] ?? 'TP. HCM'); ?></div>
                                    <div class="meta">
                                        <span>Diện Tích: <b><?php echo $item['area']; ?></b> m²</span><br>
                                        <span>Loại Căn Hộ: <b><?php echo $item['bedroom']; ?></b> PN</span>
                                        <span><b><?php echo $item['bathroom']; ?></b> WC</span>
                                    </div>
                                    <div class="meta-sub">Nội thất: <?php echo $item['furniture']; ?></div>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>

            <button class="slider-btn right" onclick="scrollSlider('ban', 1)">❯</button>
        </div>
    </section>

    <!-- SECTION: Tin cho thuê (SLIDER) -->
    <section class="khoi">
        <h2 class="khoi-tieude">Tin cho thuê mới đăng</h2>
        <div class="slider-wrapper">
            <button class="slider-btn left" onclick="scrollSlider('thue', -1)">❮</button>

            <div class="slider-viewport" id="slider-thue">
                <div class="slider-track">
                    <?php while($item = mysqli_fetch_assoc($result_thue)): ?>
                        <a href="detail.php?id=<?php echo $item['id']; ?>" style="text-decoration:none; color:inherit;">
                            <div class="card">
                                <div class="card-img">
                                    <?php
                                        $thumb = !empty($item['image_url']) ? "../uploads/" . $item['image_url'] : 'https://via.placeholder.com/400x250?text=No+Image';
                                    ?>
                                    <img src="<?php echo $thumb; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                    <span class="badge badge-rent">THUÊ</span>
                                </div>
                                <div class="card-body">
                                    <div class="price">
                                        <?php
                                            if($item['price'] >= 1000000000) {
                                                echo number_format($item['price'] / 1000000000, 1) . " Tỷ";
                                            } else {
                                                echo number_format($item['price'] / 1000000, 1) . " Triệu";
                                            }
                                        ?>
                                    </div>
                                    <div class="title"><?php echo htmlspecialchars($item['title']); ?></div>
                                    <div class="address">📍 <?php echo ($item['district'] ?? 'N/A') . ", " . ($item['province'] ?? 'TP. HCM'); ?></div>
                                    <div class="meta">
                                        <span>Diện Tích: <b><?php echo $item['area']; ?></b> m²</span><br>
                                        <span>Loại Căn Hộ: <b><?php echo $item['bedroom']; ?></b> PN</span>
                                        <span><b><?php echo $item['bathroom']; ?></b> WC</span>
                                    </div>
                                    <div class="meta-sub">Nội thất: <?php echo $item['furniture']; ?></div>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>

            <button class="slider-btn right" onclick="scrollSlider('thue', 1)">❯</button>
        </div>
    </section>

    <!-- GIỚI THIỆU -->
    <section class="gioi-thieu">
        <h2>Mua Bán Và Cho Thuê Căn Hộ Nhanh Chóng Trên <?php echo $ten_trang; ?></h2>
        <p class="sub">(<?php echo $ten_trang; ?> - Nền tảng đăng tin căn hộ hiện đại)</p>
        <div class="gioi-thieu-content" id="gioiThieuContent">
            <p>Không giống các nền tảng bất động sản tổng hợp, <?php echo $ten_trang; ?> tập trung vào trải nghiệm đăng tin nhanh và tìm kiếm chính xác. Người dùng không cần thao tác phức tạp mà vẫn có thể tiếp cận các căn hộ phù hợp chỉ trong thời gian ngắn.</p>
            <p>Nền tảng hỗ trợ phân loại rõ ràng giữa căn hộ cho thuê và căn hộ chuyển nhượng, giúp người dùng dễ dàng lọc và tìm đúng nhu cầu của mình. Các thông tin quan trọng như diện tích, giá, số phòng, nội thất đều được hiển thị trực quan ngay trên từng tin đăng.</p>
            <p>Ngoài ra, <?php echo $ten_trang; ?> còn hướng tới việc xây dựng một môi trường đăng tin minh bạch, nơi người dùng có thể chủ động đăng bài, chỉnh sửa và quản lý thông tin một cách dễ dàng.</p>
            <p>Với định hướng phát triển lâu dài, nền tảng sẽ tiếp tục cải tiến giao diện, tối ưu tốc độ và bổ sung các tính năng mới như tìm kiếm nâng cao, gợi ý thông minh và quản lý tin đăng hiệu quả hơn.</p>
        </div>
        <button class="btn-xem-them" onclick="toggleGioiThieu()" id="btnXemThem">Xem thêm</button>
    </section>

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
                <li><a href="#">Trang chủ</a></li>
                <li><a href="#">Căn hộ mới</a></li>
                <li><a href="#">Cho thuê</a></li>
                <li><a href="#">Chuyển nhượng</a></li>
            </ul>
        </div>
        <div>
            <h4>Hỗ trợ</h4>
            <ul>
                <li><a href="#">Đăng tin</a></li>
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
        <p>&copy; <?php echo $nam_hien_tai; ?> <b><?php echo $ten_trang; ?></b>. All rights reserved.</p>
    </div>
</footer>

<script>

function togglePopup(id, show = true) {
    document.getElementById(id).style.display = show ? "flex" : "none";
}
function openLogin()     { togglePopup("loginPopup", true); }
function closeLogin()    { togglePopup("loginPopup", false); }
function openRegister()  { togglePopup("registerPopup", true); }
function closeRegister() { togglePopup("registerPopup", false); }
function switchToLogin() { closeRegister(); openLogin(); }


const sliderState = {};
const GAP = 20;

function getVisible() {
    if (window.innerWidth <= 480) return 2;
    if (window.innerWidth <= 768) return 3;
    if (window.innerWidth <= 1200) return 4;
    return 5;
}

function initSlider(type) {
    const viewport = document.getElementById("slider-" + type);
    if (!viewport) return;

    const track = viewport.querySelector(".slider-track");
    if (!track) return;

    // Lấy tất cả thẻ <a> trực tiếp trong track (mỗi <a> bọc 1 card)
    const items = track.querySelectorAll(":scope > a");
    if (!items.length) return;

    const visible    = getVisible();
    const totalGaps  = visible - 1;
    const cardWidth  = (viewport.offsetWidth - GAP * totalGaps) / visible;

    // Set width chính xác cho mỗi item
    items.forEach(item => {
        item.style.width    = cardWidth + "px";
        item.style.flexShrink = "0";
    });

    // Reset về vị trí 0
    sliderState[type] = 0;
    track.style.transition = "none";
    track.style.transform  = "translateX(0)";
}

function scrollSlider(type, direction) {
    const viewport = document.getElementById("slider-" + type);
    if (!viewport) return;

    const track = viewport.querySelector(".slider-track");
    if (!track) return;

    const items = track.querySelectorAll(":scope > a");
    if (!items.length) return;

    const visible = getVisible();
    const total   = items.length;

    if (sliderState[type] === undefined) sliderState[type] = 0;

    sliderState[type] = Math.max(0, Math.min(
        sliderState[type] + direction,
        total - visible
    ));

    const cardWidth = items[0].offsetWidth;
    const offset    = sliderState[type] * (cardWidth + GAP);

    track.style.transition = "transform 0.4s ease";
    track.style.transform  = `translateX(-${offset}px)`;
}

// Init khi load
window.addEventListener("DOMContentLoaded", () => {
    initSlider("ban");
    initSlider("thue");
});

// Reset khi resize
window.addEventListener("resize", () => {
    initSlider("ban");
    initSlider("thue");
});

function toggleGioiThieu() {
    const content = document.getElementById("gioiThieuContent");
    const btn     = document.getElementById("btnXemThem");
    content.classList.toggle("expand");
    btn.innerText = content.classList.contains("expand") ? "Thu gọn" : "Xem thêm";
}


window.onload = function() {
    const loginStatus    = "<?php echo $login_status; ?>";
    const registerStatus = "<?php echo $register_status; ?>";
    const forceOpenLogin = <?php echo (isset($_GET["admin_login"]) || isset($_GET["open_login"])) ? "true" : "false"; ?>;

    if (forceOpenLogin || loginStatus === "error") {
        openLogin();
        return;
    }
    if (registerStatus === "success" || registerStatus === "error") {
        openRegister();
        if (registerStatus === "success") {
            setTimeout(() => { closeRegister(); openLogin(); }, 1500);
        }
    }
    window.history.replaceState({}, document.title, window.location.pathname);
};
</script>

</body>
</html>