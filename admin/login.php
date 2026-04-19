<?php
require_once(__DIR__ . '/includes/bootstrap.php');

if (!empty(current_admin())) {
    header('Location: dashboard.php');
    exit();
}

$_SESSION['login_status'] = 'error';
$_SESSION['login_msg'] = 'Hãy đăng nhập bằng Gmail admin tại form đăng nhập chung để vào khu quản trị.';
header('Location: ../public/index.php?admin_login=1');
exit();
