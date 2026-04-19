<?php require_once(__DIR__ . '/bootstrap.php'); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($pageTitle ?? 'Trang quản trị'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body>
<div class="admin-shell">
    <?php include(__DIR__ . '/sidebar.php'); ?>
    <main class="admin-main">
        <?php include(__DIR__ . '/topbar.php'); ?>
        <section class="admin-content">
