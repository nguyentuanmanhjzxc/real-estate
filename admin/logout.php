<?php
require_once(__DIR__ . '/includes/bootstrap.php');
unset($_SESSION['admin']);
unset($_SESSION['user']);
header('Location: ../public/index.php');
exit();
