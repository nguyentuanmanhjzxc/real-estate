<?php
include(__DIR__ . '/../../../config/database.php');

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

function is_password_match(string $plainPassword, string $storedHash): bool
{
    $info = password_get_info($storedHash);

    if (!empty($info['algo'])) {
        return password_verify($plainPassword, $storedHash);
    }

    return md5($plainPassword) === $storedHash;
}

function upgrade_legacy_hash(mysqli $conn, int $userId, string $plainPassword, string $storedHash): void
{
    $info = password_get_info($storedHash);
    if (!empty($info['algo'])) {
        return;
    }

    if (md5($plainPassword) !== $storedHash) {
        return;
    }

    $newHash = password_hash($plainPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
    if ($stmt) {
        $stmt->bind_param('si', $newHash, $userId);
        $stmt->execute();
        $stmt->close();
    }
}

function require_admin(): void
{
    if (empty($_SESSION['admin'])) {
        header('Location: login.php');
        exit();
    }
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
