<?php
session_start();
include(__DIR__ . '/../config/database.php');

// Check if user is logged in
if (empty($_SESSION['user'])) {
    $_SESSION['error'] = 'Vui lòng đăng nhập để thực hiện thao tác này.';
    header('Location: index.php?open_login=1');
    exit();
}

$userId = (int)($_SESSION['user']['id'] ?? 0);
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$postId = (int)($_POST['post_id'] ?? $_GET['post_id'] ?? 0);

// Verify the post belongs to the user using prepared statement
function verifyPostOwnership($conn, $postId, $userId) {
    $stmt = $conn->prepare("SELECT * FROM post WHERE id = ? AND user_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $postId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $post = $result->fetch_assoc();
        $stmt->close();
        return $post;
    }
    return false;
}

// Handle actions
switch ($action) {
    case 'delete':
        if ($postId && verifyPostOwnership($conn, $postId, $userId)) {
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
            
            // Delete image records using prepared statement
            $stmt = $conn->prepare("DELETE FROM images WHERE post_id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $postId);
                $stmt->execute();
                $stmt->close();
            }
            
            // Delete post using prepared statement
            $stmt = $conn->prepare("DELETE FROM post WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $postId);
                $stmt->execute();
                $stmt->close();
            }
            
            $_SESSION['success'] = 'Đã xóa tin đăng thành công.';
        } else {
            $_SESSION['error'] = 'Không thể xóa tin đăng this.';
        }
        header('Location: my-posts.php');
        exit();
        
    case 'toggle_status':
        if ($postId && verifyPostOwnership($conn, $postId, $userId)) {
            // Toggle status (1 = active, 0 = inactive) using prepared statement
            $stmt = $conn->prepare("UPDATE post SET status = CASE WHEN status = 1 THEN 0 ELSE 1 END WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $postId);
                $stmt->execute();
                $stmt->close();
            }
            
            $_SESSION['success'] = 'Đã cập nhật trạng thái tin đăng.';
        } else {
            $_SESSION['error'] = 'Không thể cập nhật trạng thái tin đăng.';
        }
        header('Location: my-posts.php');
        exit();
        
    case 'edit':
        // Get post data for editing
        if ($postId && verifyPostOwnership($conn, $postId, $userId)) {
            $post = verifyPostOwnership($conn, $postId, $userId);
            
            // Get project options
            $projects = mysqli_query($conn, "SELECT id, name, district, province FROM projects ORDER BY name ASC LIMIT 100");
            
            // Get post images using prepared statement
            $stmt = $conn->prepare("SELECT * FROM images WHERE post_id = ? ORDER BY is_thumbnail DESC, id ASC");
            if ($stmt) {
                $stmt->bind_param("i", $postId);
                $stmt->execute();
                $images = $stmt->get_result();
                $stmt->close();
            } else {
                $images = false;
            }
            
            $ten_trang = 'TheApartment';
            $user = $_SESSION['user'];
            $isAdmin = (int)($user['role'] ?? 0) === 1;
            
            include(__DIR__ . '/../includes/edit-post-form.php');
        } else {
            $_SESSION['error'] = 'Không thể chỉnh sửa tin đăng này.';
            header('Location: my-posts.php');
        }
        exit();
        
    case 'update':
        // Handle post update
        if ($postId && verifyPostOwnership($conn, $postId, $userId)) {
            $title = mysqli_real_escape_string($conn, $_POST['title'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            $area = (float)($_POST['area'] ?? 0);
            $bedroom = (int)($_POST['bedroom'] ?? 0);
            $bathroom = (int)($_POST['bathroom'] ?? 0);
            $type = mysqli_real_escape_string($conn, $_POST['type'] ?? 'Chuyển nhượng');
            $furniture = mysqli_real_escape_string($conn, $_POST['furniture'] ?? 'Cơ bản');
            $description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
            $content = mysqli_real_escape_string($conn, $_POST['content'] ?? '');
            $project_id = (int)($_POST['project_id'] ?? 0);
            $contact_phone = mysqli_real_escape_string($conn, $_POST['contact_phone'] ?? '');
            $contact_email = mysqli_real_escape_string($conn, $_POST['contact_email'] ?? '');
            
            // Update post using prepared statement
            $updateStmt = $conn->prepare("UPDATE post SET 
                    title = ?,
                    price = ?,
                    area = ?,
                    bedroom = ?,
                    bathroom = ?,
                    type = ?,
                    furniture = ?,
                    description = ?,
                    content = ?,
                    project_id = ?,
                    contact_phone = ?,
                    contact_email = ?,
                    updated_at = NOW()
                    WHERE id = ?");
            
            if ($updateStmt) {
                $updateStmt->bind_param("sdiiissssissii", 
                    $title, $price, $area, $bedroom, $bathroom, $type, 
                    $furniture, $description, $content, $project_id, 
                    $contact_phone, $contact_email, $postId);
                $updateStmt->execute();
                $updateStmt->close();
                
                // Handle image upload
                if (!empty($_FILES['images']['name'][0])) {
                    $uploadDir = __DIR__ . '/../uploads/';
                    $imageNames = [];
                    
                    foreach ($_FILES['images']['name'] as $key => $name) {
                        if ($_FILES['images']['error'][$key] === 0) {
                            $tmpName = $_FILES['images']['tmp_name'][$key];
                            $extension = pathinfo($name, PATHINFO_EXTENSION);
                            $newName = uniqid('apt_') . '.' . $extension;
                            
                            if (move_uploaded_file($tmpName, $uploadDir . $newName)) {
                                $imageNames[] = $newName;
                            }
                        }
                    }
                    
                    // Insert new images using prepared statement
                    if (!empty($imageNames)) {
                        $imgStmt = $conn->prepare("INSERT INTO images (post_id, image_url, is_thumbnail) VALUES (?, ?, ?)");
                        if ($imgStmt) {
                            foreach ($imageNames as $index => $imageName) {
                                $isThumb = $index === 0 ? 1 : 0;
                                $imgStmt->bind_param("isi", $postId, $imageName, $isThumb);
                                $imgStmt->execute();
                            }
                            $imgStmt->close();
                        }
                    }
                }
                
                $_SESSION['success'] = 'Đã cập nhật tin đăng thành công.';
            } else {
                $_SESSION['error'] = 'Có lỗi xảy ra khi cập nhật tin đăng.';
            }
        } else {
            $_SESSION['error'] = 'Không thể cập nhật tin đăng this.';
        }
        header('Location: my-posts.php');
        exit();
        
    default:
        header('Location: my-posts.php');
        exit();
}
?>