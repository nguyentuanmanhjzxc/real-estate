<?php
include(__DIR__ . '/../../config/database.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function h($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function format_money($amount): string
{
    $amount = (float) ($amount ?? 0);

    if ($amount >= 1000000000) {
        return number_format($amount / 1000000000, 1, ',', '.') . ' tỷ';
    }

    if ($amount >= 1000000) {
        return number_format($amount / 1000000, 0, ',', '.') . ' triệu';
    }

    return number_format($amount, 0, ',', '.') . ' đ';
}

function current_admin(): array
{
    if (!empty($_SESSION['user']) && (int) ($_SESSION['user']['role'] ?? 0) === 1) {
        return $_SESSION['user'];
    }

    if (!empty($_SESSION['admin'])) {
        return $_SESSION['admin'];
    }

    return [];
}

function require_admin(): void
{
    $admin = current_admin();

    if (empty($admin)) {
        $_SESSION['login_status'] = 'error';
        $_SESSION['login_msg'] = 'Vui lòng đăng nhập bằng tài khoản admin để vào trang quản trị.';
        header('Location: ../public/index.php?admin_login=1');
        exit();
    }

    $_SESSION['user'] = [
        'id' => (int) ($admin['id'] ?? 0),
        'name' => $admin['name'] ?? 'Admin',
        'email' => $admin['email'] ?? '',
        'phone' => $admin['phone'] ?? '',
        'role' => 1,
        'avatar' => $admin['avatar'] ?? ''
    ];
}


function admin_table_has_column(mysqli $conn, string $table, string $column): bool
{
    static $cache = [];
    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $stmt = $conn->prepare(
        'SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1'
    );
    if (!$stmt) {
        $cache[$key] = false;
        return false;
    }
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $result = $stmt->get_result();
    $cache[$key] = $result && $result->num_rows > 0;
    $stmt->close();
    return $cache[$key];
}

function get_single_value(mysqli $conn, string $sql)
{
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_row($result);
    return $row[0] ?? 0;
}

function is_active_menu(string $key, string $activeMenu): string
{
    return $key === $activeMenu ? 'active' : '';
}
