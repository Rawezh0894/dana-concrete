-- Create list_materials table
-- This table is needed for the add_material functionality

CREATE TABLE IF NOT EXISTS `list_materials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `quantity` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `currency_type` enum('دینار','دۆلار') DEFAULT 'دینار',
  `purchase_price_usd` decimal(15,2) DEFAULT 0.00,
  `purchase_price_iqd` decimal(15,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample data for list_materials (optional)
INSERT INTO `list_materials` (`id`, `name`, `quantity`, `created_at`, `currency_type`, `purchase_price_usd`, `purchase_price_iqd`) VALUES
(2, 'تایە ', 12.00, '2025-07-23 13:49:28', 'دۆلار', 130.00, 0.00),
(3, 'باتری ', 11.00, '2025-07-23 13:54:42', 'دۆلار', 100.00, 0.00);

-- Show success message
SELECT 'list_materials table created successfully!' as message; 