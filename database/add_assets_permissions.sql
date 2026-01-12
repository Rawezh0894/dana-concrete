-- Add permissions for Assets and Depreciation Management
-- Run this SQL script to add the necessary permissions

INSERT INTO `permissions` (`name`, `description`) VALUES
('view_assets', 'بینینی ئامێرەکان'),
('add_assets', 'زیادکردنی ئامێر'),
('update_assets', 'نوێکردنەوەی ئامێر'),
('delete_assets', 'سڕینەوەی ئامێر'),
('view_depreciation', 'بینینی داخوران'),
('calculate_depreciation', 'ژمێریاری داخوران'),
('post_depreciation', 'پۆستکردنی داخوران'),
('view_depreciation_reports', 'بینینی راپۆرتی داخوران')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Grant all permissions to admin role (if admin role exists)
-- Note: Adjust role names based on your actual role system
-- INSERT INTO role_permissions (role, permission_id)
-- SELECT 'admin', id FROM permissions WHERE name IN ('view_assets', 'add_assets', 'update_assets', 'delete_assets', 'view_depreciation', 'calculate_depreciation', 'post_depreciation', 'view_depreciation_reports')
-- ON DUPLICATE KEY UPDATE role = role;
