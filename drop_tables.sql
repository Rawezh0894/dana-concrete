-- Drop tables that were created for testing
-- This will remove the tables from the database

-- Drop purchase_material_items table first (due to foreign key constraints)
DROP TABLE IF EXISTS `purchase_material_items`;

-- Drop purchase_materials table
DROP TABLE IF EXISTS `purchase_materials`;

-- Drop other_expense_persons table
DROP TABLE IF EXISTS `other_expense_persons`;

-- Drop list_materials table
DROP TABLE IF EXISTS `list_materials`;

-- Show success message
SELECT 'Tables dropped successfully!' as message; 