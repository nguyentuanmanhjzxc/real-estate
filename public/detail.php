<?php
session_start();
include("../config/database.php");
$ten_trang = "TheApartment";
$nam_hien_tai = date("Y");

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { header("Location: index.php"); exit; }

// Lấy thông tin bài đăng
$sql = "SELECT post.*, projects.province, projects.district, projects.name AS project_name
        FROM post
        LEFT JOIN projects ON post.project_id = projects.id
        WHERE post.id = $id AND post.status = 1
        LIMIT 1";
$result = mysqli_query($conn, $sql);
$item = mysqli_fetch_assoc($result);
if (!$item) { header("Location: index.php"); exit; }

// Lấy tất cả ảnh
$sql_imgs = "SELECT image_url, is_thumbnail FROM images WHERE post_id = $id ORDER BY is_thumbnail DESC";
$result_imgs = mysqli_query($conn, $sql_imgs);
$images = [];
while ($img = mysqli_fetch_assoc($result_imgs)) { $images[] = $img; }
if (empty($images)) { $images[] = ['image_url' => '', 'is_thumbnail' => 1]; }

// Tin liên quan
$sql_related = "SELECT post.*, images.image_url, projects.province, projects.district
                FROM post
                LEFT JOIN images ON post.id = images.post_id AND images.is_thumbnail = 1
                LEFT JOIN projects ON post.project_id = projects.id
                WHERE post.status = 1 AND post.id != $id
                ORDER BY post.created_at DESC LIMIT 4";
$result_related = mysqli_query($conn, $sql_related);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($item['title']); ?> - <?php echo $ten_trang; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/detail.css">
</head>
<body>

<!-- POPUP LOGIN -->
<div class="login-popup" id="loginPopup">
    <div class="login-container">
        <span class="close-login" onclick="closeLogin()">&times;</span>
        <div class="login-left">
            <h2>Đăng nhập</h2>
            <p>Chào mừng bạn đến với <b><?php echo $ten_trang; ?></b></p><br>
            <form method="post" action="login.php">
            <input type="text" name="email" placeholder="Email">
            <input type="password" name="password" placeholder="Mật khẩu">
            <div class="forgot-text">Quên mật khẩu?</div>
            <button type="submit" class="btn-submit">Đăng nhập</button>
            </form>
            <div class="divider">HOẶC</div>
            <button class="google-login-btn">
                <img src="https://fonts.gstatic.com/s/i/productlogos/googleg/v6/24px.svg" width="18"> Tiếp tục với Google
            </button>
        </div>
        <div class="login-right">
            <h3><?php echo $ten_trang; ?></h3>
            <p>Gia nhập cộng đồng để nhận những ưu đãi bất động sản tốt nhất thị trường.</p>
        </div>
    </div>
</div>

<!-- NAVBAR -->
<nav class="navbar">
    <a href="index.php" style="text-decoration:none">
        <div class="navbar-logo"><span>The</span>Apartment</div>
    </a>
    <ul class="navbar-menu">
        <li><a href="index.php">Mua bán</a></li>
        <li><a href="index.php">Cho thuê</a></li>
        <li><a href="#">Dự án</a></li>
        <li><a href="#">Tin tức</a></li>
    </ul>
        <div class="navbar-actions">
        <?php if(isset($_SESSION['user'])): ?>

            <div class="user-box">
                <span class="user-greeting">
                    Xin chào, <b><?php echo htmlspecialchars($_SESSION['user']['name']); ?></b>
                </span>
                <a href="my-posts.php" class="admin-link-nav">Tin của tôi</a>
                <a href="post-create.php" class="btn-link-reset"><button class="btn-dang-tin">Đăng tin</button></a>
                <a href="logout.php" class="btn-logout">Đăng xuất</a>
            </div>

        <?php else: ?>
            <button class="btn-dang-nhap" onclick="openLogin()">Đăng nhập</button>
            <button class="btn-dang-tin" onclick="openLogin()">Đăng tin</button>
        <?php endif; ?>
        </div>
</nav>

<!-- SEARCH BAR -->
<div class="search-bar">
    <div class="search-box">
        <input type="text" placeholder="Tìm kiếm căn hộ, khu vực, dự án...">
        <button>Tìm kiếm</button>
    </div>
</div>

<!-- FILTER BAR -->
<div class="filter-bar">
    <select class="filter-select"><option>Loại</option><option>Mua bán</option><option>Cho thuê</option></select>
    <select class="filter-select"><option>Căn hộ chung cư</option><option>Căn hộ dịch vụ</option><option>Penthouse</option></select>
    <select class="filter-select"><option>Vũng Tàu</option><option>TP. HCM</option><option>Hà Nội</option></select>
    <select class="filter-select"><option>Diện Tích</option><option>Dưới 50m²</option><option>50–80m²</option><option>Trên 80m²</option></select>
    <select class="filter-select"><option>Môi giới chuyên nghiệp</option><option>Cá nhân</option></select>
</div>

<!-- BREADCRUMB -->
<div class="breadcrumb">
    <a href="index.php">Trang chủ</a>
    <span class="sep">›</span>
    <a href="index.php">tổng trang</a>
    <span class="sep">›</span>
    <a href="#">khu nhận sao / đề tở ra được trang này</a>
    <span class="sep">›</span>
    <span class="bc-current"><?php echo htmlspecialchars(mb_strimwidth($item['title'], 0, 50, '...')); ?></span>
</div>

<!-- MAIN LAYOUT -->
<div class="detail-layout">

    <!-- LEFT -->
    <div>
        <!-- GALLERY -->
        <div class="gallery-wrap">
            <div class="gallery-main-img">
                <?php
                    $base = "../uploads/";
                    $first = !empty($images[0]['image_url']) ? $base . $images[0]['image_url'] : 'https://via.placeholder.com/800x430?text=No+Image';
                ?>
                <img src="<?php echo $first; ?>" id="mainImg" alt="Ảnh căn hộ">

                <div class="g-badges">
                    <span class="g-badge <?php echo $item['type'] == 'Cho thuê' ? 'rent' : 'sell'; ?>">
                        <?php echo $item['type'] == 'Cho thuê' ? 'CHO THUÊ' : 'CHUYỂN NHƯỢNG'; ?>
                    </span>
                    <?php if (!empty($item['project_name'])): ?>
                    <span class="g-badge proj"><?php echo htmlspecialchars($item['project_name']); ?></span>
                    <?php endif; ?>
                </div>

                <span class="g-counter" id="gCounter">1 / <?php echo count($images); ?></span>

                <?php if (count($images) > 1): ?>
                <button class="g-arrow prev" onclick="changeImg(-1)">&#8249;</button>
                <button class="g-arrow next" onclick="changeImg(1)">&#8250;</button>
                <?php endif; ?>
            </div>

            <div class="gallery-thumbs">
                <?php foreach ($images as $i => $img): ?>
                <?php $src = !empty($img['image_url']) ? $base . $img['image_url'] : 'https://via.placeholder.com/80x54'; ?>
                <div class="thumb <?php echo $i === 0 ? 'active' : ''; ?>" onclick="setImg(<?php echo $i; ?>)">
                    <img src="<?php echo $src; ?>" alt="ảnh <?php echo $i+1; ?>">
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- THÔNG TIN CĂN HỘ -->
        <div class="info-block">
            <div class="type-pill">
                <?php echo $item['type'] == 'Cho thuê' ? '🏠 CHO THUÊ' : '🔑 CHUYỂN NHƯỢNG'; ?>
            </div>
            <h1 class="info-title"><?php echo htmlspecialchars($item['title']); ?></h1>
            <div class="info-addr">📍 <?php echo htmlspecialchars(($item['district'] ?? 'N/A') . ', ' . ($item['province'] ?? 'TP. Hồ Chí Minh')); ?></div>

            <div class="price-row">
                <div>
                    <div class="price-main">
                        <?php
                            if ($item['price'] >= 1000000000)
                                echo number_format($item['price'] / 1000000000, 1) . ' Tỷ đồng';
                            else
                                echo number_format($item['price'] / 1000000, 0, ',', '.') . ' Triệu đồng';
                        ?>
                    </div>
                    <div class="price-sub">
                        <?php
                            if ($item['type'] == 'Cho thuê') echo 'Giá thuê / tháng';
                            elseif ($item['area'] > 0) echo '≈ ' . number_format(($item['price'] / 1000000) / $item['area'], 1) . ' Triệu/m²';
                        ?>
                    </div>
                </div>
                <div class="area-pill">
                    <strong><?php echo $item['area']; ?> m²</strong>
                    <span>Diện tích</span>
                </div>
            </div>

            <div class="specs-grid">
                <div class="spec"><div class="spec-ico">🛏</div><div><div class="spec-lbl">Phòng ngủ</div><div class="spec-val"><?php echo $item['bedroom']; ?> phòng</div></div></div>
                <div class="spec"><div class="spec-ico">🚿</div><div><div class="spec-lbl">Phòng tắm</div><div class="spec-val"><?php echo $item['bathroom']; ?> phòng</div></div></div>
                <div class="spec"><div class="spec-ico">🪑</div><div><div class="spec-lbl">Nội thất</div><div class="spec-val"><?php echo htmlspecialchars($item['furniture']); ?></div></div></div>
                <div class="spec"><div class="spec-ico">🏗</div><div><div class="spec-lbl">Dự án</div><div class="spec-val"><?php echo htmlspecialchars($item['project_name'] ?? 'N/A'); ?></div></div></div>
                <?php if (!empty($item['direction'])): ?>
                <div class="spec"><div class="spec-ico">🧭</div><div><div class="spec-lbl">Hướng</div><div class="spec-val"><?php echo htmlspecialchars($item['direction']); ?></div></div></div>
                <?php endif; ?>
                <?php if (!empty($item['floor'])): ?>
                <div class="spec"><div class="spec-ico">🏢</div><div><div class="spec-lbl">Tầng</div><div class="spec-val">Tầng <?php echo htmlspecialchars($item['floor']); ?></div></div></div>
                <?php endif; ?>
            </div>
        </div>
    
        <!-- THÔNG TIN CHI TIẾT -->
                    <div class="card-block">
    <div class="block-title">Thông tin chi tiết</div>

    <!-- 🔽 ĐOẠN VĂN (CHẮC CHẮN HIỆN) -->
    <div style="margin-bottom:12px; font-size:14px; line-height:1.6;">
        <?php
            $desc = $item['description'] ?? $item['content'] ?? '';

            if (!empty($desc)) {
                echo nl2br(htmlspecialchars($desc));
            } else {
                echo "Căn hộ có vị trí thuận tiện, không gian thoáng mát, phù hợp để ở hoặc đầu tư lâu dài.";
            }
        ?>
    </div>
    <!-- 🔼 -->

    <div class="detail-table">
        <div><span>Loại hình</span><b><?php echo $item['type']; ?></b></div>
        <div><span>Diện tích</span><b><?php echo $item['area']; ?> m²</b></div>
        <div><span>Giá</span><b><?php echo number_format($item['price']); ?> VNĐ</b></div>
        <div><span>Phòng ngủ</span><b><?php echo $item['bedroom']; ?></b></div>
        <div><span>Phòng tắm</span><b><?php echo $item['bathroom']; ?></b></div>
        <div><span>Nội thất</span><b><?php echo $item['furniture']; ?></b></div>
        <div><span>Hướng nhà</span><b><?php echo $item['direction'] ?? 'N/A'; ?></b></div>
        <div><span>Tầng</span><b><?php echo $item['floor'] ?? 'N/A'; ?></b></div>
        <div><span>Dự án</span><b><?php echo $item['project_name'] ?? 'N/A'; ?></b></div>
        <div><span>Khu vực</span><b><?php echo $item['district'] . ', ' . $item['province']; ?></b></div>
    </div>
</div>

<!-- TIỆN ÍCH -->
<div class="card-block">
    <div class="block-title">Tiện ích xung quanh</div>

    <div class="amenities">
        <span>🏫 Gần trường học</span>
        <span>🏥 Gần bệnh viện</span>
        <span>🛒 Siêu thị</span>
        <span>🏋️ Phòng gym</span>
        <span>🌳 Công viên</span>
        <span>☕ Quán cafe</span>
        <span>🍽 Nhà hàng</span>
        <span>🚗 Bãi đỗ xe</span>
    </div>
</div>

<!-- LÝ DO CHỌN -->
<div class="card-block">
    <div class="block-title">Vì sao nên chọn căn hộ này?</div>

    <ul class="why-list">
        <li>✔ Vị trí trung tâm, di chuyển thuận tiện</li>
        <li>✔ Giá tốt hơn khu vực</li>
        <li>✔ Nội thất đầy đủ, vào ở ngay</li>
        <li>✔ Pháp lý rõ ràng</li>
        <li>✔ Khu dân cư an ninh</li>
    </ul>
</div>

<!-- PHÁP LÝ -->
<div class="card-block">
    <div class="block-title">Thông tin pháp lý</div>

    <div class="legal-box">
        <p>📄 Sổ hồng riêng, sang tên nhanh chóng</p>
        <p>📑 Không tranh chấp, không quy hoạch</p>
        <p>🏦 Hỗ trợ vay ngân hàng</p>
    </div>
</div>

        <!-- BẢN ĐỒ -->
        <div class="card-block">
            <div class="block-title">Xem vị trí căn hộ ở trên bản đồ</div>
            <div class="map-frame">
                <?php $mq = urlencode(($item['district'] ?? '') . ' ' . ($item['province'] ?? 'TP. Ho Chi Minh') . ' Vietnam'); ?>
                <iframe src="https://maps.google.com/maps?q=<?php echo $mq; ?>&output=embed" allowfullscreen loading="lazy"></iframe>
            </div>
        </div>
    </div>

    <!-- SIDEBAR -->
    <div class="sidebar">

        <!-- LIÊN HỆ -->
        <div class="s-card">
            <div class="block-title" style="margin-bottom:14px">Liên hệ tư vấn</div>
            <div class="contact-head">
                <div class="c-avatar"><?php echo strtoupper(mb_substr($item['contact_name'] ?? 'M', 0, 1)); ?></div>
                <div>
                    <div class="c-name"><?php echo htmlspecialchars($item['contact_name'] ?? 'Nguyễn Tuấn Mạnh'); ?></div>
                    <div class="c-role">✅ Môi giới</div>
                    <div class="c-stars">⭐ 5.0 · 12 đánh giá</div>
                </div>
            </div>
            <button class="btn-cta primary" onclick="return requireLogin()">
            <?php echo htmlspecialchars($item['contact_phone'] ?? 'Xem số điện thoại'); ?>
            </button>
            <button class="btn-cta ghost" onclick="return requireLogin()">✉️ Nhắn tin</button>
        </div>

        <!-- ĐÁNH GIÁ -->
        <div class="s-card">
            <div class="block-title" style="margin-bottom:14px">Khách hàng đánh giá</div>
            <div class="review">
                <div class="r-avatar">T</div>
                <div>
                    <div class="r-name">Trần Minh Tú</div>
                    <div class="r-stars">★★★★★</div>
                    <div class="r-text">Căn hộ đẹp, vị trí thuận tiện, môi giới nhiệt tình!</div>
                </div>
            </div>
            <div class="review">
                <div class="r-avatar" style="background:linear-gradient(135deg,#1a9e5c,#34d399)">L</div>
                <div>
                    <div class="r-name">Lê Thị Hương</div>
                    <div class="r-stars">★★★★☆</div>
                    <div class="r-text">Nội thất mới, không gian thoáng đãng. Rất hài lòng.</div>
                </div>
            </div>
        </div>

        <!-- BÌNH LUẬN -->
        <div class="s-card">
            <div class="block-title" style="margin-bottom:4px">Bình luận</div>
            <div class="cmt-row">
                <input type="text" placeholder="Bình luận..." onclick="return requireLogin()">
                <button class="btn-send" onclick="return requireLogin()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </div>
        </div>
    </div>

</div><!-- /detail-layout -->

<!-- TIN TƯƠNG TỰ -->
<div class="related-wrap">
    <h2 class="khoi-tieude">Các tin đăng tương tự</h2>
    <div class="grid-4">
        <?php while ($rel = mysqli_fetch_assoc($result_related)): ?>
        <?php $rt = !empty($rel['image_url']) ? "../uploads/" . $rel['image_url'] : 'https://via.placeholder.com/400x220?text=No+Image'; ?>
        <a href="detail.php?id=<?php echo $rel['id']; ?>" style="text-decoration:none;color:inherit;display:block">
            <div class="card">
                <div class="card-img">
                    <img src="<?php echo $rt; ?>" alt="<?php echo htmlspecialchars($rel['title']); ?>">
                    <span class="badge"><?php echo $rel['type'] == 'Cho thuê' ? 'THUÊ' : 'CHUYỂN NHƯỢNG'; ?></span>
                </div>
                <div class="card-body">
                    <div class="price"><?php echo $rel['price'] >= 1000000000 ? ($rel['price'] / 1000000000) . ' Tỷ' : number_format($rel['price'] / 1000000) . ' Triệu'; ?></div>
                    <div class="title"><?php echo htmlspecialchars($rel['title']); ?></div>
                    <div class="address">📍 <?php echo htmlspecialchars(($rel['district'] ?? 'N/A') . ', ' . ($rel['province'] ?? 'TP. HCM')); ?></div>
                    <div class="meta"><?php echo $rel['area']; ?> m² · <?php echo $rel['bedroom']; ?> PN · <?php echo $rel['bathroom']; ?> WC</div>
                </div>
            </div>
        </a>
        <?php endwhile; ?>
    </div>
</div>

<!-- FOOTER -->
<footer class="footer">
    <div class="footer-grid">
        <div><h4><?php echo $ten_trang; ?></h4><p class="footer-desc"><?php echo $ten_trang; ?> là nền tảng chuyên đăng tin và tìm kiếm căn hộ, hỗ trợ kết nối nhu cầu cho thuê và chuyển nhượng nhanh chóng.</p></div>
        <div><h4>Liên kết nhanh</h4><ul><li><a href="index.php">Trang chủ</a></li><li><a href="#">Căn hộ mới</a></li><li><a href="#">Cho thuê</a></li><li><a href="#">Chuyển nhượng</a></li></ul></div>
        <div><h4>Hỗ trợ</h4><ul><li><a href="#">Đăng tin</a></li><li><a href="#">Hướng dẫn sử dụng</a></li><li><a href="#">Chính sách bảo mật</a></li><li><a href="#">Điều khoản sử dụng</a></li></ul></div>
        <div><h4>Liên hệ</h4><ul><li>📍 TP. Hồ Chí Minh</li><li>📞 0901 234 567</li><li>📧 contact@theapartment.vn</li></ul></div>
    </div>
    <div class="footer-bottom"><p>&copy; <?php echo $nam_hien_tai; ?> <b><?php echo $ten_trang; ?></b>. All rights reserved.</p></div>
</footer>

<script>
// GALLERY
const imgs = <?php
    $srcs = [];
    foreach ($images as $img) {
        $srcs[] = !empty($img['image_url']) ? '../uploads/' . $img['image_url'] : 'https://via.placeholder.com/800x430?text=No+Image';
    }
    echo json_encode($srcs);
?>;
let cur = 0;

function setImg(idx) {
    cur = idx;
    const el = document.getElementById('mainImg');
    el.style.opacity = '0';
    setTimeout(() => { el.src = imgs[idx]; el.style.opacity = '1'; }, 180);
    document.getElementById('gCounter').textContent = (idx + 1) + ' / ' + imgs.length;
    document.querySelectorAll('.thumb').forEach((t, i) => t.classList.toggle('active', i === idx));
    document.querySelectorAll('.thumb')[idx]?.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
}

function changeImg(dir) {
    let n = cur + dir;
    if (n < 0) n = imgs.length - 1;
    if (n >= imgs.length) n = 0;
    setImg(n);
}

document.addEventListener('keydown', e => {
    if (e.key === 'ArrowLeft') changeImg(-1);
    if (e.key === 'ArrowRight') changeImg(1);
});

// POPUP
function openLogin() { document.getElementById('loginPopup').style.display = 'flex'; }
function closeLogin() { document.getElementById('loginPopup').style.display = 'none'; }
document.getElementById('loginPopup').addEventListener('click', function(e) { if (e.target === this) closeLogin(); });


const isLoggedIn = <?php echo isset($_SESSION['user']) ? 'true' : 'false'; ?>;

function requireLogin() {
    if (!isLoggedIn) {
        openLogin();
        return false;
    }
    return true;
}
</script>
</body>
</html>