<?php
session_start();
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../includes/post-helpers.php');

if (empty($_SESSION['user'])) {
    $_SESSION['error'] = 'Vui lòng đăng nhập để thực hiện thao tác này.';
    header('Location: index.php?open_login=1');
    exit();
}

$userId = (int)($_SESSION['user']['id'] ?? 0);
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$postId = (int)($_POST['post_id'] ?? $_GET['post_id'] ?? 0);

function verifyPostOwnership(mysqli $conn, int $postId, int $userId)
{
    $stmt = $conn->prepare('SELECT * FROM post WHERE id = ? AND user_id = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('ii', $postId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result ? $result->fetch_assoc() : false;
    $stmt->close();
    return $post;
}

function redirectMyPosts(): void
{
    header('Location: my-posts.php');
    exit();
}

switch ($action) {
    case 'delete':
        $post = $postId > 0 ? verifyPostOwnership($conn, $postId, $userId) : false;
        if (!$post) {
            $_SESSION['error'] = 'Không thể xóa tin đăng này.';
            redirectMyPosts();
        }

        $stmt = $conn->prepare('SELECT id, image_url FROM images WHERE post_id = ?');
        if ($stmt) {
            $stmt->bind_param('i', $postId);
            $stmt->execute();
            $imgResult = $stmt->get_result();
            while ($img = $imgResult->fetch_assoc()) {
                $filePath = __DIR__ . '/../uploads/' . $img['image_url'];
                if (is_file($filePath)) {
                    @unlink($filePath);
                }
            }
            $stmt->close();
        }

        $stmt = $conn->prepare('DELETE FROM images WHERE post_id = ?');
        if ($stmt) {
            $stmt->bind_param('i', $postId);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conn->prepare('DELETE FROM post WHERE id = ? AND user_id = ?');
        if ($stmt) {
            $stmt->bind_param('ii', $postId, $userId);
            $stmt->execute();
            $deleted = $stmt->affected_rows > 0;
            $stmt->close();
            $_SESSION['success'] = $deleted ? 'Đã xóa tin đăng thành công.' : 'Không có tin đăng nào được xóa.';
        } else {
            $_SESSION['error'] = 'Không thể xóa tin đăng lúc này.';
        }
        redirectMyPosts();

    case 'toggle_status':
        $post = $postId > 0 ? verifyPostOwnership($conn, $postId, $userId) : false;
        if (!$post) {
            $_SESSION['error'] = 'Không thể cập nhật trạng thái tin đăng này.';
            redirectMyPosts();
        }

        $newStatus = ((int)$post['status'] === 1) ? 0 : 1;
        $statusSql = post_table_has_column($conn, 'post', 'updated_at')
            ? 'UPDATE post SET status = ?, updated_at = NOW() WHERE id = ? AND user_id = ?'
            : 'UPDATE post SET status = ? WHERE id = ? AND user_id = ?';
        $stmt = $conn->prepare($statusSql);
        if ($stmt) {
            $stmt->bind_param('iii', $newStatus, $postId, $userId);
            $stmt->execute();
            $stmt->close();
            $_SESSION['success'] = $newStatus === 1 ? 'Tin đăng đã được hiển thị lại.' : 'Tin đăng đã được ẩn khỏi trang tìm kiếm.';
        } else {
            $_SESSION['error'] = 'Không thể cập nhật trạng thái tin đăng.';
        }
        redirectMyPosts();

    case 'edit':
        $post = $postId > 0 ? verifyPostOwnership($conn, $postId, $userId) : false;
        if (!$post) {
            $_SESSION['error'] = 'Không thể chỉnh sửa tin đăng này.';
            redirectMyPosts();
        }

        $projects = mysqli_query($conn, 'SELECT id, name, district, province FROM projects ORDER BY name ASC LIMIT 200');
        $stmt = $conn->prepare('SELECT * FROM images WHERE post_id = ? ORDER BY is_thumbnail DESC, id ASC');
        if ($stmt) {
            $stmt->bind_param('i', $postId);
            $stmt->execute();
            $images = $stmt->get_result();
            $stmt->close();
        } else {
            $images = false;
        }

        $ten_trang = 'TheApartment';
        $user = $_SESSION['user'];
        $isAdmin = (int)($user['role'] ?? 0) === 1;
        include(__DIR__ . '/../includes/edit-post-form.php');
        exit();

    case 'update':
        $post = $postId > 0 ? verifyPostOwnership($conn, $postId, $userId) : false;
        if (!$post) {
            $_SESSION['error'] = 'Không thể cập nhật tin đăng này.';
            redirectMyPosts();
        }

        $title = trim($_POST['title'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $area = (float)($_POST['area'] ?? 0);
        $bedroomValue = normalize_room_number($_POST['bedroom'] ?? '');
        $bathroomValue = normalize_room_number($_POST['bathroom'] ?? '');
        $typeValue = normalize_post_type(trim($_POST['type'] ?? ''));
        $furnitureValue = normalize_furniture_value(trim($_POST['furniture'] ?? 'Nội thất cơ bản'));
        $floorRangeValue = normalize_floor_range(trim($_POST['floor'] ?? ''));
        $slug = unique_post_slug($conn, $title, $postId);
        $description = trim($_POST['description'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $projectId = (int)($_POST['project_id'] ?? 0);
        $contactName = trim($_POST['contact_name'] ?? ($_SESSION['user']['name'] ?? ''));
        $contactPhone = trim($_POST['contact_phone'] ?? '');
        $contactEmail = trim($_POST['contact_email'] ?? '');
        $direction = trim($_POST['direction'] ?? '');
        $floor = trim($_POST['floor'] ?? '');
        $package = trim($_POST['package'] ?? ($post['package'] ?? 'normal'));
        $priceUnit = trim($_POST['price_unit'] ?? ($post['price_unit'] ?? ($typeValue === 'Cho thuê' ? 'monthly' : 'total')));
        $deposit = trim($_POST['deposit'] ?? ($post['deposit'] ?? ''));
        $managementFee = trim($_POST['management_fee'] ?? ($post['management_fee'] ?? ''));
        $contactMethod = trim($_POST['contact_method'] ?? ($post['contact_method'] ?? 'call'));

        $errors = [];
        if ($title === '') $errors[] = 'Vui lòng nhập tiêu đề tin đăng.';
        if ($price <= 0) $errors[] = 'Vui lòng nhập giá lớn hơn 0.';
        if ($area <= 0) $errors[] = 'Vui lòng nhập diện tích lớn hơn 0.';
        if ($bedroomValue <= 0) $errors[] = 'Vui lòng chọn số phòng ngủ.';
        if ($bathroomValue <= 0) $errors[] = 'Vui lòng chọn số phòng tắm.';
        if ($contactName === '') $errors[] = 'Vui lòng nhập tên người liên hệ.';
        if ($contactPhone === '') {
            $errors[] = 'Vui lòng nhập số điện thoại liên hệ.';
        } elseif (!preg_match('/^[0-9+\-\s\.]{8,20}$/', $contactPhone)) {
            $errors[] = 'Số điện thoại liên hệ không hợp lệ.';
        }
        if ($contactEmail !== '' && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email liên hệ không hợp lệ.';
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            header('Location: post-action.php?action=edit&post_id=' . $postId);
            exit();
        }

        $projectId = resolve_post_project_id($conn, $projectId, $location, $title);
        $content = build_optional_post_content([
            'package' => $package,
            'price_unit' => $priceUnit,
            'deposit' => $deposit,
            'management_fee' => $managementFee,
            'contact_method' => $contactMethod,
        ]);

        // Database của mỗi máy có thể thiếu vài cột như content, direction, floor...
        // Vì vậy đoạn dưới chỉ UPDATE những cột thật sự tồn tại trong bảng post.
        if (!post_table_has_column($conn, 'post', 'content') && $content !== '') {
            $description = trim($description . "\n\n" . $content);
        }

        $sets = [];
        $types = '';
        $values = [];

        $addSet = function (string $column, $value, string $type) use ($conn, &$sets, &$types, &$values): void {
            if (post_table_has_column($conn, 'post', $column)) {
                $sets[] = "`{$column}` = ?";
                $types .= $type;
                $values[] = $value;
            }
        };

        $addSet('title', $title, 's');
        $addSet('slug', $slug, 's');
        $addSet('price', $price, 'd');
        $addSet('area', $area, 'd');
        $addSet('bedroom', $bedroomValue, 'i');
        $addSet('bathroom', $bathroomValue, 'i');
        $addSet('floor_range', $floorRangeValue, 's');
        $addSet('type', $typeValue, 's');
        $addSet('furniture', $furnitureValue, 's');
        $addSet('description', $description, 's');
        $addSet('content', $content, 's');
        if ($projectId > 0) {
            $addSet('project_id', $projectId, 'i');
        } elseif (post_table_has_column($conn, 'post', 'project_id')) {
            $sets[] = 'project_id = NULL';
        }
        $addSet('contact_name', $contactName, 's');
        $addSet('contact_phone', $contactPhone, 's');
        $addSet('contact_email', $contactEmail, 's');
        $addSet('direction', $direction, 's');
        $addSet('floor', $floor, 's');
        $addSet('package', $package, 's');
        $addSet('price_unit', $priceUnit, 's');
        $addSet('deposit', $deposit, 's');
        $addSet('management_fee', $managementFee, 's');
        $addSet('contact_method', $contactMethod, 's');
        $addSet('location', $location, 's');
        $addSet('is_vip', in_array($package, ['featured', 'premium'], true) ? 1 : 0, 'i');

        if (post_table_has_column($conn, 'post', 'updated_at')) {
            $sets[] = 'updated_at = NOW()';
        }

        if (empty($sets)) {
            $_SESSION['error'] = 'Không tìm thấy cột phù hợp để cập nhật bảng post.';
            header('Location: post-action.php?action=edit&post_id=' . $postId);
            exit();
        }

        $types .= 'ii';
        $values[] = $postId;
        $values[] = $userId;

        $sql = 'UPDATE post SET ' . implode(', ', $sets) . ' WHERE id = ? AND user_id = ?';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $_SESSION['error'] = 'Không thể chuẩn bị dữ liệu cập nhật: ' . $conn->error;
            header('Location: post-action.php?action=edit&post_id=' . $postId);
            exit();
        }
        stmt_bind_params($stmt, $types, $values);
        $stmt->execute();
        $stmt->close();

        if (!empty($_POST['delete_images']) && is_array($_POST['delete_images'])) {
            foreach ($_POST['delete_images'] as $imageId) {
                delete_post_image_by_id($conn, (int)$imageId, $postId);
            }
        }

        $uploadResult = upload_post_images($conn, $postId, 'images', false);
        $thumbnailImageId = (int)($_POST['thumbnail_image_id'] ?? 0);
        ensure_post_thumbnail($conn, $postId, $thumbnailImageId);

        $_SESSION['success'] = 'Đã cập nhật tin đăng thành công.';
        if ($uploadResult['saved'] > 0) {
            $_SESSION['success'] .= '<br>Đã thêm ' . $uploadResult['saved'] . ' ảnh mới.';
        }
        if (!empty($uploadResult['errors'])) {
            $_SESSION['success'] .= '<br>Lưu ý: ' . implode('<br>', $uploadResult['errors']);
        }
        redirectMyPosts();

    default:
        redirectMyPosts();
}
