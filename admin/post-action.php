<?php
// Include database connection and helper functions
require_once(__DIR__ . '/includes/bootstrap.php');
require_admin();

// Get the global database connection
global $conn;

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$postId = (int)($_GET['post_id'] ?? $_POST['post_id'] ?? 0);

// Verify the post exists
function getPost($conn, $postId) {
    $stmt = $conn->prepare("SELECT * FROM post WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $result = $stmt->get_result();
        $post = $result->fetch_assoc();
        $stmt->close();
        return $post;
    }
    return null;
}

switch ($action) {
    case 'toggle_status':
        if ($postId && getPost($conn, $postId)) {
            $stmt = $conn->prepare("UPDATE post SET status = CASE WHEN status = 1 THEN 0 ELSE 1 END WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $postId);
                $stmt->execute();
                $stmt->close();
                $_SESSION['admin_success'] = 'Đã cập nhật trạng thái tin đăng thành công.';
            } else {
                $_SESSION['admin_error'] = 'Có lỗi xảy ra khi cập nhật trạng thái.';
            }
        } else {
            $_SESSION['admin_error'] = 'Tin đăng không tồn tại.';
        }
        header('Location: apartments.php');
        exit();

    case 'delete':
        if ($postId && getPost($conn, $postId)) {
            // Delete images first
            $stmt = $conn->prepare("SELECT image_url FROM images WHERE post_id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $postId);
                $stmt->execute();
                $imgResult = $stmt->get_result();
                while ($img = $imgResult->fetch_assoc()) {
                    $filePath = __DIR__ . '/../uploads/' . $img['image_url'];
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                $stmt->close();
            }
            
            // Delete image records
            $stmt = $conn->prepare("DELETE FROM images WHERE post_id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $postId);
                $stmt->execute();
                $stmt->close();
            }
            
            // Delete post
            $stmt = $conn->prepare("DELETE FROM post WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $postId);
                $stmt->execute();
                $stmt->close();
                $_SESSION['admin_success'] = 'Đã xóa tin đăng thành công.';
            } else {
                $_SESSION['admin_error'] = 'Có lỗi xảy ra khi xóa tin đăng.';
            }
        } else {
            $_SESSION['admin_error'] = 'Tin đăng không tồn tại.';
        }
        header('Location: apartments.php');
        exit();

    case 'approve':
        if ($postId && getPost($conn, $postId)) {
            $stmt = $conn->prepare("UPDATE post SET status = 1 WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $postId);
                $stmt->execute();
                $stmt->close();
                $_SESSION['admin_success'] = 'Đã duyệt tin đăng thành công.';
            } else {
                $_SESSION['admin_error'] = 'Có lỗi xảy ra khi duyệt tin đăng.';
            }
        } else {
            $_SESSION['admin_error'] = 'Tin đăng không tồn tại.';
        }
        header('Location: apartments.php');
        exit();

    case 'reject':
        if ($postId && getPost($conn, $postId)) {
            $stmt = $conn->prepare("UPDATE post SET status = 0 WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $postId);
                $stmt->execute();
                $stmt->close();
                $_SESSION['admin_success'] = 'Đã từ chối tin đăng.';
            } else {
                $_SESSION['admin_error'] = 'Có lỗi xảy ra khi từ chối tin đăng.';
            }
        } else {
            $_SESSION['admin_error'] = 'Tin đăng không tồn tại.';
        }
        header('Location: apartments.php');
        exit();

    case 'delete_project':
        $projectId = (int)($_GET['project_id'] ?? $_POST['project_id'] ?? 0);
        if ($projectId) {
            // Check if project exists
            $stmt = $conn->prepare("SELECT id FROM projects WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $projectId);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $stmt->close();
                    
                    // Update posts to remove project association
                    $stmt = $conn->prepare("UPDATE post SET project_id = 0 WHERE project_id = ?");
                    if ($stmt) {
                        $stmt->bind_param("i", $projectId);
                        $stmt->execute();
                        $stmt->close();
                    }
                    
                    // Delete the project
                    $stmt = $conn->prepare("DELETE FROM projects WHERE id = ?");
                    if ($stmt) {
                        $stmt->bind_param("i", $projectId);
                        $stmt->execute();
                        $stmt->close();
                        $_SESSION['admin_success'] = 'Đã xóa dự án thành công.';
                    } else {
                        $_SESSION['admin_error'] = 'Có lỗi xảy ra khi xóa dự án.';
                    }
                } else {
                    $stmt->close();
                    $_SESSION['admin_error'] = 'Dự án không tồn tại.';
                }
            }
        } else {
            $_SESSION['admin_error'] = 'Dự án không tồn tại.';
        }
        header('Location: projects.php');
        exit();

    case 'delete_image':
        $imageId = (int)($_GET['image_id'] ?? $_POST['image_id'] ?? 0);
        if ($imageId) {
            // Get image info
            $stmt = $conn->prepare("SELECT image_url FROM images WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $imageId);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($image = $result->fetch_assoc()) {
                    // Delete file
                    $filePath = __DIR__ . '/../uploads/' . $image['image_url'];
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                    $stmt->close();
                    
                    // Delete record
                    $stmt = $conn->prepare("DELETE FROM images WHERE id = ?");
                    if ($stmt) {
                        $stmt->bind_param("i", $imageId);
                        $stmt->execute();
                        $stmt->close();
                        $_SESSION['admin_success'] = 'Đã xóa hình ảnh thành công.';
                    } else {
                        $_SESSION['admin_error'] = 'Có lỗi xảy ra khi xóa hình ảnh.';
                    }
                } else {
                    $stmt->close();
                    $_SESSION['admin_error'] = 'Hình ảnh không tồn tại.';
                }
            }
        } else {
            $_SESSION['admin_error'] = 'Hình ảnh không tồn tại.';
        }
        header('Location: images.php');
        exit();

    default:
        header('Location: apartments.php');
        exit();
}