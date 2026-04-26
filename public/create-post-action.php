<?php
session_start();
include(__DIR__ . '/../config/database.php');

// Check if user is logged in
if (empty($_SESSION['user'])) {
    $_SESSION['error'] = 'Vui lòng đăng nhập để đăng tin.';
    header('Location: index.php?open_login=1');
    exit();
}

$userId = (int)($_SESSION['user']['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: post-create.php');
    exit();
}

// Get and sanitize input data
$title = trim($_POST['title'] ?? '');
$price = (float)($_POST['price'] ?? 0);
$area = (float)($_POST['area'] ?? 0);
$bedroom = (int)($_POST['bedroom'] ?? 0);
$bathroom = (int)($_POST['bathroom'] ?? 0);
$type = trim($_POST['type'] ?? 'Chuyển nhượng');
$furniture = trim($_POST['furniture'] ?? 'Cơ bản');
$description = trim($_POST['description'] ?? '');
$content = trim($_POST['content'] ?? '');
$project_id = (int)($_POST['project_id'] ?? 0);
$contact_name = trim($_POST['contact_name'] ?? $_SESSION['user']['name'] ?? '');
$contact_phone = trim($_POST['contact_phone'] ?? '');
$contact_email = trim($_POST['contact_email'] ?? '');
$direction = trim($_POST['direction'] ?? '');
$floor = trim($_POST['floor'] ?? '');

// Validation
$errors = [];

if (empty($title)) {
    $errors[] = 'Vui lòng nhập tiêu đề tin đăng.';
}

if ($price <= 0) {
    $errors[] = 'Vui lòng nhập giá.';
}

if ($area <= 0) {
    $errors[] = 'Vui lòng nhập diện tích.';
}

if (empty($contact_phone)) {
    $errors[] = 'Vui lòng nhập số điện thoại liên hệ.';
}

if (!empty($errors)) {
    $_SESSION['error'] = implode('<br>', $errors);
    header('Location: post-create.php');
    exit();
}

// Map type from form to database value
$typeMap = [
    'Chuyển nhượng căn hộ' => 'Chuyển nhượng',
    'Cho thuê căn hộ' => 'Cho thuê',
    'Chuyển nhượng' => 'Chuyển nhượng',
    'Cho thuê' => 'Cho thuê',
];
$typeValue = $typeMap[$type] ?? 'Chuyển nhượng';

// Map bedroom value
$bedroomMap = [
    '1 phòng ngủ' => 1,
    '2 phòng ngủ' => 2,
    '3 phòng ngủ' => 3,
    '4+ phòng ngủ' => 4,
];
$bedroomValue = isset($bedroomMap[$bedroom]) ? $bedroomMap[$bedroom] : (int)$bedroom;

// Map bathroom value
$bathroomMap = [
    '1 phòng tắm' => 1,
    '2 phòng tắm' => 2,
    '3 phòng tắm' => 3,
];
$bathroomValue = isset($bathroomMap[$bathroom]) ? $bathroomMap[$bathroom] : (int)$bathroom;

// Map furniture value
$furnitureMap = [
    'Full nội thất' => 'Full nội thất',
    'Nội thất cơ bản' => 'Nội thất cơ bản',
    'Nhà trống' => 'Nhà trống',
];
$furnitureValue = $furnitureMap[$furniture] ?? 'Cơ bản';

// Escape values for database
$title = mysqli_real_escape_string($conn, $title);
$description = mysqli_real_escape_string($conn, $description);
$content = mysqli_real_escape_string($conn, $content);
$contact_name = mysqli_real_escape_string($conn, $contact_name);
$contact_phone = mysqli_real_escape_string($conn, $contact_phone);
$contact_email = mysqli_real_escape_string($conn, $contact_email);
$direction = mysqli_real_escape_string($conn, $direction);
$floor = mysqli_real_escape_string($conn, $floor);
$typeValue = mysqli_real_escape_string($conn, $typeValue);
$furnitureValue = mysqli_real_escape_string($conn, $furnitureValue);

// Insert into database
$sql = "INSERT INTO post (
    user_id, title, price, area, bedroom, bathroom, type, furniture, 
    description, content, project_id, contact_name, contact_phone, contact_email,
    direction, floor, status, created_at, updated_at
) VALUES (
    {$userId}, '{$title}', {$price}, {$area}, {$bedroomValue}, {$bathroomValue}, 
    '{$typeValue}', '{$furnitureValue}', '{$description}', '{$content}', 
    {$project_id}, '{$contact_name}', '{$contact_phone}', '{$contact_email}',
    '{$direction}', '{$floor}', 1, NOW(), NOW()
)";

if (mysqli_query($conn, $sql)) {
    $postId = mysqli_insert_id($conn);
    
    // Handle image upload
    if (!empty($_FILES['apartment_images']['name'][0])) {
        $uploadDir = __DIR__ . '/../uploads/';
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        
        $imageNames = [];
        $totalFiles = count($_FILES['apartment_images']['name']);
        
        for ($i = 0; $i < $totalFiles; $i++) {
            if ($_FILES['apartment_images']['error'][$i] === 0) {
                $tmpName = $_FILES['apartment_images']['tmp_name'][$i];
                $originalName = $_FILES['apartment_images']['name'][$i];
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                
                // Validate file type
                if (!in_array($extension, $allowedExtensions)) {
                    continue;
                }
                
                // Validate file size
                if ($_FILES['apartment_images']['size'][$i] > $maxFileSize) {
                    continue;
                }
                
                // Generate unique filename
                $newName = 'post_' . $postId . '_' . time() . '_' . $i . '.' . $extension;
                
                if (move_uploaded_file($tmpName, $uploadDir . $newName)) {
                    $imageNames[] = $newName;
                }
            }
        }
        
        // Insert image records
        if (!empty($imageNames)) {
            foreach ($imageNames as $index => $imageName) {
                $isThumb = $index === 0 ? 1 : 0;
                $imageName = mysqli_real_escape_string($conn, $imageName);
                mysqli_query($conn, "INSERT INTO images (post_id, image_url, is_thumbnail) VALUES ({$postId}, '{$imageName}', {$isThumb})");
            }
        }
    }
    
    $_SESSION['success'] = 'Đăng tin thành công! Tin đăng của bạn đang được hiển thị.';
    header('Location: my-posts.php');
    exit();
} else {
    $_SESSION['error'] = 'Có lỗi xảy ra khi đăng tin: ' . mysqli_error($conn);
    header('Location: post-create.php');
    exit();
}
?>