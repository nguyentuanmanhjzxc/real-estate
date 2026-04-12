<?php
session_start();
include(__DIR__ . '/../config/database.php');

if (empty($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$userId = (int)$_SESSION['user']['id'];

// ===== LẤY USER =====
$stmt = $conn->prepare("SELECT name, email, phone, avatar FROM users WHERE id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name  = trim($_POST['name'] ?? '');
    $phone = preg_replace('/\s+/', '', $_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    $avatarName = $user['avatar'] ?? '';
    $error = "";

    // ===== VALIDATE PHONE =====
    if (!preg_match('/^[0-9]{10,11}$/', $phone)) {
        $error = "SĐT phải 10-11 số";
    }

    // ===== VALIDATE EMAIL =====
    if (empty($error) && !empty($email)) {

        if (!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $email)) {
            $error = "Email phải là @gmail.com";
        } else {
            $check = $conn->prepare("SELECT id FROM users WHERE email=? AND id<>?");
            $check->bind_param("si", $email, $userId);
            $check->execute();

            if ($check->get_result()->num_rows > 0) {
                $error = "Email đã tồn tại";
            }
        }
    }

    // ===== XỬ LÝ AVATAR =====
    if (empty($error) && !empty($_FILES['avatar']['name'])) {
        $targetDir = __DIR__ . "/../uploads/avatar/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif'];

        if (!in_array($ext, $allowed)) {
            $error = "Ảnh phải jpg, png, gif";
        } else {
            $avatarName = time() . '_' . rand(1000,9999) . '.' . $ext;
            move_uploaded_file($_FILES['avatar']['tmp_name'], $targetDir . $avatarName);
        }
    }

    // ===== CHỈ UPDATE KHI KHÔNG CÓ LỖI =====
    if (empty($error)) {

        $update = $conn->prepare("
            UPDATE users 
            SET name=?, phone=?, email=?, avatar=? 
            WHERE id=?
        ");
        $update->bind_param("ssssi", $name, $phone, $email, $avatarName, $userId);
        $update->execute();

        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['avatar'] = $avatarName;

        header("Location: profile.php?success=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Profile</title>
<link rel="stylesheet" href="../assets/style.css">


</style>

</head>

<body>

<form method="POST" enctype="multipart/form-data">

<div class="profile-container">

    <!-- SIDEBAR -->
    <div class="profile-sidebar">

        <?php
        $avatar = !empty($user['avatar'])
            ? "../uploads/avatar/" . $user['avatar']
            : "https://via.placeholder.com/120";
        ?>

        <!-- AVATAR -->
        <img src="<?= $avatar ?>" class="avatar" id="avatarPreview"
             onclick="document.getElementById('avatarInput').click()"
             style="cursor:pointer;">

        <input type="file" name="avatar" id="avatarInput" hidden>

        <p style="font-size:13px;">Bấm vào ảnh để đổi</p>

        <h3><?= htmlspecialchars($user['name'] ?? '') ?></h3>
        <p><?= htmlspecialchars($user['email'] ?? 'Chưa có email') ?></p>

        <hr>
    </div>

    <!-- MAIN -->
    <div class="profile-card">

       
        <a href="index.php" class="btn-back">Về trang chủ</a>

                    <h2>Chỉnh sửa thông tin</h2>
            <?php if (isset($_GET['success']) && empty($error)): ?>
                <p class="success">Cập nhật thành công!</p>
            <?php endif; ?>
        <!-- ERROR -->
        <?php if (!empty($error)): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>

        <!-- FORM -->
        <div class="form-group">
            <label>Tên</label>
            <input type="text" name="name"
                value="<?= htmlspecialchars($user['name'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="text" name="email"
                value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                placeholder="Nhập email @gmail.com">
        </div>

        <div class="form-group">
            <label>SĐT</label>
            <input type="text" name="phone"
                value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
        </div>

        <button class="btn-save">Lưu thay đổi</button>

    </div>

</div>

</form>
<script>
const input = document.getElementById("avatarInput");
const preview = document.getElementById("avatarPreview");

input.addEventListener("change", function(e) {
    const file = e.target.files[0];
    if (file) {
        preview.src = URL.createObjectURL(file);
    }
});
</script>

</body>
</html>