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
        <li><a href="#">Hướng dẫn</a></li>
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
                        <a href="profile.php"> Trang cá nhân</a>
                        <a href="my-posts.php"> Tin của tôi</a>
                        <a href="../modules/auth/logout.php"> Đăng xuất</a>
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
        <form class="post-form" action="#" method="post" enctype="multipart/form-data">
            <section class="post-section">
                <div class="section-head compact-head">
                    <div>
                        <h3>Thiết lập tin đăng</h3>
                        <p>Chọn đúng loại tin ngay từ đầu để phần nhập liệu phía dưới gọn hơn và dễ duyệt hơn.</p>
                    </div>
                    <div class="head-actions compact-actions">
                        <a href="my-posts.php" class="outline-btn">Xem tin của tôi</a>
                        <a href="#preview-box" class="dark-btn">Xem bản xem trước</a>
                    </div>
                    <span class="step-badge">01</span>
                </div>
                <div class="form-grid three">
                    <div class="field">
                        <label>Mục đích</label>
                        <select>
                            <option>Chuyển nhượng căn hộ</option>
                            <option>Cho thuê căn hộ</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Loại người đăng</label>
                        <input type="text" value="<?php echo htmlspecialchars($roleId === 2 ? 'Môi giới / chuyên nghiệp' : 'Chính chủ / khách hàng'); ?>" readonly>
                    </div>
                    <div class="field">
                        <label>Gói hiển thị</label>
                        <select>
                            <option>Tin thường</option>
                            <option>Tin nổi bật</option>
                            <option>Tin ưu tiên</option>
                        </select>
                    </div>
                </div>
                <div class="toggle-row" style="margin-top:16px;">
                    <span class="option-chip active">Form gọn cho web tìm kiếm căn hộ</span>
                    <span class="option-chip">Không làm nặng kiểu cổng bất động sản tổng hợp</span>
                    <span class="option-chip">Tập trung mua / thuê</span>
                </div>
            </section>

            <section class="post-section">
                <div class="section-head">
                    <div>
                        <h3>Thông tin căn hộ</h3>
                        <p>Nhóm trường thông tin giữ vừa đủ để bài đăng nhìn chuyên nghiệp nhưng vẫn nhanh nhập.</p>
                    </div>
                    <span class="step-badge">02</span>
                </div>
                <div class="form-grid">
                    <div class="field full">
                        <label>Tiêu đề tin đăng</label>
                        <input type="text" placeholder="Ví dụ: Căn hộ 2PN full nội thất tại Quận 2, view sông, 72m²" id="preview-title-input">
                        <div class="field-hint">Nên nêu loại căn hộ + khu vực + điểm nổi bật + diện tích.</div>
                    </div>
                    <div class="field">
                        <label>Dự án / toà nhà</label>
                        <select>
                            <option>Chọn dự án</option>
                            <?php if ($projects && mysqli_num_rows($projects) > 0): ?>
                                <?php while ($project = mysqli_fetch_assoc($projects)): ?>
                                    <option><?php echo htmlspecialchars($project['name'] . ' - ' . ($project['district'] ?? '') . ', ' . ($project['province'] ?? '')); ?></option>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <option>Dữ liệu dự án sẽ hiển thị ở đây</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Khu vực hiển thị</label>
                        <input type="text" placeholder="Ví dụ: Bình Thạnh, TP.HCM" id="preview-location-input">
                    </div>
                    <div class="field">
                        <label>Diện tích</label>
                        <input type="number" placeholder="m²" id="preview-area-input">
                    </div>
                    <div class="field">
                        <label>Phòng ngủ</label>
                        <select id="preview-bedroom-input">
                            <option>1 phòng ngủ</option>
                            <option selected>2 phòng ngủ</option>
                            <option>3 phòng ngủ</option>
                            <option>4+ phòng ngủ</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Phòng tắm</label>
                        <select id="preview-bathroom-input">
                            <option>1 phòng tắm</option>
                            <option selected>2 phòng tắm</option>
                            <option>3 phòng tắm</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Tầng</label>
                        <input type="text" placeholder="Ví dụ: Tầng 12">
                    </div>
                    <div class="field">
                        <label>Hướng ban công / cửa chính</label>
                        <select>
                            <option>Đông Nam</option>
                            <option>Tây Nam</option>
                            <option>Đông Bắc</option>
                            <option>Tây Bắc</option>
                            <option>Không xác định</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Tình trạng nội thất</label>
                        <select>
                            <option>Full nội thất</option>
                            <option>Nội thất cơ bản</option>
                            <option>Nhà trống</option>
                        </select>
                    </div>
                </div>
            </section>

            <section class="post-section">
                <div class="section-head">
                    <div>
                        <h3>Giá và chi phí</h3>
                        <p>Với tin cho thuê, nên làm rõ tiền thuê, cọc và phí dịch vụ. Với tin bán, ưu tiên giá tổng và mức thương lượng.</p>
                    </div>
                    <span class="step-badge">03</span>
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label>Giá chính</label>
                        <input type="number" placeholder="Nhập giá bằng VNĐ" id="preview-price-input">
                    </div>
                    <div class="field">
                        <label>Đơn vị</label>
                        <select>
                            <option>Tổng giá bán</option>
                            <option>Giá thuê / tháng</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Tiền cọc</label>
                        <input type="text" placeholder="Ví dụ: 2 tháng tiền thuê">
                    </div>
                    <div class="field">
                        <label>Phí quản lý / phí khác</label>
                        <input type="text" placeholder="Ví dụ: 15.000đ/m², phí xe riêng">
                    </div>
                </div>
            </section>

            <section class="post-section">
                <div class="section-head">
                    <div>
                        <h3>Hình ảnh căn hộ</h3>
                        <p>Cho phép chọn nhiều ảnh, xem trước ngay trên form và ưu tiên ảnh đầu tiên làm ảnh bìa.</p>
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

                    <div class="upload-helper-grid">
                        <div class="upload-helper-item">
                            <strong>Ảnh bìa</strong>
                            <span>Nên là phòng khách hoặc góc chụp tổng thể sáng rõ.</span>
                        </div>
                        <div class="upload-helper-item">
                            <strong>Ảnh chi tiết</strong>
                            <span>Thêm phòng ngủ, bếp, nhà vệ sinh, ban công, tiện ích.</span>
                        </div>
                        <div class="upload-helper-item">
                            <strong>Lưu ý duyệt tin</strong>
                            <span>Không chèn số điện thoại hoặc watermark quá lớn lên ảnh.</span>
                        </div>
                    </div>

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
                        <p>Giữ mô tả theo thứ tự rõ ràng để người xem và admin đều dễ kiểm tra.</p>
                    </div>
                    <span class="step-badge">05</span>
                </div>
                <div class="form-grid one">
                    <div class="field full">
                        <label>Mô tả chi tiết</label>
                        <textarea placeholder="Gợi ý bố cục: diện tích → số phòng → nội thất → vị trí → tiện ích xung quanh → mức giá → điều kiện liên hệ."></textarea>
                        <div class="field-hint">Bạn có thể thêm sẵn gợi ý mô tả tự động ở bước sau để người dùng mới đăng tin đỡ bị bí.</div>
                    </div>
                </div>
                <div class="tag-row">
                    <span class="tag-chip">Gần metro</span>
                    <span class="tag-chip">Full nội thất</span>
                    <span class="tag-chip">View sông</span>
                    <span class="tag-chip">Có hầm xe</span>
                    <span class="tag-chip">Nhận nhà ngay</span>
                </div>
            </section>

            <section class="post-section">
                <div class="section-head">
                    <div>
                        <h3>Thông tin liên hệ</h3>
                        <p>Khối này tách riêng để người đăng kiểm tra lại số điện thoại và cách nhận cuộc gọi / tin nhắn.</p>
                    </div>
                    <span class="step-badge">06</span>
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label>Tên người liên hệ</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['name']); ?>">
                    </div>
                    <div class="field">
                        <label>Số điện thoại</label>
                        <input type="text" placeholder="Nhập số điện thoại nhận liên hệ">
                    </div>
                    <div class="field">
                        <label>Email</label>
                        <input type="email" placeholder="Nhập email nhận khách quan tâm">
                    </div>
                    <div class="field">
                        <label>Ưu tiên liên hệ</label>
                        <select>
                            <option>Gọi điện</option>
                            <option>Zalo</option>
                            <option>Chat trên website</option>
                        </select>
                    </div>
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

            <section class="side-card">
                <h3>Checklist duyệt tin</h3>
                <ul class="check-list">
                    <li>Tiêu đề ngắn, rõ khu vực và điểm nổi bật.</li>
                    <li>Giá nhập khớp với nội dung mô tả.</li>
                    <li>Ảnh là ảnh thật, không gắn số điện thoại lên hình.</li>
                    <li>Mỗi tin chỉ tập trung vào một căn hộ cụ thể.</li>
                    <li>Mô tả có thông tin diện tích, phòng, tiện ích, liên hệ.</li>
                </ul>
            </section>

            <section class="side-card">
                <h3>Đề xuất flow cho web của bạn</h3>
                <div class="tag-row" style="margin-top:12px;">
                    <span class="status-chip pending">Người dùng đăng nhập</span>
                    <span class="status-chip pending">Tạo tin</span>
                    <span class="status-chip pending">Admin kiểm tra</span>
                    <span class="status-chip pending">Hiển thị ngoài trang tìm kiếm</span>
                </div>
                <div class="head-actions" style="margin-top:16px;">
                    <a href="my-posts.php" class="soft-btn">Về quản lý tin</a>
                    <a href="#" class="dark-btn">Nút gửi duyệt</a>
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
