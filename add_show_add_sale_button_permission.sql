-- Add new permission for showing add sale button
INSERT INTO permissions (name, description) VALUES ('show_add_sale_button', 'توانای پیشاندانی دوگمەی زیادکردنی فرۆشتن');

-- Grant this permission to admin role (if not already exists)
INSERT INTO role_permissions (role, permission_id) 
SELECT 'admin', id FROM permissions WHERE name = 'show_add_sale_button'
ON DUPLICATE KEY UPDATE role = role;

-- Grant this permission to other roles as needed
-- Example: Grant to manager role
INSERT INTO role_permissions (role, permission_id) 
SELECT 'manager', id FROM permissions WHERE name = 'show_add_sale_button'
ON DUPLICATE KEY UPDATE role = role; 