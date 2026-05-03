<?php
require_once(__DIR__ . '/includes/bootstrap.php');
require_admin();

$pageTitle = 'Quản lý dự án';
$activeMenu = 'projects';
$pageHeading = 'Quản lý dự án / khu vực';
$pageDescription = 'Thêm, sửa, xóa các dự án bất động sản trên hệ thống.';
$projectAddressColumn = admin_table_has_column($conn, 'projects', 'address_detail') ? 'address_detail' : (admin_table_has_column($conn, 'projects', 'address') ? 'address' : '');

// Get success/error messages
$adminSuccess = $_SESSION['admin_success'] ?? '';
$adminError = $_SESSION['admin_error'] ?? '';
unset($_SESSION['admin_success'], $_SESSION['admin_error']);

// Handle add/edit project
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add') {
        $name = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
        $district = mysqli_real_escape_string($conn, $_POST['district'] ?? '');
        $province = mysqli_real_escape_string($conn, $_POST['province'] ?? '');
        $address = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
        
        if (!empty($name)) {
            if ($projectAddressColumn !== '') {
                $stmt = $conn->prepare("INSERT INTO projects (name, district, province, `{$projectAddressColumn}`) VALUES (?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("ssss", $name, $district, $province, $address);
                    if ($stmt->execute()) {
                        $_SESSION['admin_success'] = 'Đã thêm dự án thành công.';
                    } else {
                        $_SESSION['admin_error'] = 'Có lỗi xảy ra khi thêm dự án.';
                    }
                    $stmt->close();
                }
            } else {
                $stmt = $conn->prepare("INSERT INTO projects (name, district, province) VALUES (?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("sss", $name, $district, $province);
                    if ($stmt->execute()) {
                        $_SESSION['admin_success'] = 'Đã thêm dự án thành công.';
                    } else {
                        $_SESSION['admin_error'] = 'Có lỗi xảy ra khi thêm dự án.';
                    }
                    $stmt->close();
                }
            }
        } else {
            $_SESSION['admin_error'] = 'Vui lòng nhập tên dự án.';
        }
        header('Location: projects.php');
        exit();
    }
    
    if ($action === 'edit') {
        $id = (int)($_POST['edit_id'] ?? 0);
        $name = mysqli_real_escape_string($conn, $_POST['edit_name'] ?? '');
        $district = mysqli_real_escape_string($conn, $_POST['edit_district'] ?? '');
        $province = mysqli_real_escape_string($conn, $_POST['edit_province'] ?? '');
        $address = mysqli_real_escape_string($conn, $_POST['edit_address'] ?? '');
        
        if ($id > 0 && !empty($name)) {
            if ($projectAddressColumn !== '') {
                $stmt = $conn->prepare("UPDATE projects SET name = ?, district = ?, province = ?, `{$projectAddressColumn}` = ? WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("ssssi", $name, $district, $province, $address, $id);
                    if ($stmt->execute()) {
                        $_SESSION['admin_success'] = 'Đã cập nhật dự án thành công.';
                    } else {
                        $_SESSION['admin_error'] = 'Có lỗi xảy ra khi cập nhật dự án.';
                    }
                    $stmt->close();
                }
            } else {
                $stmt = $conn->prepare("UPDATE projects SET name = ?, district = ?, province = ? WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("sssi", $name, $district, $province, $id);
                    if ($stmt->execute()) {
                        $_SESSION['admin_success'] = 'Đã cập nhật dự án thành công.';
                    } else {
                        $_SESSION['admin_error'] = 'Có lỗi xảy ra khi cập nhật dự án.';
                    }
                    $stmt->close();
                }
            }
        } else {
            $_SESSION['admin_error'] = 'Vui lòng nhập đầy đủ thông tin.';
        }
        header('Location: projects.php');
        exit();
    }
}

// Get project data for editing
$editProject = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $editId);
        $stmt->execute();
        $result = $stmt->get_result();
        $editProject = $result->fetch_assoc();
        $stmt->close();
    }
}

// Get projects list
$projects = mysqli_query($conn, "
    SELECT p.*, 
           (SELECT COUNT(*) FROM post WHERE project_id = p.id) as post_count
    FROM projects p
    ORDER BY p.name ASC
");

$totalProjects = (int) get_single_value($conn, 'SELECT COUNT(*) FROM projects');
$activeProjects = (int) get_single_value($conn, 'SELECT COUNT(*) FROM projects WHERE id IN (SELECT DISTINCT project_id FROM post WHERE status = 1)');

include(__DIR__ . '/includes/header.php');

// Alert box for success/error
function adminAlert($type, $message) {
    $bg = $type === 'success' ? '#d1fae5' : '#fee2e2';
    $color = $type === 'success' ? '#065f46' : '#991b1b';
    $border = $type === 'success' ? '#a7f3d0' : '#fecaca';
    echo "<div style='background:{$bg};color:{$color};border:1px solid {$border};padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:14px;'>".h($message)."</div>";
}
?>

<?php if ($adminSuccess): ?>
    <?php adminAlert('success', $adminSuccess); ?>
<?php endif; ?>
<?php if ($adminError): ?>
    <?php adminAlert('error', $adminError); ?>
<?php endif; ?>

<?php if ($editProject): ?>
<!-- Edit Project Form -->
<section class="panel-card" style="border: 2px solid var(--primary);">
    <div class="panel-head solo">
        <div>
            <h3>Chỉnh sửa dự án</h3>
            <p>Cập nhật thông tin dự án <strong>#<?php echo (int)$editProject['id']; ?></strong>.</p>
        </div>
        <a href="projects.php" class="mini-btn" style="background: #6b7280; color: white;">Hủy chỉnh sửa</a>
    </div>
    
    <form method="POST" action="projects.php" style="display: grid; grid-template-columns: 2fr 1fr 1fr 2fr auto; gap: 12px; align-items: end;">
        <input type="hidden" name="edit_id" value="<?php echo (int)$editProject['id']; ?>">
        <div class="field">
            <label>Tên dự án *</label>
            <input type="text" name="edit_name" value="<?php echo h($editProject['name']); ?>" required>
        </div>
        <div class="field">
            <label>Quận / Huyện</label>
            <input type="text" name="edit_district" value="<?php echo h($editProject['district'] ?? ''); ?>">
        </div>
        <div class="field">
            <label>Tỉnh / Thành phố</label>
            <input type="text" name="edit_province" value="<?php echo h($editProject['province'] ?? ''); ?>">
        </div>
        <div class="field">
            <label>Địa chỉ chi tiết</label>
            <input type="text" name="edit_address" value="<?php echo h($projectAddressColumn !== '' ? ($editProject[$projectAddressColumn] ?? '') : ''); ?>">
        </div>
        <button type="submit" name="action" value="edit" class="dark-btn" style="height: 40px; padding: 0 20px;">Cập nhật</button>
    </form>
</section>
<?php else: ?>
<!-- Add New Project Form -->
<section class="panel-card">
    <div class="panel-head solo">
        <div>
            <h3>Thêm dự án mới</h3>
            <p>Điền thông tin để thêm dự án mới vào hệ thống.</p>
        </div>
    </div>
    
    <form method="POST" action="projects.php" style="display: grid; grid-template-columns: 2fr 1fr 1fr 2fr auto; gap: 12px; align-items: end;">
        <div class="field">
            <label>Tên dự án *</label>
            <input type="text" name="name" placeholder="Ví dụ: Vinhomes Central Park" required>
        </div>
        <div class="field">
            <label>Quận / Huyện</label>
            <input type="text" name="district" placeholder="Ví dụ: Bình Thạnh">
        </div>
        <div class="field">
            <label>Tỉnh / Thành phố</label>
            <input type="text" name="province" placeholder="Ví dụ: TP. HCM">
        </div>
        <div class="field">
            <label>Địa chỉ chi tiết</label>
            <input type="text" name="address" placeholder="Ví dụ: 720A Điện Biên Phủ">
        </div>
        <button type="submit" name="action" value="add" class="dark-btn" style="height: 40px; padding: 0 20px;">+ Thêm</button>
    </form>
</section>
<?php endif; ?>

<!-- Projects List -->
<section class="panel-card">
    <div class="panel-head solo">
        <div>
            <h3>Danh sách dự án</h3>
            <p>Quản lý tất cả dự án đã thêm vào hệ thống.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên dự án</th>
                    <th>Quận / Huyện</th>
                    <th>Tỉnh / Thành phố</th>
                    <th>Số tin đăng</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($projects && mysqli_num_rows($projects) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($projects)): ?>
                    <tr>
                        <td>#<?php echo (int) $row['id']; ?></td>
                        <td><strong><?php echo h($row['name']); ?></strong></td>
                        <td><?php echo h($row['district'] ?: 'N/A'); ?></td>
                        <td><?php echo h($row['province'] ?: 'N/A'); ?></td>
                        <td>
                            <span class="status-badge <?php echo $row['post_count'] > 0 ? 'success' : 'neutral'; ?>">
                                <?php echo (int) $row['post_count']; ?> tin
                            </span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a class="mini-btn dark" href="listings.php?project=<?php echo (int)$row['id']; ?>">Xem tin</a>
                                <a class="mini-btn" href="projects.php?edit=<?php echo (int)$row['id']; ?>">Sửa</a>
                                <a class="mini-btn" href="post-action.php?action=delete_project&project_id=<?php echo (int)$row['id']; ?>" style="background:#dc2626;color:white;" onclick="return confirm('Xác nhận xóa dự án này? Các tin đăng thuộc dự án sẽ không bị xóa nhưng sẽ mất liên kết.')">Xóa</a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6"><div class="empty-state small">Chưa có dự án nào trong hệ thống.</div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include(__DIR__ . '/includes/footer.php'); ?>