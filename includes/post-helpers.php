<?php
if (!function_exists('app_h')) {
    function app_h($value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('post_table_has_column')) {
    function post_table_has_column(mysqli $conn, string $table, string $column): bool
    {
        static $cache = [];
        $key = $table . '.' . $column;
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        // Không dùng: SHOW COLUMNS FROM `table` LIKE ?
        // Vì một số phiên bản MySQL/MariaDB không cho đặt dấu ? trong câu SHOW,
        // dẫn tới lỗi SQL syntax khi mysqli->prepare().
        $stmt = $conn->prepare(
            'SELECT 1
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
             LIMIT 1'
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
}


if (!function_exists('stmt_bind_params')) {
    function stmt_bind_params(mysqli_stmt $stmt, string $types, array &$values): bool
    {
        $refs = [];
        $refs[] = $types;
        foreach ($values as $key => &$value) {
            $refs[] = &$value;
        }
        return call_user_func_array([$stmt, 'bind_param'], $refs);
    }
}

if (!function_exists('format_post_money')) {
    function format_post_money($amount, string $type = ''): string
    {
        $amount = (float)($amount ?? 0);
        $suffix = ($type === 'Cho thuê') ? '/tháng' : '';
        if ($amount >= 1000000000) {
            return number_format($amount / 1000000000, 1, ',', '.') . ' tỷ' . $suffix;
        }
        if ($amount >= 1000000) {
            return number_format($amount / 1000000, 0, ',', '.') . ' triệu' . $suffix;
        }
        return number_format($amount, 0, ',', '.') . ' đ' . $suffix;
    }
}

if (!function_exists('normalize_post_type')) {
    function normalize_post_type(string $type): string
    {
        $map = [
            'Chuyển nhượng căn hộ' => 'Chuyển nhượng',
            'Cho thuê căn hộ' => 'Cho thuê',
            'mua-ban' => 'Chuyển nhượng',
            'cho-thue' => 'Cho thuê',
            'Chuyển nhượng' => 'Chuyển nhượng',
            'Cho thuê' => 'Cho thuê',
        ];
        return $map[$type] ?? 'Chuyển nhượng';
    }
}


if (!function_exists('normalize_furniture_value')) {
    function normalize_furniture_value(string $value): string
    {
        $value = trim($value);
        $map = [
            'Full nội thất' => 'Đầy đủ',
            'Đầy đủ nội thất' => 'Đầy đủ',
            'Đầy đủ' => 'Đầy đủ',
            'Nội thất cơ bản' => 'Cơ bản',
            'Cơ bản' => 'Cơ bản',
            'Nhà trống' => 'Trống',
            'Trống' => 'Trống',
        ];
        return $map[$value] ?? 'Cơ bản';
    }
}

if (!function_exists('normalize_floor_range')) {
    function normalize_floor_range(string $floorText): string
    {
        $floorText = trim($floorText);
        if ($floorText === '') {
            return 'Trung';
        }

        if (preg_match('/\d+/', $floorText, $matches)) {
            $floor = (int)$matches[0];
            if ($floor <= 5) {
                return 'Thấp';
            }
            if ($floor <= 15) {
                return 'Trung';
            }
            return 'Cao';
        }

        if (mb_stripos($floorText, 'thấp') !== false) {
            return 'Thấp';
        }
        if (mb_stripos($floorText, 'cao') !== false) {
            return 'Cao';
        }
        return 'Trung';
    }
}

if (!function_exists('app_slugify')) {
    function app_slugify(string $text): string
    {
        $text = trim($text);
        $map = [
            'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
            'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
            'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
            'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
            'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
            'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y','đ'=>'d',
            'À'=>'a','Á'=>'a','Ạ'=>'a','Ả'=>'a','Ã'=>'a','Â'=>'a','Ầ'=>'a','Ấ'=>'a','Ậ'=>'a','Ẩ'=>'a','Ẫ'=>'a','Ă'=>'a','Ằ'=>'a','Ắ'=>'a','Ặ'=>'a','Ẳ'=>'a','Ẵ'=>'a',
            'È'=>'e','É'=>'e','Ẹ'=>'e','Ẻ'=>'e','Ẽ'=>'e','Ê'=>'e','Ề'=>'e','Ế'=>'e','Ệ'=>'e','Ể'=>'e','Ễ'=>'e',
            'Ì'=>'i','Í'=>'i','Ị'=>'i','Ỉ'=>'i','Ĩ'=>'i',
            'Ò'=>'o','Ó'=>'o','Ọ'=>'o','Ỏ'=>'o','Õ'=>'o','Ô'=>'o','Ồ'=>'o','Ố'=>'o','Ộ'=>'o','Ổ'=>'o','Ỗ'=>'o','Ơ'=>'o','Ờ'=>'o','Ớ'=>'o','Ợ'=>'o','Ở'=>'o','Ỡ'=>'o',
            'Ù'=>'u','Ú'=>'u','Ụ'=>'u','Ủ'=>'u','Ũ'=>'u','Ư'=>'u','Ừ'=>'u','Ứ'=>'u','Ự'=>'u','Ử'=>'u','Ữ'=>'u',
            'Ỳ'=>'y','Ý'=>'y','Ỵ'=>'y','Ỷ'=>'y','Ỹ'=>'y','Đ'=>'d',
        ];
        $text = strtr($text, $map);
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');
        return $text !== '' ? $text : 'tin-dang';
    }
}

if (!function_exists('unique_post_slug')) {
    function unique_post_slug(mysqli $conn, string $title, int $ignorePostId = 0): string
    {
        $base = app_slugify($title);
        $slug = $base;
        $i = 1;

        while (post_table_has_column($conn, 'post', 'slug')) {
            if ($ignorePostId > 0) {
                $stmt = $conn->prepare('SELECT id FROM post WHERE slug = ? AND id <> ? LIMIT 1');
                if (!$stmt) {
                    break;
                }
                $stmt->bind_param('si', $slug, $ignorePostId);
            } else {
                $stmt = $conn->prepare('SELECT id FROM post WHERE slug = ? LIMIT 1');
                if (!$stmt) {
                    break;
                }
                $stmt->bind_param('s', $slug);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            $exists = $result && $result->num_rows > 0;
            $stmt->close();

            if (!$exists) {
                return $slug;
            }

            $i++;
            $slug = $base . '-' . $i;
        }

        return $slug;
    }
}

if (!function_exists('normalize_room_number')) {
    function normalize_room_number($value): int
    {
        $value = trim((string)$value);
        if ($value === '') {
            return 0;
        }
        if (preg_match('/\d+/', $value, $matches)) {
            return (int)$matches[0];
        }
        return 0;
    }
}

if (!function_exists('split_location_text')) {
    function split_location_text(string $location): array
    {
        $location = trim(preg_replace('/\s+/', ' ', $location));
        if ($location === '') {
            return ['', ''];
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', $location))));
        if (count($parts) >= 2) {
            return [$parts[0], implode(', ', array_slice($parts, 1))];
        }

        return [$location, ''];
    }
}

if (!function_exists('resolve_post_project_id')) {
    function resolve_post_project_id(mysqli $conn, int $projectId, string $location, string $title = ''): int
    {
        if ($projectId > 0) {
            $stmt = $conn->prepare('SELECT id FROM projects WHERE id = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('i', $projectId);
                $stmt->execute();
                $result = $stmt->get_result();
                $exists = $result && $result->num_rows > 0;
                $stmt->close();
                if ($exists) {
                    return $projectId;
                }
            }
        }

        $location = trim($location);
        if ($location === '') {
            return 0;
        }

        [$district, $province] = split_location_text($location);
        $projectName = 'Khu vực tự nhập';
        if ($district !== '') {
            $projectName .= ' - ' . $district;
        }

        $stmt = $conn->prepare('SELECT id FROM projects WHERE name = ? AND district = ? AND province = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('sss', $projectName, $district, $province);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result ? $result->fetch_assoc() : null;
            $stmt->close();
            if ($row) {
                return (int)$row['id'];
            }
        }
        $address = $location;
        $projectColumns = ['name', 'district', 'province'];
        $projectPlaceholders = ['?', '?', '?'];
        $projectTypes = 'sss';
        $projectValues = [$projectName, $district, $province];

        if (post_table_has_column($conn, 'projects', 'address_detail')) {
            $projectColumns[] = 'address_detail';
            $projectPlaceholders[] = '?';
            $projectTypes .= 's';
            $projectValues[] = $address;
        } elseif (post_table_has_column($conn, 'projects', 'address')) {
            $projectColumns[] = 'address';
            $projectPlaceholders[] = '?';
            $projectTypes .= 's';
            $projectValues[] = $address;
        }

        $sql = 'INSERT INTO projects (`' . implode('`, `', $projectColumns) . '`) VALUES (' . implode(', ', $projectPlaceholders) . ')';
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            stmt_bind_params($stmt, $projectTypes, $projectValues);
            if ($stmt->execute()) {
                $newId = (int)$stmt->insert_id;
                $stmt->close();
                return $newId;
            }
            $stmt->close();
        }

        return 0;
    }
}

if (!function_exists('build_optional_post_content')) {
    function build_optional_post_content(array $data): string
    {
        $lines = [];
        if (!empty($data['package'])) {
            $packageLabel = [
                'normal' => 'Tin thường',
                'featured' => 'Tin nổi bật',
                'premium' => 'Tin ưu tiên',
            ][$data['package']] ?? $data['package'];
            $lines[] = 'Gói hiển thị: ' . $packageLabel;
        }
        if (!empty($data['price_unit'])) {
            $unitLabel = $data['price_unit'] === 'monthly' ? 'Giá thuê / tháng' : 'Tổng giá bán';
            $lines[] = 'Đơn vị giá: ' . $unitLabel;
        }
        if (!empty($data['deposit'])) {
            $lines[] = 'Tiền cọc: ' . trim($data['deposit']);
        }
        if (!empty($data['management_fee'])) {
            $lines[] = 'Phí quản lý/phí khác: ' . trim($data['management_fee']);
        }
        if (!empty($data['contact_method'])) {
            $methodLabel = [
                'call' => 'Gọi điện',
                'zalo' => 'Zalo',
                'chat' => 'Chat trên website',
            ][$data['contact_method']] ?? $data['contact_method'];
            $lines[] = 'Ưu tiên liên hệ: ' . $methodLabel;
        }
        return implode("\n", $lines);
    }
}

if (!function_exists('upload_post_images')) {
    function upload_post_images(mysqli $conn, int $postId, string $inputName, bool $forceFirstAsThumbnail = false): array
    {
        $result = ['saved' => 0, 'errors' => []];
        if (empty($_FILES[$inputName]) || empty($_FILES[$inputName]['name']) || !is_array($_FILES[$inputName]['name'])) {
            return $result;
        }

        $uploadDir = realpath(__DIR__ . '/../uploads');
        if ($uploadDir === false) {
            $uploadDir = __DIR__ . '/../uploads';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }
        }
        $uploadDir = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxFileSize = 5 * 1024 * 1024;

        $existingThumb = 0;
        $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM images WHERE post_id = ? AND is_thumbnail = 1');
        if ($stmt) {
            $stmt->bind_param('i', $postId);
            $stmt->execute();
            $countResult = $stmt->get_result();
            $row = $countResult ? $countResult->fetch_assoc() : null;
            $existingThumb = (int)($row['total'] ?? 0);
            $stmt->close();
        }

        $insertStmt = $conn->prepare('INSERT INTO images (post_id, image_url, is_thumbnail) VALUES (?, ?, ?)');
        if (!$insertStmt) {
            $result['errors'][] = 'Không thể lưu thông tin ảnh vào database.';
            return $result;
        }

        $fileCount = count($_FILES[$inputName]['name']);
        for ($i = 0; $i < $fileCount; $i++) {
            $name = $_FILES[$inputName]['name'][$i] ?? '';
            $tmpName = $_FILES[$inputName]['tmp_name'][$i] ?? '';
            $error = (int)($_FILES[$inputName]['error'][$i] ?? UPLOAD_ERR_NO_FILE);
            $size = (int)($_FILES[$inputName]['size'][$i] ?? 0);

            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($error !== UPLOAD_ERR_OK) {
                $result['errors'][] = 'Một ảnh tải lên bị lỗi.';
                continue;
            }
            if ($size <= 0 || $size > $maxFileSize) {
                $result['errors'][] = 'Ảnh ' . $name . ' vượt quá dung lượng 5MB.';
                continue;
            }

            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedExtensions, true)) {
                $result['errors'][] = 'Ảnh ' . $name . ' không đúng định dạng.';
                continue;
            }

            $mime = mime_content_type($tmpName);
            if (!in_array($mime, $allowedMimeTypes, true)) {
                $result['errors'][] = 'Ảnh ' . $name . ' không phải file hình hợp lệ.';
                continue;
            }

            $newName = 'post_' . $postId . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
            if (!move_uploaded_file($tmpName, $uploadDir . $newName)) {
                $result['errors'][] = 'Không thể lưu ảnh ' . $name . '.';
                continue;
            }

            $isThumb = ($forceFirstAsThumbnail && $result['saved'] === 0) || ($existingThumb === 0 && $result['saved'] === 0) ? 1 : 0;
            $insertStmt->bind_param('isi', $postId, $newName, $isThumb);
            if ($insertStmt->execute()) {
                $result['saved']++;
                if ($isThumb) {
                    $existingThumb = 1;
                }
            } else {
                @unlink($uploadDir . $newName);
                $result['errors'][] = 'Không thể ghi ảnh ' . $name . ' vào database.';
            }
        }

        $insertStmt->close();
        return $result;
    }
}

if (!function_exists('delete_post_image_by_id')) {
    function delete_post_image_by_id(mysqli $conn, int $imageId, int $postId): bool
    {
        $stmt = $conn->prepare('SELECT image_url FROM images WHERE id = ? AND post_id = ? LIMIT 1');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ii', $imageId, $postId);
        $stmt->execute();
        $result = $stmt->get_result();
        $img = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        if (!$img) {
            return false;
        }

        $filePath = __DIR__ . '/../uploads/' . $img['image_url'];
        if (is_file($filePath)) {
            @unlink($filePath);
        }

        $stmt = $conn->prepare('DELETE FROM images WHERE id = ? AND post_id = ?');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ii', $imageId, $postId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}

if (!function_exists('ensure_post_thumbnail')) {
    function ensure_post_thumbnail(mysqli $conn, int $postId, int $thumbnailImageId = 0): void
    {
        if ($thumbnailImageId > 0) {
            $stmt = $conn->prepare('SELECT id FROM images WHERE id = ? AND post_id = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('ii', $thumbnailImageId, $postId);
                $stmt->execute();
                $result = $stmt->get_result();
                $exists = $result && $result->num_rows > 0;
                $stmt->close();
                if ($exists) {
                    $stmt = $conn->prepare('UPDATE images SET is_thumbnail = CASE WHEN id = ? THEN 1 ELSE 0 END WHERE post_id = ?');
                    if ($stmt) {
                        $stmt->bind_param('ii', $thumbnailImageId, $postId);
                        $stmt->execute();
                        $stmt->close();
                    }
                    return;
                }
            }
        }

        $stmt = $conn->prepare('SELECT id FROM images WHERE post_id = ? AND is_thumbnail = 1 LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('i', $postId);
            $stmt->execute();
            $result = $stmt->get_result();
            $hasThumb = $result && $result->num_rows > 0;
            $stmt->close();
            if ($hasThumb) {
                return;
            }
        }

        $stmt = $conn->prepare('SELECT id FROM images WHERE post_id = ? ORDER BY id ASC LIMIT 1');
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('i', $postId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        if (!$row) {
            return;
        }

        $newThumbId = (int)$row['id'];
        $stmt = $conn->prepare('UPDATE images SET is_thumbnail = CASE WHEN id = ? THEN 1 ELSE 0 END WHERE post_id = ?');
        if ($stmt) {
            $stmt->bind_param('ii', $newThumbId, $postId);
            $stmt->execute();
            $stmt->close();
        }
    }
}
