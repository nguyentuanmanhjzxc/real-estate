<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa tin đăng - <?php echo app_h($ten_trang ?? 'TheApartment'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/posting.css">
</head>
<body>
<?php
$roleId = (int)($user['role'] ?? 0);
$currentLocation = trim((string)($post['location'] ?? ''));
if ($currentLocation === '') {
    $projectDistrict = '';
    $projectProvince = '';
    if (!empty($post['project_id'])) {
        $locStmt = $conn->prepare('SELECT district, province FROM projects WHERE id = ? LIMIT 1');
        if ($locStmt) {
            $pid = (int)$post['project_id'];
            $locStmt->bind_param('i', $pid);
            $locStmt->execute();
            $locResult = $locStmt->get_result();
            $locRow = $locResult ? $locResult->fetch_assoc() : null;
            $locStmt->close();
            if ($locRow) {
                $projectDistrict = (string)($locRow['district'] ?? '');
                $projectProvince = (string)($locRow['province'] ?? '');
            }
        }
    }
    $currentLocation = trim($projectDistrict . (($projectDistrict && $projectProvince) ? ', ' : '') . $projectProvince);
}
$packageValue = $post['package'] ?? (((int)($post['is_vip'] ?? 0) === 1) ? 'featured' : 'normal');
$priceUnitValue = $post['price_unit'] ?? (($post['type'] ?? '') === 'Cho thuê' ? 'monthly' : 'total');
$contactMethodValue = $post['contact_method'] ?? 'call';
?>
<nav class="navbar">
    <a href="index.php" class="btn-link-reset"><div class="navbar-logo"><span>The</span>Apartment</div></a>
    <ul class="navbar-menu">
        <li><a href="index.php">Trang chủ</a></li>
        <li><a href="listings.php?type_filter=mua-ban">Mua bán</a></li>
        <li><a href="listings.php?type_filter=cho-thue">Cho thuê</a></li>
        <li><a href="my-posts.php">Tin của tôi</a></li>
    </ul>
    <div class="navbar-actions user-area">
        <a href="post-create.php" class="btn-link-reset"><button class="btn-dang-tin">Đăng tin mới</button></a>
        <div class="user-menu">
            <?php $avatar = !empty($user['avatar']) ? '../uploads/avatar/' . $user['avatar'] : 'https://via.placeholder.com/40'; ?>
            <div class="user-btn"><img src="<?php echo app_h($avatar); ?>" class="nav-avatar"> <?php echo app_h($user['name'] ?? 'Tài khoản'); ?> ▼</div>
            <div class="dropdown">
                <a href="profile.php">Trang cá nhân</a>
                <a href="my-posts.php">Tin của tôi</a>
                <?php if (!empty($isAdmin)): ?><a href="../admin/dashboard.php">Quay lại trang admin</a><?php endif; ?>
                <a href="../modules/auth/logout.php">Đăng xuất</a>
            </div>
        </div>
    </div>
</nav>

<div class="page-shell compact-post-page">
    <div class="list-head">
        <div>
            <h2>Chỉnh sửa tin đăng</h2>
            <p class="badge-muted">Cập nhật lại thông tin, giá, hình ảnh và liên hệ của tin đăng.</p>
        </div>
        <div class="head-actions">
            <a href="my-posts.php" class="outline-btn">← Quay lại tin của tôi</a>
            <a href="detail.php?id=<?php echo (int)$post['id']; ?>" class="dark-btn" target="_blank">Xem tin</a>
        </div>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="posting-layout single-column-edit">
        <form class="post-form" action="post-action.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="post_id" value="<?php echo (int)$post['id']; ?>">

            <section class="post-section">
                <div class="section-head compact-head">
                    <div>
                        <h3>Thiết lập tin đăng</h3>
                        <p>Điều chỉnh loại tin và gói hiển thị.</p>
                    </div>
                    <span class="step-badge">01</span>
                </div>
                <div class="form-grid three">
                    <div class="field">
                        <label>Mục đích *</label>
                        <select name="type" required>
                            <option value="Chuyển nhượng căn hộ" <?php echo ($post['type'] ?? '') === 'Chuyển nhượng' ? 'selected' : ''; ?>>Chuyển nhượng căn hộ</option>
                            <option value="Cho thuê căn hộ" <?php echo ($post['type'] ?? '') === 'Cho thuê' ? 'selected' : ''; ?>>Cho thuê căn hộ</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Loại người đăng</label>
                        <input type="text" value="<?php echo app_h($roleId === 2 ? 'Môi giới / chuyên nghiệp' : 'Chính chủ / khách hàng'); ?>" readonly>
                    </div>
                    <div class="field">
                        <label>Gói hiển thị</label>
                        <select name="package">
                            <option value="normal" <?php echo $packageValue === 'normal' ? 'selected' : ''; ?>>Tin thường</option>
                            <option value="featured" <?php echo $packageValue === 'featured' ? 'selected' : ''; ?>>Tin nổi bật</option>
                            <option value="premium" <?php echo $packageValue === 'premium' ? 'selected' : ''; ?>>Tin ưu tiên</option>
                        </select>
                    </div>
                </div>
            </section>

            <section class="post-section">
                <div class="section-head">
                    <div>
                        <h3>Thông tin căn hộ</h3>
                        <p>Cập nhật các thông số chính của căn hộ.</p>
                    </div>
                    <span class="step-badge">02</span>
                </div>
                <div class="form-grid">
                    <div class="field full">
                        <label>Tiêu đề tin đăng *</label>
                        <input type="text" name="title" value="<?php echo app_h($post['title'] ?? ''); ?>" required>
                    </div>
                    <div class="field">
                        <label>Dự án / toà nhà</label>
                        <select name="project_id">
                            <option value="0">Chọn dự án</option>
                            <?php if ($projects && mysqli_num_rows($projects) > 0): ?>
                                <?php while ($project = mysqli_fetch_assoc($projects)): ?>
                                    <option value="<?php echo (int)$project['id']; ?>" <?php echo (int)($post['project_id'] ?? 0) === (int)$project['id'] ? 'selected' : ''; ?>>
                                        <?php echo app_h($project['name'] . ' - ' . ($project['district'] ?? '') . ', ' . ($project['province'] ?? '')); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Khu vực hiển thị</label>
                        <input type="text" name="location" value="<?php echo app_h($currentLocation); ?>" placeholder="Ví dụ: Bình Thạnh, TP.HCM">
                    </div>
                    <div class="field">
                        <label>Diện tích *</label>
                        <input type="number" name="area" value="<?php echo app_h($post['area'] ?? ''); ?>" step="0.1" min="0" required>
                    </div>
                    <div class="field">
                        <label>Phòng ngủ *</label>
                        <select name="bedroom" required>
                            <?php for ($i = 1; $i <= 4; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo (int)($post['bedroom'] ?? 0) === $i ? 'selected' : ''; ?>><?php echo $i === 4 ? '4+ phòng ngủ' : $i . ' phòng ngủ'; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Phòng tắm *</label>
                        <select name="bathroom" required>
                            <?php for ($i = 1; $i <= 3; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo (int)($post['bathroom'] ?? 0) === $i ? 'selected' : ''; ?>><?php echo $i; ?> phòng tắm</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Tầng</label>
                        <input type="text" name="floor" value="<?php echo app_h($post['floor'] ?? ''); ?>" placeholder="Ví dụ: Tầng 12">
                    </div>
                    <div class="field">
                        <label>Hướng ban công / cửa chính</label>
                        <select name="direction">
                            <?php foreach (['Đông Nam', 'Tây Nam', 'Đông Bắc', 'Tây Bắc', 'Không xác định'] as $dir): ?>
                                <?php $value = $dir === 'Không xác định' ? '' : $dir; ?>
                                <option value="<?php echo app_h($value); ?>" <?php echo (string)($post['direction'] ?? '') === $value ? 'selected' : ''; ?>><?php echo app_h($dir); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Tình trạng nội thất</label>
                        <select name="furniture">
                            <?php foreach (['Đầy đủ' => 'Đầy đủ nội thất', 'Cơ bản' => 'Nội thất cơ bản', 'Trống' => 'Nhà trống'] as $furValue => $furLabel): ?>
                                <option value="<?php echo app_h($furValue); ?>" <?php echo ($post['furniture'] ?? '') === $furValue ? 'selected' : ''; ?>><?php echo app_h($furLabel); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </section>

            <section class="post-section">
                <div class="section-head">
                    <div>
                        <h3>Giá và chi phí</h3>
                        <p>Cập nhật giá bán hoặc giá thuê.</p>
                    </div>
                    <span class="step-badge">03</span>
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label>Giá chính *</label>
                        <input type="number" name="price" value="<?php echo app_h($post['price'] ?? ''); ?>" step="1000000" min="0" required>
                    </div>
                    <div class="field">
                        <label>Đơn vị</label>
                        <select name="price_unit">
                            <option value="total" <?php echo $priceUnitValue === 'total' ? 'selected' : ''; ?>>Tổng giá bán</option>
                            <option value="monthly" <?php echo $priceUnitValue === 'monthly' ? 'selected' : ''; ?>>Giá thuê / tháng</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Tiền cọc</label>
                        <input type="text" name="deposit" value="<?php echo app_h($post['deposit'] ?? ''); ?>" placeholder="Ví dụ: 2 tháng tiền thuê">
                    </div>
                    <div class="field">
                        <label>Phí quản lý / phí khác</label>
                        <input type="text" name="management_fee" value="<?php echo app_h($post['management_fee'] ?? ''); ?>" placeholder="Ví dụ: 15.000đ/m²">
                    </div>
                </div>
            </section>

            <section class="post-section">
                <div class="section-head">
                    <div>
                        <h3>Hình ảnh căn hộ</h3>
                        <p>Chọn ảnh bìa, xóa ảnh không cần thiết hoặc thêm ảnh mới.</p>
                    </div>
                    <span class="step-badge">04</span>
                </div>

                <?php if ($images && mysqli_num_rows($images) > 0): ?>
                    <div class="existing-images">
                        <label>Ảnh hiện có</label>
                        <div class="existing-images-grid enhanced-existing-images">
                            <?php mysqli_data_seek($images, 0); ?>
                            <?php while ($img = mysqli_fetch_assoc($images)): ?>
                                <div class="existing-image-item enhanced-image-item">
                                    <img src="../uploads/<?php echo app_h($img['image_url']); ?>" alt="Ảnh căn hộ">
                                    <?php if ((int)$img['is_thumbnail'] === 1): ?><span class="thumb-badge">Ảnh bìa</span><?php endif; ?>
                                    <div class="image-controls">
                                        <label><input type="radio" name="thumbnail_image_id" value="<?php echo (int)$img['id']; ?>" <?php echo (int)$img['is_thumbnail'] === 1 ? 'checked' : ''; ?>> Làm ảnh bìa</label>
                                        <label><input type="checkbox" name="delete_images[]" value="<?php echo (int)$img['id']; ?>"> Xóa</label>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="upload-box">
                    <input type="file" id="apartment-images" name="images[]" accept="image/*" multiple hidden>
                    <label for="apartment-images" class="upload-dropzone" id="upload-dropzone">
                        <div class="upload-dropzone-icon">📷</div>
                        <h4>Thêm ảnh mới</h4>
                        <p>Kéo thả ảnh vào đây hoặc bấm để chọn nhiều ảnh từ máy tính.</p>
                        <span class="upload-cta">Chọn ảnh</span>
                        <div class="upload-note">Ảnh mới sẽ được thêm vào danh sách ảnh hiện có • Tối đa 5MB/ảnh</div>
                    </label>
                    <div class="selected-files-bar" id="selected-files-bar"><span id="selected-files-count">Chưa chọn ảnh mới</span></div>
                    <div class="image-preview-grid" id="image-preview-grid"><div class="empty-upload-state">Ảnh mới bạn chọn sẽ hiện tại đây.</div></div>
                </div>
            </section>

            <section class="post-section">
                <div class="section-head">
                    <div>
                        <h3>Mô tả bài đăng</h3>
                        <p>Cập nhật mô tả rõ ràng để người xem dễ hiểu.</p>
                    </div>
                    <span class="step-badge">05</span>
                </div>
                <div class="form-grid one">
                    <div class="field full">
                        <label>Mô tả chi tiết</label>
                        <textarea name="description" rows="6" placeholder="Mô tả vị trí, nội thất, tiện ích, pháp lý, điều kiện thuê/mua..."><?php echo app_h($post['description'] ?? ''); ?></textarea>
                    </div>
                </div>
            </section>

            <section class="post-section">
                <div class="section-head">
                    <div>
                        <h3>Thông tin liên hệ</h3>
                        <p>Cập nhật thông tin để khách hàng liên hệ.</p>
                    </div>
                    <span class="step-badge">06</span>
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label>Tên người liên hệ *</label>
                        <input type="text" name="contact_name" value="<?php echo app_h($post['contact_name'] ?? ($user['name'] ?? '')); ?>" required>
                    </div>
                    <div class="field">
                        <label>Số điện thoại *</label>
                        <input type="text" name="contact_phone" value="<?php echo app_h($post['contact_phone'] ?? ''); ?>" required>
                    </div>
                    <div class="field">
                        <label>Email</label>
                        <input type="email" name="contact_email" value="<?php echo app_h($post['contact_email'] ?? ''); ?>">
                    </div>
                    <div class="field">
                        <label>Ưu tiên liên hệ</label>
                        <select name="contact_method">
                            <option value="call" <?php echo $contactMethodValue === 'call' ? 'selected' : ''; ?>>Gọi điện</option>
                            <option value="zalo" <?php echo $contactMethodValue === 'zalo' ? 'selected' : ''; ?>>Zalo</option>
                            <option value="chat" <?php echo $contactMethodValue === 'chat' ? 'selected' : ''; ?>>Chat trên website</option>
                        </select>
                    </div>
                </div>
                <div class="form-submit-actions">
                    <a href="my-posts.php" class="outline-btn">Hủy</a>
                    <button type="submit" class="dark-btn">Lưu thay đổi</button>
                </div>
            </section>
        </form>
    </div>
</div>

<script>
(function () {
    const fileInput = document.getElementById('apartment-images');
    const dropzone = document.getElementById('upload-dropzone');
    const previewGrid = document.getElementById('image-preview-grid');
    const selectedFilesCount = document.getElementById('selected-files-count');

    function renderFiles(files) {
        const imageFiles = Array.from(files).filter(file => file.type.startsWith('image/'));
        selectedFilesCount.textContent = imageFiles.length ? 'Đã chọn ' + imageFiles.length + ' ảnh mới' : 'Chưa chọn ảnh mới';
        previewGrid.innerHTML = '';
        if (!imageFiles.length) {
            previewGrid.innerHTML = '<div class="empty-upload-state">Ảnh mới bạn chọn sẽ hiện tại đây.</div>';
            return;
        }
        imageFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = e => {
                const item = document.createElement('div');
                item.className = 'image-preview-item';
                item.innerHTML = '<img src="' + e.target.result + '" alt="preview"><span>' + (index + 1) + '</span>';
                previewGrid.appendChild(item);
            };
            reader.readAsDataURL(file);
        });
    }

    fileInput.addEventListener('change', function () { renderFiles(this.files); });
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
        const dataTransfer = new DataTransfer();
        Array.from(event.dataTransfer.files).forEach(file => {
            if (file.type.startsWith('image/')) dataTransfer.items.add(file);
        });
        fileInput.files = dataTransfer.files;
        renderFiles(fileInput.files);
    });
})();
</script>
</body>
</html>
