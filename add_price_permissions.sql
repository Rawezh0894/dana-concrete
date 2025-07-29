-- Add new permissions for price management in summary concrete receipts
INSERT INTO `permissions` (`id`, `name`, `description`) VALUES
(87, 'view_concrete_prices', 'بینینی نرخەکانی کۆنکرێت'),
(88, 'set_concrete_prices', 'دانانی نرخی کۆنکرێت'),
(89, 'edit_concrete_prices', 'دەستکاری نرخی کۆنکرێت');
 
-- Add these permissions to admin role (assuming admin role exists)
INSERT INTO `role_permissions` (`role`, `permission_id`) VALUES
('admin', 87),
('admin', 88),
('admin', 89); 