-- Table for tracking specific payment allocations to sales transactions
-- This allows customers to specify which sales their payment should be applied to

CREATE TABLE `customer_payment_allocations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `debt_payment_id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `allocated_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `debt_payment_id` (`debt_payment_id`),
  KEY `sale_id` (`sale_id`),
  CONSTRAINT `customer_payment_allocations_ibfk_1` FOREIGN KEY (`debt_payment_id`) REFERENCES `customer_debt_payments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_payment_allocations_ibfk_2` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add payment_type column to customer_debt_payments table
ALTER TABLE `customer_debt_payments` 
ADD COLUMN `payment_type` ENUM('fifo', 'specific_sales', 'opening_debt_only') NOT NULL DEFAULT 'fifo' AFTER `note`;

-- Add index for better performance
ALTER TABLE `customer_payment_allocations` 
ADD INDEX `idx_debt_payment_sale` (`debt_payment_id`, `sale_id`);
