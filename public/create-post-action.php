<?php
session_start();
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../includes/post-helpers.php');

if (empty($_SESSION['user'])) {
    $_SESSION['error'] = 'Vui lòng đăng nhập để đăng tin.';
    header('Location: index.php?open_login=1');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: post-create.php');
    exit();
}

$userId = (int)($_SESSION['user']['id'] ?? 0);
if ($userId <= 0) {
    $_SESSION['error'] = 'Phiên đăng nhập không hợp lệ. Vui lòng đăng nhập lại.';
    header('Location: index.php?open_login=1');
    exit();
}

$title = trim($_POST['title'] ?? '');
$price = (float)($_POST['price'] ?? 0);
$area = (float)($_POST['area'] ?? 0);
$bedroomValue = normalize_room_number($_POST['bedroom'] ?? '');
$bathroomValue = normalize_room_number($_POST['bathroom'] ?? '');
$typeValue = normalize_post_type(trim($_POST['type'] ?? ''));
$furnitureValue = normalize_furniture_value(trim($_POST['furniture'] ?? 'Nội thất cơ bản'));
$floorRangeValue = normalize_floor_range(trim($_POST['floor'] ?? ''));
$slug = unique_post_slug($conn, $title);
$description = trim($_POST['description'] ?? '');
$location = trim($_POST['location'] ?? '');
$projectId = (int)($_POST['project_id'] ?? 0);
$contactName = trim($_POST['contact_name'] ?? ($_SESSION['user']['name'] ?? ''));
$contactPhone = trim($_POST['contact_phone'] ?? '');
$contactEmail = trim($_POST['contact_email'] ?? '');
$direction = trim($_POST['direction'] ?? '');
$floor = trim($_POST['floor'] ?? '');
$package = trim($_POST['package'] ?? 'normal');
$priceUnit = trim($_POST['price_unit'] ?? ($typeValue === 'Cho thuê' ? 'monthly' : 'total'));
$deposit = trim($_POST['deposit'] ?? '');
$managementFee = trim($_POST['management_fee'] ?? '');
$contactMethod = trim($_POST['contact_method'] ?? 'call');

$errors = [];
if ($title === '') {
    $errors[] = 'Vui lòng nhập tiêu đề tin đăng.';
}
if ($price <= 0) {
    $errors[] = 'Vui lòng nhập giá lớn hơn 0.';
}
if ($area <= 0) {
    $errors[] = 'Vui lòng nhập diện tích lớn hơn 0.';
}
if ($bedroomValue <= 0) {
    $errors[] = 'Vui lòng chọn số phòng ngủ.';
}
if ($bathroomValue <= 0) {
    $errors[] = 'Vui lòng chọn số phòng tắm.';
}
if ($contactName === '') {
    $errors[] = 'Vui lòng nhập tên người liên hệ.';
}
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
    header('Location: post-create.php');
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
// Vì vậy đoạn dưới chỉ INSERT những cột thật sự tồn tại trong bảng post.
if (!post_table_has_column($conn, 'post', 'content') && $content !== '') {
    $description = trim($description . "\n\n" . $content);
}

$columns = [];
$placeholders = [];
$types = '';
$values = [];

$insertField = function (string $column, $value, string $type) use ($conn, &$columns, &$placeholders, &$types, &$values): void {
    if (post_table_has_column($conn, 'post', $column)) {
        $columns[] = $column;
        $placeholders[] = '?';
        $types .= $type;
        $values[] = $value;
    }
};

$insertRaw = function (string $column, string $rawSql) use ($conn, &$columns, &$placeholders): void {
    if (post_table_has_column($conn, 'post', $column)) {
        $columns[] = $column;
        $placeholders[] = $rawSql;
    }
};

$insertField('user_id', $userId, 'i');
$insertField('title', $title, 's');
$insertField('slug', $slug, 's');
$insertField('price', $price, 'd');
$insertField('area', $area, 'd');
$insertField('bedroom', $bedroomValue, 'i');
$insertField('bathroom', $bathroomValue, 'i');
$insertField('floor_range', $floorRangeValue, 's');
$insertField('type', $typeValue, 's');
$insertField('furniture', $furnitureValue, 's');
$insertField('description', $description, 's');
$insertField('content', $content, 's');
if ($projectId > 0) {
    $insertField('project_id', $projectId, 'i');
}
$insertField('contact_name', $contactName, 's');
$insertField('contact_phone', $contactPhone, 's');
$insertField('contact_email', $contactEmail, 's');
$insertField('direction', $direction, 's');
$insertField('floor', $floor, 's');
$insertField('package', $package, 's');
$insertField('price_unit', $priceUnit, 's');
$insertField('deposit', $deposit, 's');
$insertField('management_fee', $managementFee, 's');
$insertField('contact_method', $contactMethod, 's');
$insertField('location', $location, 's');

if (post_table_has_column($conn, 'post', 'is_vip')) {
    $insertField('is_vip', in_array($package, ['featured', 'premium'], true) ? 1 : 0, 'i');
}
if (post_table_has_column($conn, 'post', 'status')) {
    $columns[] = 'status';
    $placeholders[] = '1';
}
$insertRaw('created_at', 'NOW()');
$insertRaw('updated_at', 'NOW()');

if (empty($columns)) {
    $_SESSION['error'] = 'Không tìm thấy cột phù hợp trong bảng post. Vui lòng kiểm tra lại database.';
    header('Location: post-create.php');
    exit();
}

$sql = 'INSERT INTO post (`' . implode('`, `', $columns) . '`) VALUES (' . implode(', ', $placeholders) . ')';
$stmt = $conn->prepare($sql);

if (!$stmt) {
    $_SESSION['error'] = 'Không thể chuẩn bị dữ liệu đăng tin: ' . $conn->error;
    header('Location: post-create.php');
    exit();
}

stmt_bind_params($stmt, $types, $values);

if (!$stmt->execute()) {
    $_SESSION['error'] = 'Có lỗi xảy ra khi đăng tin: ' . $stmt->error;
    $stmt->close();
    header('Location: post-create.php');
    exit();
}

$postId = (int)$stmt->insert_id;
$stmt->close();

$uploadResult = upload_post_images($conn, $postId, 'apartment_images', true);
ensure_post_thumbnail($conn, $postId);

if ($uploadResult['saved'] === 0) {
    $_SESSION['success'] = 'Đăng tin thành công. Bạn chưa tải ảnh, hệ thống sẽ hiển thị ảnh mặc định cho tin này.';
} else {
    $_SESSION['success'] = 'Đăng tin thành công! Đã tải lên ' . $uploadResult['saved'] . ' ảnh cho tin đăng.';
}

if (!empty($uploadResult['errors'])) {
    $_SESSION['success'] .= '<br>Lưu ý: ' . implode('<br>', $uploadResult['errors']);
}

header('Location: my-posts.php');
exit();
