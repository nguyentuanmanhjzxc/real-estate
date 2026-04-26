<?php
session_start();
include(__DIR__ . '/../config/database.php');

if (empty($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

$ten_trang = 'TheApartment';
$user = $_SESSION['user'];
$roleId = (int)($user['role'] ?? 0);

$projects = mysqli_query($conn, "SELECT id, name, district, province FROM projects ORDER BY name ASC LIMIT 100");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng tin căn hộ - <?php echo $ten_trang; ?></title>
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
        <li><a href="my-posts.php">Tin của tôi</a></li>
        <li><a href="index.php">Trang chủ</a></li>
    </ul>
    <div class="navbar-actions user-area">
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
                        <?php if ($roleId === 1): ?>
                            <a href="../admin/dashboard.php">Quay lại trang admin</a>
                        <?php endif; ?>
                        <a href="../modules/auth/logout.php">Đăng xuất</a>
                    </div>
                </div>

            <?php else: ?>
            <button class="btn-dang-nhap" onclick="openLogin()">Đăng nhập</button>
            <button class="btn-dang-tin" onclick="openLogin()">Đăng tin</button>
        <?php endif; ?>
    </div>
</nav>

<div class="page-shell compact-post-page">
    <div class="posting-layout">
        <form class="post-form" action="create-post-action.php" method="post" enctype="multipart/form-data">
            <section class="post-section">
                <div class="section-head compact-head">
                    <div>
                        <h3>Thiết lập tin đăng</h3>
                        <p>Chọn thông tin cơ bản để bắt đầu đăng tin.</p>
                    </div>
                    <span class="step-badge">01</span>
                </div>
                <div class="form-grid three">
                    <div class="field">
                        <label>Mục đích</label>
                        <select name="type">
                            <option value="Chuyển nhượng căn hộ">Chuyển nhượng căn hộ</option>
                            <option value="Cho thuê căn hộ">Cho thuê căn hộ</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Loại người đăng</label>
                        <input type="text" value="<?php echo htmlspecialchars($roleId === 2 ? 'Môi giới / chuyên nghiệp' : 'Chính chủ / khách hàng'); ?>" readonly>
                    </div>
                    <div class="field">
                        <label>Gói hiển thị</label>
                        <select name="package">
                            <option value="normal">Tin thường</option>
                            <option value="featured">Tin nổi bật</option>
                            <option value="premium">Tin ưu tiên</option>
                        </select>
                    </div>
                </div>
            </section>

            <section class="post-section">
                <div class="section-head">
                    <div>
                        <h3>Thông tin căn hộ</h3>
                        <p>Nhập các thông tin chính của căn hộ.</p>
                    </div>
                    <span class="step-badge">02</span>
                </div>
                <div class="form-grid">
                    <div class="field full">
                        <label>Tiêu đề tin đăng *</label>
                        <input type="text" name="title" placeholder="Ví dụ: Căn hộ 2PN full nội thất tại Quận 2, view sông, 72m²" id="preview-title-input" required>
                                            </div>
                    <div class="field">
                        <label>Dự án / toà nhà</label>
                        <select name="project_id">
                            <option value="0">Chọn dự án</option>
                            <?php if ($projects && mysqli_num_rows($projects) > 0): ?>
                                <?php while ($project = mysqli_fetch_assoc($projects)): ?>
                                    <option value="<?php echo $project['id']; ?>"><?php echo htmlspecialchars($project['name'] . ' - ' . ($project['district'] ?? '') . ', ' . ($project['province'] ?? '')); ?></option>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <option>Dữ liệu dự án sẽ hiển thị ở đây</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Khu vực hiển thị</label>
                        <input type="text" name="location" placeholder="Ví dụ: Bình Thạnh, TP.HCM" id="preview-location-input">
                    </div>
                    <div class="field">
                        <label>Diện tích *</label>
                        <input type="number" name="area" placeholder="m²" id="preview-area-input" step="0.1" min="0" required>
                    </div>
                    <div class="field">
                        <label>Phòng ngủ</label>
                        <select name="bedroom" id="preview-bedroom-input">
                            <option value="1 phòng ngủ">1 phòng ngủ</option>
                            <option value="2 phòng ngủ" selected>2 phòng ngủ</option>
                            <option value="3 phòng ngủ">3 phòng ngủ</option>
                            <option value="4+ phòng ngủ">4+ phòng ngủ</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Phòng tắm</label>
                        <select name="bathroom" id="preview-bathroom-input">
                            <option value="1 phòng tắm">1 phòng tắm</option>
                            <option value="2 phòng tắm" selected>2 phòng tắm</option>
                            <option value="3 phòng tắm">3 phòng tắm</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Tầng</label>
                        <input type="text" name="floor" placeholder="Ví dụ: Tầng 12">
                    </div>
                    <div class="field">
                        <label>Hướng ban công / cửa chính</label>
                        <select name="direction">
                            <option value="Đông Nam">Đông Nam</option>
                            <option value="Tây Nam">Tây Nam</option>
                            <option value="Đông Bắc">Đông Bắc</option>
                            <option value="Tây Bắc">Tây Bắc</option>
                            <option value="">Không xác định</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Tình trạng nội thất</label>
                        <select name="furniture">
                            <option value="Full nội thất">Full nội thất</option>
                            <option value="Nội thất cơ bản">Nội thất cơ bản</option>
                            <option value="Nhà trống">Nhà trống</option>
                        </select>
                    </div>
                </div>
            </section>

            <section class="post-section">
                <div class="section-head">
                    <div>
                        <h3>Giá và chi phí</h3>
                        <p>Điền giá bán hoặc giá thuê của căn hộ.</p>
                    </div>
                    <span class="step-badge">03</span>
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label>Giá chính *</label>
                        <input type="number" name="price" placeholder="Nhập giá bằng VNĐ" id="preview-price-input" required>
                    </div>
                    <div class="field">
                        <label>Đơn vị</label>
                        <select name="price_unit">
                            <option value="total">Tổng giá bán</option>
                            <option value="monthly">Giá thuê / tháng</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Tiền cọc</label>
                        <input type="text" name="deposit" placeholder="Ví dụ: 2 tháng tiền thuê">
                    </div>
                    <div class="field">
                        <label>Phí quản lý / phí khác</label>
                        <input type="text" name="management_fee" placeholder="Ví dụ: 15.000đ/m², phí xe riêng">
                    </div>
                </div>
            </section>

            <section class="post-section">
                <div class="section-head">
                    <div>
                        <h3>Hình ảnh căn hộ</h3>
                        <p>Chọn ảnh thật của căn hộ để hiển thị trong tin đăng.</p>
                    </div>
                    <span class="step-badge">04</span>
                </div>
                <div class="upload-box">
                    <input
                        type="file"
                        id="apartment-images"
                        name="apartment_images[]"
                        accept="image/*"
                        multiple
                        hidden
                    >

                    <label for="apartment-images" class="upload-dropzone" id="upload-dropzone">
                        <div class="upload-dropzone-icon">📷</div>
                        <h4>Tải ảnh căn hộ lên</h4>
                        <p>Kéo thả ảnh vào đây hoặc bấm để chọn nhiều ảnh từ máy tính.</p>
                        <span class="upload-cta">Chọn ảnh</span>
                        <div class="upload-note">Hỗ trợ nhiều ảnh • Ảnh đầu tiên sẽ là ảnh bìa • Ưu tiên JPG, PNG, WEBP</div>
                    </label>

                    <div class="selected-files-bar" id="selected-files-bar">
                        <span id="selected-files-count">Chưa chọn ảnh nào</span>
                        <button type="button" class="clear-images-btn" id="clear-images-btn">Xóa tất cả</button>
                    </div>

                    <div class="image-preview-grid" id="image-preview-grid">
                        <div class="empty-upload-state">
                            Ảnh bạn chọn sẽ hiện ở đây để kiểm tra nhanh trước khi gửi duyệt.
                        </div>
                    </div>
                </div>
            </section>

            <section class="post-section">
                <div class="section-head">
                    <div>
                        <h3>Mô tả bài đăng</h3>
                        <p>Viết mô tả ngắn gọn, rõ những điểm nổi bật của căn hộ.</p>
                    </div>
                    <span class="step-badge">05</span>
                </div>
                <div class="form-grid one">
                    <div class="field full">
                        <label>Mô tả chi tiết</label>
                        <textarea name="description" rows="6" placeholder="Gợi ý bố cục: diện tích → số phòng → nội thất → vị trí → tiện ích xung quanh → mức giá → điều kiện liên hệ."></textarea>
                    </div>
                </div>
            </section>

            <section class="post-section">
                <div class="section-head">
                    <div>
                        <h3>Thông tin liên hệ</h3>
                        <p>Nhập thông tin để khách có thể liên hệ với bạn.</p>
                    </div>
                    <span class="step-badge">06</span>
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label>Tên người liên hệ</label>
                        <input type="text" name="contact_name" value="<?php echo htmlspecialchars($user['name']); ?>">
                    </div>
                    <div class="field">
                        <label>Số điện thoại *</label>
                        <input type="text" name="contact_phone" placeholder="Nhập số điện thoại nhận liên hệ" required>
                    </div>
                    <div class="field">
                        <label>Email</label>
                        <input type="email" name="contact_email" placeholder="Nhập email nhận khách quan tâm">
                    </div>
                    <div class="field">
                        <label>Ưu tiên liên hệ</label>
                        <select name="contact_method">
                            <option value="call">Gọi điện</option>
                            <option value="zalo">Zalo</option>
                            <option value="chat">Chat trên website</option>
                        </select>
                    </div>
                </div>
                <div class="form-submit-actions">
                    <a href="my-posts.php" class="outline-btn">Hủy</a>
                    <button type="submit" class="dark-btn">Đăng tin ngay</button>
                </div>
            </section>
        </form>

        <aside class="side-stack" id="preview-box">
            <section class="side-card">
                <div class="section-head" style="margin-bottom:16px;">
                    <div>
                        <h3>Bản xem trước</h3>
                        <p>Người đăng nhìn thấy ngay bài của mình sẽ hiển thị như thế nào.</p>
                    </div>
                </div>
                <div class="preview-card">
                    <div class="preview-image" id="preview-image-box">
                        <span class="preview-badge">TIN MẪU</span>
                    </div>
                    <div class="preview-body">
                        <div class="preview-price" id="preview-price-text">3,2 tỷ</div>
                        <div class="preview-title" id="preview-title-text">Căn hộ 2PN full nội thất tại Thảo Điền, 72m²</div>
                        <div class="preview-meta">
                            <span id="preview-area-text">72 m²</span>
                            <span id="preview-bedroom-text">2 PN</span>
                            <span id="preview-bathroom-text">2 WC</span>
                            <span id="preview-location-text">Thủ Đức</span>
                        </div>
                    </div>
                </div>
            </section>
        </aside>
    </div>
</div>

<script>
(function () {
    const fileInput = document.getElementById('apartment-images');
    const dropzone = document.getElementById('upload-dropzone');
    const previewGrid = document.getElementById('image-preview-grid');
    const selectedFilesCount = document.getElementById('selected-files-count');
    const clearImagesBtn = document.getElementById('clear-images-btn');
    const previewImageBox = document.getElementById('preview-image-box');

    const titleInput = document.getElementById('preview-title-input');
    const priceInput = document.getElementById('preview-price-input');
    const areaInput = document.getElementById('preview-area-input');
    const bedroomInput = document.getElementById('preview-bedroom-input');
    const bathroomInput = document.getElementById('preview-bathroom-input');
    const locationInput = document.getElementById('preview-location-input');

    const previewTitleText = document.getElementById('preview-title-text');
    const previewPriceText = document.getElementById('preview-price-text');
    const previewAreaText = document.getElementById('preview-area-text');
    const previewBedroomText = document.getElementById('preview-bedroom-text');
    const previewBathroomText = document.getElementById('preview-bathroom-text');
    const previewLocationText = document.getElementById('preview-location-text');

    function formatCurrencyVND(value) {
        if (!value) return '3,2 tỷ';
        const number = Number(value);
        if (Number.isNaN(number)) return value;
        return new Intl.NumberFormat('vi-VN').format(number) + ' đ';
    }

    function shortBedroomText(value) {
        const match = String(value || '').match(/\d+/);
        return match ? match[0] + ' PN' : value;
    }

    function shortBathroomText(value) {
        const match = String(value || '').match(/\d+/);
        return match ? match[0] + ' WC' : value;
    }

    function renderPreviewFiles(files) {
        const imageFiles = Array.from(files || []).filter(file => file.type.startsWith('image/'));

        if (!imageFiles.length) {
            previewGrid.innerHTML = '<div class="empty-upload-state">Ảnh bạn chọn sẽ hiện ở đây để kiểm tra nhanh trước khi gửi duyệt.</div>';
            selectedFilesCount.textContent = 'Chưa chọn ảnh nào';
            previewImageBox.style.backgroundImage = '';
            return;
        }

        selectedFilesCount.textContent = imageFiles.length + ' ảnh đã chọn';
        previewGrid.innerHTML = '';

        imageFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function (event) {
                const previewItem = document.createElement('div');
                previewItem.className = 'image-preview-item';
                previewItem.innerHTML = `
                    <div class="image-preview-thumb" style="background-image:url('${event.target.result}')">
                        <span class="image-preview-badge">${index === 0 ? 'Ảnh bìa' : 'Ảnh ' + (index + 1)}</span>
                    </div>
                    <div class="image-preview-name" title="${file.name}">${file.name}</div>
                `;
                previewGrid.appendChild(previewItem);

                if (index === 0) {
                    previewImageBox.style.backgroundImage = `linear-gradient(rgba(31,41,55,.12), rgba(31,41,55,.24)), url('${event.target.result}')`;
                }
            };
            reader.readAsDataURL(file);
        });
    }

    fileInput.addEventListener('change', function () {
        renderPreviewFiles(fileInput.files);
    });

    clearImagesBtn.addEventListener('click', function () {
        fileInput.value = '';
        renderPreviewFiles([]);
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, function (event) {
            event.preventDefault();
            dropzone.classList.add('is-dragover');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, function (event) {
            event.preventDefault();
            dropzone.classList.remove('is-dragover');
        });
    });

    dropzone.addEventListener('drop', function (event) {
        const files = event.dataTransfer.files;
        const dataTransfer = new DataTransfer();
        Array.from(files).forEach(file => dataTransfer.items.add(file));
        fileInput.files = dataTransfer.files;
        renderPreviewFiles(fileInput.files);
    });

    if (titleInput) {
        titleInput.addEventListener('input', function () {
            previewTitleText.textContent = titleInput.value.trim() || 'Căn hộ 2PN full nội thất tại Thảo Điền, 72m²';
        });
    }

    if (priceInput) {
        priceInput.addEventListener('input', function () {
            previewPriceText.textContent = formatCurrencyVND(priceInput.value.trim());
        });
    }

    if (areaInput) {
        areaInput.addEventListener('input', function () {
            previewAreaText.textContent = areaInput.value.trim() ? areaInput.value.trim() + ' m²' : '72 m²';
        });
    }

    if (bedroomInput) {
        bedroomInput.addEventListener('change', function () {
            previewBedroomText.textContent = shortBedroomText(bedroomInput.value) || '2 PN';
        });
    }

    if (bathroomInput) {
        bathroomInput.addEventListener('change', function () {
            previewBathroomText.textContent = shortBathroomText(bathroomInput.value) || '2 WC';
        });
    }

    if (locationInput) {
        locationInput.addEventListener('input', function () {
            previewLocationText.textContent = locationInput.value.trim() || 'Thủ Đức';
        });
    }
})();
</script>
</body>
</html>
