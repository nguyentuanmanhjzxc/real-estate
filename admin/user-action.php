<?php
require_once(__DIR__ . '/includes/bootstrap.php');
require_admin();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$userId = (int)($_GET['user_id'] ?? $_POST['user_id'] ?? 0);

// Verify the user exists (and is not admin)
function getUser($conn, $userId) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role_id <> 1");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user;
    }
    return null;
}

switch ($action) {
    case 'toggle_status':
        if ($userId && getUser($conn, $userId)) {
            // Toggle is_active field (1 = active, 0 = locked)
            $stmt = $conn->prepare("UPDATE users SET is_active = CASE WHEN COALESCE(is_active, 1) = 1 THEN 0 ELSE 1 END WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $stmt->close();
                $_SESSION['admin_success'] = 'Đã cập nhật trạng thái tài khoản thành công.';
            } else {
                $_SESSION['admin_error'] = 'Có lỗi xảy ra khi cập nhật trạng thái.';
            }
        } else {
            $_SESSION['admin_error'] = 'Tài khoản không tồn tại hoặc là admin.';
        }
        header('Location: customers.php');
        exit();

    case 'delete':
        if ($userId && getUser($conn, $userId)) {
            // Check if user has any posts
            $postCount = (int) get_single_value($conn, "SELECT COUNT(*) FROM post WHERE user_id = {$userId}");
            
            // Delete user's posts first
            $stmt = $conn->prepare("SELECT id FROM post WHERE user_id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $postResult = $stmt->get_result();
                while ($post = $postResult->fetch_assoc()) {
                    // Delete images for each post
                    $imgStmt = $conn->prepare("SELECT image_url FROM images WHERE post_id = ?");
                    if ($imgStmt) {
                        $imgStmt->bind_param("i", $post['id']);
                        $imgStmt->execute();
                        $imgResult = $imgStmt->get_result();
                        while ($img = $imgResult->fetch_assoc()) {
                            $filePath = __DIR__ . '/../uploads/' . $img['image_url'];
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                        }
                        $imgStmt->close();
                    }
                    
                    // Delete image records
                    $imgStmt = $conn->prepare("DELETE FROM images WHERE post_id = ?");
                    if ($imgStmt) {
                        $imgStmt->bind_param("i", $post['id']);
                        $imgStmt->execute();
                        $imgStmt->close();
                    }
                }
                $stmt->close();
            }
            
            // Delete user's posts
            $stmt = $conn->prepare("DELETE FROM post WHERE user_id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $stmt->close();
            }
            
            // Delete user
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $stmt->close();
                $_SESSION['admin_success'] = 'Đã xóa tài khoản người dùng thành công.';
            } else {
                $_SESSION['admin_error'] = 'Có lỗi xảy ra khi xóa tài khoản.';
            }
        } else {
            $_SESSION['admin_error'] = 'Tài khoản không tồn tại hoặc là admin.';
        }
        header('Location: customers.php');
        exit();

    case 'activate':
        if ($userId && getUser($conn, $userId)) {
            $stmt = $conn->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $stmt->close();
                $_SESSION['admin_success'] = 'Đã kích hoạt tài khoản thành công.';
            } else {
                $_SESSION['admin_error'] = 'Có lỗi xảy ra khi kích hoạt tài khoản.';
            }
        } else {
            $_SESSION['admin_error'] = 'Tài khoản không tồn tại hoặc là admin.';
        }
        header('Location: customers.php');
        exit();

    case 'lock':
        if ($userId && getUser($conn, $userId)) {
            $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $stmt->close();
                $_SESSION['admin_success'] = 'Đã khóa tài khoản thành công.';
            } else {
                $_SESSION['admin_error'] = 'Có lỗi xảy ra khi khóa tài khoản.';
            }
        } else {
            $_SESSION['admin_error'] = 'Tài khoản không tồn tại hoặc là admin.';
        }
        header('Location: customers.php');
        exit();

    default:
        header('Location: customers.php');
        exit();
}
?>