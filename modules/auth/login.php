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
    $stmt->bind_param("si", $newHash, $userId);
    $stmt->execute();
    $stmt->close();
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $input = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if(empty($input) || empty($password)){
        $_SESSION['login_status'] = 'error';
        $_SESSION['login_msg'] = 'Vui lòng nhập thông tin đầy đủ';
        header("Location: ../../public/index.php");
        exit();
    }

    if(filter_var($input, FILTER_VALIDATE_EMAIL)){
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE phone = ?");
    }

    $stmt->bind_param("s", $input);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows == 0){
        $_SESSION['login_status'] = 'error';
        $_SESSION['login_msg'] = 'Tài khoản không tồn tại';
        header("Location: index.php");
        exit();
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    if(!isPasswordMatch($password, $user['password'])){
        $_SESSION['login_status'] = 'error';
        $_SESSION['login_msg'] = 'Sai mật khẩu';
        header("Location: index.php");
        exit();
    }

    if ((int)$user['role_id'] === 1) {
        $_SESSION['login_status'] = 'error';
        $_SESSION['login_msg'] = 'Tài khoản quản trị vui lòng đăng nhập tại cổng Admin.';
        header("Location: index.php");
        exit();
    }

    upgradeLegacyHash($conn, (int)$user['id'], $password, $user['password']);

    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'role' => $user['role_id']
    ];

    header("Location: ../../public/index.php");
    exit();
}
?>