<?php
include("../config/database.php");

$nam_hien_tai = date("Y");
// 1. Đổi tên thương hiệu tại đây
$ten_trang = "TheApartment"; 

// Query dữ liệu căn hộ + ảnh đại diện
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

<div class="login-popup" id="loginPopup">
    <div class="login-container">
        <span class="close-login" onclick="closeLogin()">&times;</span>
        
        <div class="login-left">
            <h2>Đăng nhập</h2>
            <p>Chào mừng bạn đến với  <b><?php echo $ten_trang; ?></b></p>
            <br>
            <div class="form-group">
                <input type="text" placeholder="Email">
                <input type="password" placeholder="Mật khẩu">
            </div>

            <div class="forgot-text">Quên mật khẩu?</div>
            
            <button class="btn-submit">Đăng nhập</button>

            <div class="divider">HOẶC</div>

            <button class="google-login-btn">
                <img src="https://fonts.gstatic.com/s/i/productlogos/googleg/v6/24px.svg" alt="Google" width="18">
                Tiếp tục với Google
            </button>

            <div class="register-link">
                Chưa có tài khoản? <a href="#" onclick="openRegister()">Đăng ký ngay</a>
            </div>
        </div>

        <div class="login-right">
            <div class="overlay-text">
                <h3><?php echo $ten_trang?></h3>
                <p>Gia nhập cộng đồng <?php echo $ten_trang; ?> để nhận những ưu đãi bất động sản tốt nhất thị trường.</p>
            </div>
        </div>
    </div>
</div>

<div class="register-popup" id="registerPopup">
    <div class="login-container">

        <span class="close-login" onclick="closeRegister()">&times;</span>

        <!-- BÊN TRÁI (HÌNH) -->
        <div class="login-right">
            <div class="overlay-text">
                <h3><?php echo $ten_trang?></h3>
                <p>
                    Tạo tài khoản tại <?php echo $ten_trang; ?> 
                    để bắt đầu hành trình tìm căn hộ lý tưởng.
                </p>
            </div>
        </div>

        <!-- BÊN PHẢI (FORM) -->
        <div class="login-left">

            <h2>Đăng ký</h2>

            <div class="form-group">

                <input type="text" placeholder="Email hoặc SĐT">

                <input type="password" placeholder="Mật khẩu">

                <input type="password" placeholder="Nhập lại mật khẩu">

            </div>

            <div class="checkbox-group">
                <input type="checkbox" id="agree">
                    <label for="agree">
                        Tôi chấp nhận mọi điều kiện
                    </label>
            </div>

            <button class="btn-submit">
                Đăng ký
            </button>

            <div class="divider">
                HOẶC
            </div>

            <button class="google-login-btn">
                <img src="https://fonts.gstatic.com/s/i/productlogos/googleg/v6/24px.svg" width="18">
                Đăng ký với Google
            </button>

            <div class="register-link">
                Đã có tài khoản?
                <a href="#" onclick="switchToLogin()">Đăng nhập</a>
            </div>

        </div>

    </div>
</div>

<nav class="navbar">
    <div class="navbar-logo"><span>The</span>Apartment</div>
    
    <ul class="navbar-menu">
        <li><a href="#">Mua bán</a></li>
        <li><a href="#">Cho thuê</a></li>
        <li><a href="#">Dự án</a></li>
        <li><a href="#">Tin tức</a></li>
    </ul>
    
    <div class="navbar-actions">
        <button class="btn-dang-nhap" onclick="openLogin()">Đăng nhập</button>
        <button class="btn-dang-tin">Đăng tin miễn phí</button>
    </div>
</nav>

<section class="banner">
    <h1>Hàng nghìn căn hộ đang chờ bạn tại <?php echo $ten_trang; ?></h1>
    <div class="hop-tim-kiem">
    <input type="text" placeholder="Bạn muốn tìm căn hộ ở đâu?" />
    <button class="btn-tim-kiem">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <span>Tìm kiếm</span>
    </button>
</div>
</section>

<main class="noi-dung">
<section class="khoi">
<h2 class="khoi-tieude">Căn hộ mới nhất</h2>

<div class="grid-4">
<?php while($item = mysqli_fetch_assoc($result)): ?>
    <div class="card">

        <div class="card-img">
            <?php 
                $base_img = "../uploads/"; 
                $thumb = !empty($item['image_url']) ? $base_img . $item['image_url'] : 'https://via.placeholder.com/400x250?text=No+Image';
            ?>
            <img src="<?php echo $thumb; ?>" alt="<?php echo $item['title']; ?>">
            
            <span class="badge 
            <?php echo $item['type'] == 'Cho thuê' ? 'badge-rent' : 'badge-sell'; ?>">
    
            <?php 
                echo $item['type'] == 'Cho thuê' 
                    ? 'THUÊ' 
                    : 'CHUYỂN NHƯỢNG'; 
                 ?>
            </span>
        </div>

        <div class="card-body">

            <div class="price">
                <?php 
                    // Tối ưu hiển thị giá: Nếu > 1 tỷ thì chia cho 1 tỷ
                    if($item['price'] >= 1000000000) {
                        echo ($item['price'] / 1000000000) . " Tỷ";
                    } else {
                        echo number_format($item['price'] / 1000000) . " Triệu";
                    }
                ?>
            </div>

            <div class="title">
                <?php echo $item['title']; ?>
            </div>

            <div class="address">
                📍 <?php echo ($item['district'] ?? 'N/A') . ", " . ($item['province'] ?? 'TP. HCM'); ?>
            </div>

            <div class="meta">
                <span>Diện Tích: <b><?php echo $item['area']; ?></b> m²</span> <br> 
                <span>Loại Căn Hộ: <b><?php echo $item['bedroom']; ?></b> PN</span>
                <span><b><?php echo $item['bathroom']; ?></b> WC</span>
            </div>

            <div class="meta-sub">
                 Nội thất: <?php echo $item['furniture']; ?>
            </div>

        </div>

    </div>
<?php endwhile; ?>
</div>
</section>

<section class="khoi">
<h2 class="khoi-tieude">Tin chuyển nhượng mới đăng</h2>

<div class="slider-wrapper">

    <button class="slider-btn left" onclick="scrollSlider('ban', -1)">❮</button>

    <div class="grid-4 slider" id="slider-ban">
    <?php while($item = mysqli_fetch_assoc($result_ban)): ?>
        <div class="card">

            <div class="card-img">
                <?php 
                    $base_img = "../uploads/"; 
                    $thumb = !empty($item['image_url']) 
                        ? $base_img . $item['image_url'] 
                        : 'https://via.placeholder.com/400x250?text=No+Image';
                ?>
                <img src="<?php echo $thumb; ?>" alt="<?php echo $item['title']; ?>">
                <span class="badge badge-sell">BÁN</span>
            </div>

            <div class="card-body">

                <div class="price">
                    <?php 
                        if($item['price'] >= 1000000000) {
                            echo ($item['price'] / 1000000000) . " Tỷ";
                        } else {
                            echo number_format($item['price'] / 1000000) . " Triệu";
                        }
                    ?>
                </div>

                <div class="title"><?php echo $item['title']; ?></div>

                <div class="address">
                    📍 <?php echo ($item['district'] ?? 'N/A') . ", " . ($item['province'] ?? 'TP. HCM'); ?>
                </div>

                <div class="meta">
                    <span>Diện Tích: <b><?php echo $item['area']; ?></b> m²</span> <br> 
                    <span><b><?php echo $item['bedroom']; ?></b> PN</span>
                    <span><b><?php echo $item['bathroom']; ?></b> WC</span>
                </div>

                <div class="meta-sub">
                    Nội thất: <?php echo $item['furniture']; ?>
                </div>

            </div>

        </div>
    <?php endwhile; ?>
    </div>

    <button class="slider-btn right" onclick="scrollSlider('ban', 1)">❯</button>

</div>
</section>

<section class="khoi">
<h2 class="khoi-tieude">Tin cho thuê mới đăng</h2>

<div class="slider-wrapper">

    <button class="slider-btn left" onclick="scrollSlider('thue', -1)">❮</button>

    <div class="grid-4 slider" id="slider-thue">
    <?php while($item = mysqli_fetch_assoc($result_thue)): ?>
        <div class="card">

            <div class="card-img">
                <?php 
                    $base_img = "../uploads/"; 
                    $thumb = !empty($item['image_url']) 
                        ? $base_img . $item['image_url'] 
                        : 'https://via.placeholder.com/400x250?text=No+Image';
                ?>
                <img src="<?php echo $thumb; ?>" alt="<?php echo $item['title']; ?>">
                <span class="badge badge-rent">THUÊ</span>
            </div>

            <div class="card-body">

                <div class="price">
                    <?php 
                        if($item['price'] >= 1000000000) {
                            echo ($item['price'] / 1000000000) . " Tỷ";
                        } else {
                            echo number_format($item['price'] / 1000000) . " Triệu";
                        }
                    ?>
                </div>

                <div class="title"><?php echo $item['title']; ?></div>

                <div class="address">
                    📍 <?php echo ($item['district'] ?? 'N/A') . ", " . ($item['province'] ?? 'TP. HCM'); ?>
                </div>

                <div class="meta">
                    <span>Diện Tích: <b><?php echo $item['area']; ?></b> m²</span> <br> 
                    <span><b><?php echo $item['bedroom']; ?></b> PN</span>
                    <span><b><?php echo $item['bathroom']; ?></b> WC</span>
                </div>

                <div class="meta-sub">
                    Nội thất: <?php echo $item['furniture']; ?>
                </div>

            </div>

        </div>
    <?php endwhile; ?>
    </div>

    <button class="slider-btn right" onclick="scrollSlider('thue', 1)">❯</button>

</div>
</section>

<section class="gioi-thieu">
    <h2>Mua Bán Và Cho Thuê Căn Hộ Nhanh Chóng Trên <?php echo $ten_trang; ?></h2>

    <p class="sub">
        (<?php echo $ten_trang; ?> - Nền tảng đăng tin căn hộ hiện đại)
    </p>

    <div class="gioi-thieu-content" id="gioiThieuContent">
        <p>
            Không giống các nền tảng bất động sản tổng hợp, <?php echo $ten_trang; ?> 
            tập trung vào trải nghiệm đăng tin nhanh và tìm kiếm chính xác. 
            Người dùng không cần thao tác phức tạp mà vẫn có thể tiếp cận 
            các căn hộ phù hợp chỉ trong thời gian ngắn.
        </p>

        <p>
            Nền tảng hỗ trợ phân loại rõ ràng giữa căn hộ cho thuê và căn hộ chuyển nhượng,
            giúp người dùng dễ dàng lọc và tìm đúng nhu cầu của mình. 
            Các thông tin quan trọng như diện tích, giá, số phòng, nội thất 
            đều được hiển thị trực quan ngay trên từng tin đăng.
        </p>

        <p>
            Ngoài ra, <?php echo $ten_trang; ?> còn hướng tới việc xây dựng 
            một môi trường đăng tin minh bạch, nơi người dùng có thể chủ động 
            đăng bài, chỉnh sửa và quản lý thông tin một cách dễ dàng.
        </p>

<p>
    Với định hướng phát triển lâu dài, nền tảng sẽ tiếp tục cải tiến 
    giao diện, tối ưu tốc độ và bổ sung các tính năng mới như tìm kiếm nâng cao, 
    gợi ý thông minh và quản lý tin đăng hiệu quả hơn.
</p>
    </div>

    <button class="btn-xem-them" onclick="toggleGioiThieu()" id="btnXemThem">
        Xem thêm
    </button>
</section>
</main>


<footer class="footer">

  <div class="footer-grid">

    <!-- CỘT 1 -->
    <div>
      <h4><?php echo $ten_trang; ?></h4>
      <p class="footer-desc">
        <?php echo $ten_trang; ?> là nền tảng chuyên đăng tin và tìm kiếm căn hộ,
        hỗ trợ người dùng nhanh chóng kết nối nhu cầu cho thuê và chuyển nhượng.
      </p>
    </div>

    <!-- CỘT 2 -->
    <div>
      <h4>Liên kết nhanh</h4>
      <ul>
        <li><a href="#">Trang chủ</a></li>
        <li><a href="#">Căn hộ mới</a></li>
        <li><a href="#">Cho thuê</a></li>
        <li><a href="#">Chuyển nhượng</a></li>
      </ul>
    </div>

    <!-- CỘT 3 -->
    <div>
      <h4>Hỗ trợ</h4>
      <ul>
        <li><a href="#">Đăng tin</a></li>
        <li><a href="#">Hướng dẫn sử dụng</a></li>
        <li><a href="#">Chính sách bảo mật</a></li>
        <li><a href="#">Điều khoản sử dụng</a></li>
      </ul>
    </div>

    <!-- CỘT 4 -->
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
    <p>
      &copy; <?php echo $nam_hien_tai; ?> <b><?php echo $ten_trang; ?></b>. All rights reserved.
    </p>
  </div>
</footer>

<script>
function togglePopup(id, show = true) {
    document.getElementById(id).style.display = show ? "flex" : "none";
}

function openLogin() {
    togglePopup("loginPopup", true);
}

function closeLogin() {
    togglePopup("loginPopup", false);
}

function openRegister() {
    togglePopup("registerPopup", true);
}

function closeRegister() {
    togglePopup("registerPopup", false);
}

function switchToLogin() {
    closeRegister();
    openLogin();
}

function scrollSlider(type, direction) {
    const slider = document.getElementById("slider-" + type);

    const card = slider.querySelector(".card");
    if (!card) return;

    const gap = 20;
    const cardWidth = card.offsetWidth + gap;

    slider.scrollBy({
        left: direction * cardWidth,
        behavior: 'smooth'
    });
    
}

function toggleGioiThieu() {
    const content = document.getElementById("gioiThieuContent");
    const btn = document.getElementById("btnXemThem");

    content.classList.toggle("expand");

    if (content.classList.contains("expand")) {
        btn.innerText = "Thu gọn";
    } else {
        btn.innerText = "Xem thêm";
    }
}
</script>

</body>
</html>