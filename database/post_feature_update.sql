-- Chạy file này trong phpMyAdmin nếu muốn lưu đầy đủ các thông tin phụ của phần đăng tin.
-- Code PHP mới vẫn chạy được nếu bạn chưa chạy file này, nhưng các trường phụ như tiền cọc/gói tin sẽ chỉ được ghép vào cột content.

ALTER TABLE post
  ADD COLUMN IF NOT EXISTS location VARCHAR(255) NULL AFTER project_id,
  ADD COLUMN IF NOT EXISTS package VARCHAR(30) DEFAULT 'normal' AFTER location,
  ADD COLUMN IF NOT EXISTS price_unit VARCHAR(30) DEFAULT 'total' AFTER price,
  ADD COLUMN IF NOT EXISTS deposit VARCHAR(255) NULL AFTER price_unit,
  ADD COLUMN IF NOT EXISTS management_fee VARCHAR(255) NULL AFTER deposit,
  ADD COLUMN IF NOT EXISTS contact_method VARCHAR(30) DEFAULT 'call' AFTER contact_email;
