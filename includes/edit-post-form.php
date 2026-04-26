<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa tin đăng - <?php echo $ten_trang; ?></title>
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
    </ul>
    <div class="navbar-actions user-area">
        <?php
        $avatar = !empty($_SESSION['user']['avatar'])
            ? "../uploads/avatar/" . $_SESSION['user']['avatar']
            : "https://via.placeholder.com/40";
        ?>

        <div class="user-menu">
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
    </div>
</nav>

<div class="page-shell compact-post-page">
    <div class="posting-layout">
        <form class="post-form" action="post-action.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
            
            <section class="post-section">
                <div class="section-head compact-head">
                    <div>
                        <h3>Chỉnh sửa tin đăng</h3>
                        <p>Cập nhật thông tin tin đăng của bạn.</p>
                    </div>
                    <span class="step-badge">01</span>
                </div>
                
                <div class="form-grid">
                    <div class="field full">
                        <label>Tiêu đề tin đăng</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
                    </div>
                    
                    <div class="field">
                        <label>Loại tin</label>
                        <select name="type">
                            <option value="Chuyển nhượng" <?php echo $post['type'] === 'Chuyển nhượng' ? 'selected' : ''; ?>>Chuyển nhượng</option>
                            <option value="Cho thuê" <?php echo $post['type'] === 'Cho thuê' ? 'selected' : ''; ?>>Cho thuê</option>
                        </select>
                    </div>
                    
                    <div class="field">
                        <label>Dự án / toà nhà</label>
                        <select name="project_id">
                            <option value="0">Chọn dự án</option>
                            <?php if ($projects && mysqli_num_rows($projects) > 0): ?>
                                <?php while ($project = mysqli_fetch_assoc($projects)): ?>
                                    <option value="<?php echo $project['id']; ?>" <?php echo $post['project_id'] == $project['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($project['name'] . ' - ' . ($project['district'] ?? '') . ', ' . ($project['province'] ?? '')); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="field">
                        <label>Diện tích (m²)</label>
                        <input type="number" name="area" value="<?php echo htmlspecialchars($post['area']); ?>" step="0.1" min="0">
                    </div>
                    
                    <div class="field">
                        <label>Phòng ngủ</label>
                        <select name="bedroom">
                            <option value="1" <?php echo $post['bedroom'] == 1 ? 'selected' : ''; ?>>1 phòng ngủ</option>
                            <option value="2" <?php echo $post['bedroom'] == 2 ? 'selected' : ''; ?>>2 phòng ngủ</option>
                            <option value="3" <?php echo $post['bedroom'] == 3 ? 'selected' : ''; ?>>3 phòng ngủ</option>
                            <option value="4" <?php echo $post['bedroom'] >= 4 ? 'selected' : ''; ?>>4+ phòng ngủ</option>
                        </select>
                    </div>
                    
                    <div class="field">
                        <label>Phòng tắm</label>
                        <select name="bathroom">
                            <option value="1" <?php echo $post['bathroom'] == 1 ? 'selected' : ''; ?>>1 phòng tắm</option>
                            <option value="2" <?php echo $post['bathroom'] == 2 ? 'selected' : ''; ?>>2 phòng tắm</option>
                            <option value="3" <?php echo $post['bathroom'] == 3 ? 'selected' : ''; ?>>3 phòng tắm</option>
                        </select>
                    </div>
                    
                    <div class="field">
                        <label>Tình trạng nội thất</label>
                        <select name="furniture">
                            <option value="Full nội thất" <?php echo $post['furniture'] === 'Full nội thất' ? 'selected' : ''; ?>>Full nội thất</option>
                            <option value="Nội thất cơ bản" <?php echo $post['furniture'] === 'Nội thất cơ bản' ? 'selected' : ''; ?>>Nội thất cơ bản</option>
                            <option value="Nhà trống" <?php echo $post['furniture'] === 'Nhà trống' ? 'selected' : ''; ?>>Nhà trống</option>
                        </select>
                    </div>
                </div>
            </section>

            <section class="post-section">
                <div class="section-head">
                    <div>
                        <h3>Giá và chi phí</h3>
                        <p>Điều chỉnh giá bán hoặc giá thuê.</p>
                    </div>
                    <span class="step-badge">02</span>
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label>Giá (VNĐ)</label>
                        <input type="number" name="price" value="<?php echo htmlspecialchars($post['price']); ?>" step="1000000" min="0">
                    </div>
                </div>
            </section>

            <section class="post-section">
                <div class="section-head">
                    <div>
                        <h3>Hình ảnh căn hộ</h3>
                        <p>Thêm ảnh mới hoặc giữ nguyên ảnh cũ.</p>
                    </div>
                    <span class="step-badge">03</span>
                </div>
                
                <?php if ($images && mysqli_num_rows($images) > 0): ?>
                <div class="existing-images">
                    <label>Ảnh hiện có:</label>
                    <div class="existing-images-grid">
                        <?php while ($img = mysqli_fetch_assoc($images)): ?>
                            <div class="existing-image-item">
                                <img src="../uploads/<?php echo htmlspecialchars($img['image_url']); ?>" alt="Ảnh">
                                <?php if ($img['is_thumbnail']): ?>
                                    <span class="thumb-badge">Ảnh bìa</span>
                                <?php endif; ?>
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
                        <div class="upload-note">Ảnh mới sẽ được thêm vào danh sách ảnh hiện có</div>
                    </label>
                </div>
            </section>

            <section class="post-section">
                <div class="section-head">
                    <div>
                        <h3>Mô tả bài đăng</h3>
                        <p>Cập nhật mô tả chi tiết về căn hộ.</p>
                    </div>
                    <span class="step-badge">04</span>
                </div>
                <div class="form-grid one">
                    <div class="field full">
                        <label>Mô tả chi tiết</label>
                        <textarea name="description" rows="6"><?php echo htmlspecialchars($post['description'] ?? ''); ?></textarea>
                    </div>
                </div>
            </section>

            <section class="post-section">
                <div class="section-head">
                    <div>
                        <h3>Thông tin liên hệ</h3>
                        <p>Cập nhật thông tin để khách liên hệ.</p>
                    </div>
                    <span class="step-badge">05</span>
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label>Tên người liên hệ</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['name']); ?>" readonly>
                    </div>
                    <div class="field">
                        <label>Số điện thoại</label>
                        <input type="text" name="contact_phone" value="<?php echo htmlspecialchars($post['contact_phone'] ?? ''); ?>" placeholder="Nhập số điện thoại">
                    </div>
                    <div class="field">
                        <label>Email</label>
                        <input type="email" name="contact_email" value="<?php echo htmlspecialchars($post['contact_email'] ?? ''); ?>" placeholder="Nhập email">
                    </div>
                </div>
                <div class="form-submit-actions">
                    <a href="my-posts.php" class="outline-btn">Hủy</a>
                    <button type="submit" class="dark-btn">Cập nhật</button>
                </div>
            </section>
        </form>
    </div>
</div>

<script>
(function () {
    const fileInput = document.getElementById('apartment-images');
    const dropzone = document.getElementById('upload-dropzone');
    
    fileInput.addEventListener('change', function () {
        const files = Array.from(this.files).filter(file => file.type.startsWith('image/'));
        if (files.length > 0) {
            alert('Đã chọn ' + files.length + ' ảnh. Ảnh sẽ được tải lên khi bạn lưu thay đổi.');
        }
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
        Array.from(files).forEach(file => {
            if (file.type.startsWith('image/')) {
                dataTransfer.items.add(file);
            }
        });
        fileInput.files = dataTransfer.files;
        fileInput.dispatchEvent(new Event('change'));
    });
})();
</script>

</body>
</html>