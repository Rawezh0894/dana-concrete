-- 1. دروستکردنی تەیبڵی جۆرەکانی مامەڵە (Transaction Categories)
CREATE TABLE IF NOT EXISTS `transaction_categories` (
    `id` int NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `type` enum('INFLOW', 'OUTFLOW', 'BOTH') DEFAULT 'BOTH', -- بۆ ئەوەی فلتەر بکرێت کامەیان بۆ زیادکردنە و کامەیان بۆ ڕاکێشان
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- دانانی هەندێک جۆری سەرەکی وەکو نموونە
INSERT IGNORE INTO `transaction_categories` (`name`, `type`) VALUES 
('نەقدی فرۆشتن', 'INFLOW'),
('قەرزی وەرگیراو', 'INFLOW'),
('داهاتی تر', 'INFLOW'),
('کڕینی کەلوپەل', 'OUTFLOW'),
('مووچەی کارمەندان', 'OUTFLOW'),
('خەرجی گشتی', 'OUTFLOW'),
('سەرفیاتی تر', 'OUTFLOW');

-- 2. زیادکردنی ستوونی category_id بۆ تەیبڵی transactions
ALTER TABLE `transactions` 
ADD COLUMN `category_id` int DEFAULT NULL AFTER `type`,
ADD CONSTRAINT `fk_transaction_category` FOREIGN KEY (`category_id`) REFERENCES `transaction_categories`(`id`) ON DELETE SET NULL;
