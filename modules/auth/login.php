<?php
include(__DIR__. "/../../config/database.php");
session_start();

function isPasswordMatch($plainPassword, $storedHash) {
    $info = password_get_info($storedHash);

    if (!empty($info['algo'])) {
        return password_verify($plainPassword, $storedHash);
    }

    return md5($plainPassword) === $storedHash;
}

function upgradeLegacyHash($conn, $userId, $plainPassword, $storedHash) {
    $info = password_get_info($storedHash);

    if (!empty($info['algo']) || md5($plainPassword) !== $storedHash) {
        return;
    }

    $newHash = password_hash($plainPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $newHash, $userId);
        $stmt->execute();
        $stmt->close();
    }
}

function redirectWithMessage($status, $message, $query = '') {
    $_SESSION['login_status'] = $status;
    $_SESSION['login_msg'] = $message;

    $location = '../../public/index.php';
    if ($query !== '') {
        $location .= '?' . ltrim($query, '?');
    }

    header('Location: ' . $location);
    exit();
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $input = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if(empty($input) || empty($password)){
        redirectWithMessage('error', 'Vui lòng nhập đầy đủ email/số điện thoại và mật khẩu', 'open_login=1');
    }

    $isEmailLogin = filter_var($input, FILTER_VALIDATE_EMAIL);

    if($isEmailLogin){
        $stmt = $conn->prepare("SELECT id, name, email, phone, password, role_id, avatar FROM users WHERE email = ? LIMIT 1");
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, phone, password, role_id, avatar FROM users WHERE phone = ? LIMIT 1");
    }

    if (!$stmt) {
        redirectWithMessage('error', 'Không thể xử lý đăng nhập lúc này', 'open_login=1');
    }

    $stmt->bind_param("s", $input);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows == 0){
        $stmt->close();
        redirectWithMessage('error', 'Tài khoản không tồn tại', 'open_login=1');
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    if(!isPasswordMatch($password, $user['password'])){
        redirectWithMessage('error', 'Sai mật khẩu', 'open_login=1');
    }

    if ((int)$user['role_id'] === 1) {
        if (!$isEmailLogin) {
            redirectWithMessage('error', 'Tài khoản admin phải đăng nhập bằng Gmail.', 'admin_login=1');
        }

        if (!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', (string)($user['email'] ?? ''))) {
            redirectWithMessage('error', 'Tài khoản admin chỉ được phép dùng Gmail để đăng nhập.', 'admin_login=1');
        }
    }

    upgradeLegacyHash($conn, (int)$user['id'], $password, $user['password']);

    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'phone' => $user['phone'],
        'role' => (int)$user['role_id'],
        'avatar' => $user['avatar'] ?? ''
    ];

    if ((int)$user['role_id'] === 1) {
        header("Location: ../../admin/dashboard.php");
        exit();
    }

    header("Location: ../../public/index.php");
    exit();
}

header('Location: ../../public/index.php');
exit();
?>
