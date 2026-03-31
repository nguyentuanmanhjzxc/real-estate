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
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $ten_trang; ?> - Tìm kiếm căn hộ mơ ước</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
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
                Chưa có tài khoản? <a href="#">Đăng ký ngay</a>
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
            
            <span class="badge <?php echo $item['type'] == 'Cho thuê' ? 'badge-rent' : 'badge-sell'; ?>">
                <?php echo $item['type'] == 'Cho thuê' ? 'THUÊ' : 'BÁN'; ?>
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
</main>

<footer class="footer">
    <p>&copy; <?php echo $nam_hien_tai . " <b>" . $ten_trang . "</b>"; ?>. All rights reserved.</p>
</footer>

<script>
function openLogin() {
    document.getElementById("loginPopup").style.display = "flex";
}
function closeLogin() {
    document.getElementById("loginPopup").style.display = "none";
}
</script>

</body>
</html>