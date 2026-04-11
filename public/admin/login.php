<?php
require_once(__DIR__ . '/includes/bootstrap.php');

if (!empty($_SESSION['admin'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Vui lòng nhập đầy đủ email và mật khẩu.';
    } else {
        $stmt = $conn->prepare('SELECT id, name, email, password, role_id FROM users WHERE email = ? AND role_id = 1 LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin = $result->fetch_assoc();
            $stmt->close();

            if (!$admin) {
                $error = 'Tài khoản này không có quyền vào trang quản trị.';
            } elseif (!is_password_match($password, $admin['password'])) {
                $error = 'Mật khẩu không đúng.';
            } else {
                upgrade_legacy_hash($conn, (int) $admin['id'], $password, $admin['password']);

                $_SESSION['admin'] = [
                    'id' => (int) $admin['id'],
                    'name' => $admin['name'],
                    'email' => $admin['email'],
                    'role_id' => (int) $admin['role_id'],
                ];

                header('Location: dashboard.php');
                exit();
            }
        } else {
            $error = 'Không thể xử lý yêu cầu đăng nhập lúc này.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập quản trị</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/admin.css">
</head>
<body class="admin-login-body">
    <div class="admin-login-wrap">
        <div class="admin-login-hero">
            <span class="hero-chip">Admin Portal</span>
            <h1>Trang quản trị dành riêng cho người quản lý</h1>
            <p>Chỉ tài khoản có quyền quản trị mới được phép truy cập. Giao diện này giúp theo dõi căn hộ, khách hàng và yêu cầu liên hệ trong một nơi duy nhất.</p>
            <div class="hero-points">
                <div class="hero-point">Kiểm soát tài khoản quản trị riêng biệt</div>
                <div class="hero-point">Theo dõi tin đăng, khách hàng và liên hệ</div>
                <div class="hero-point">Giao diện đồng bộ với phong cách hiện đại</div>
            </div>
        </div>

        <div class="admin-login-card">
            <div class="login-card-head">
                <div class="brand-icon large">TA</div>
                <div>
                    <div class="login-title">Đăng nhập quản trị</div>
                    <div class="login-subtitle">Sử dụng email quản lý đã được cấp quyền Admin</div>
                </div>
            </div>

            <?php if ($error !== ''): ?>
                <div class="admin-alert error"><?php echo h($error); ?></div>
            <?php endif; ?>

            <form method="post" class="admin-login-form">
                <label>
                    <span>Email quản trị</span>
                    <input type="email" name="email" placeholder="admin@gmail.com" required>
                </label>

                <label>
                    <span>Mật khẩu</span>
                    <input type="password" name="password" placeholder="Nhập mật khẩu" required>
                </label>

                <button type="submit" class="primary-btn full">Đăng nhập</button>
            </form>

            <div class="login-note">
                Người dùng thường không thể đăng nhập ở đây. Chỉ tài khoản <strong>role Admin</strong> mới vào được.
            </div>

            <a class="back-link" href="../index.php">← Quay lại trang chủ</a>
        </div>
    </div>
</body>
</html>
