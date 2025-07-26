-- Add missing permissions
INSERT INTO `permissions` (`id`, `name`, `description`) VALUES
(81, 'view_cash_box', 'بینینی قاسەکە'),
(82, 'add_cash_box', 'زیادکردنی قاسەکە'),
(83, 'edit_material', 'دەستکاری مەواد'),
(84, 'delete_material', 'سڕینەوەی مەواد');

-- Add permissions to admin role
INSERT INTO `role_permissions` (`role`, `permission_id`) VALUES
('admin', 81),
('admin', 82),
('admin', 83),
('admin', 84);

-- Add permissions to accountant role (if needed)
INSERT INTO `role_permissions` (`role`, `permission_id`) VALUES
('accountant', 81),
('accountant', 82),
('accountant', 83),
('accountant', 84); 