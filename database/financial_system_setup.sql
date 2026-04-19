-- 1. دروستکردنی تەیبڵی دراوەکان (Currencies)
CREATE TABLE IF NOT EXISTS `currencies` (
    `code` varchar(3) PRIMARY KEY, -- 'USD', 'IQD'
    `name` varchar(50) NOT NULL,
    `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- دانانی هەردوو دراوە سەرەکییەکە
INSERT IGNORE INTO `currencies` (`code`, `name`) VALUES 
('USD', 'دۆلاری ئەمریکی'), 
('IQD', 'دیناری عێراقی');

-- 2. دروستکردنی تەیبڵی قاسەکان (Wallets)
CREATE TABLE IF NOT EXISTS `wallets` (
    `id` int NOT NULL AUTO_INCREMENT,
    `user_id` int NOT NULL,
    `currency_code` varchar(3) NOT NULL,
    `balance` decimal(19,4) NOT NULL DEFAULT '0.0000',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_currency_uq` (`user_id`, `currency_code`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`currency_code`) REFERENCES `currencies`(`code`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. تەیبڵی نرخەکانی گۆڕینەوە (Exchange Rates)
CREATE TABLE IF NOT EXISTS `exchange_rates` (
    `id` int NOT NULL AUTO_INCREMENT,
    `base_currency` varchar(3) NOT NULL,
    `target_currency` varchar(3) NOT NULL,
    `rate` decimal(19,6) NOT NULL,
    `effective_from` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rate_timeline_uq` (`base_currency`, `target_currency`, `effective_from`),
    FOREIGN KEY (`base_currency`) REFERENCES `currencies`(`code`) ON DELETE CASCADE,
    FOREIGN KEY (`target_currency`) REFERENCES `currencies`(`code`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. تەیبڵی مامەڵەکان (Transactions)
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` int NOT NULL AUTO_INCREMENT,
    `reference_id` varchar(100) NOT NULL, -- بۆ ڕێگریکردن لە Transaction ی دووبارە
    `type` varchar(50) NOT NULL, -- 'EXCHANGE', 'DEPOSIT', 'WITHDRAWAL', 'TRANSFER'
    `status` varchar(20) DEFAULT 'COMPLETED',
    `created_by` int NOT NULL, -- کێ ئەمەی دروستکردووە
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `reference_uq` (`reference_id`),
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. تەیبڵی دەفتەری ژمێریاری (Ledger Entries) بۆ سیستەمی Double-Entry
CREATE TABLE IF NOT EXISTS `ledger_entries` (
    `id` int NOT NULL AUTO_INCREMENT,
    `transaction_id` int NOT NULL,
    `wallet_id` int NOT NULL,
    `amount` decimal(19,4) NOT NULL, -- بڕی پارا (+) بۆ هاتن، (-) بۆ دەرچوون
    `currency_code` varchar(3) NOT NULL,
    `exchange_rate_applied` decimal(19,6) DEFAULT '1.000000',
    `description` text COLLATE utf8mb4_general_ci,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`transaction_id`) REFERENCES `transactions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`wallet_id`) REFERENCES `wallets`(`id`),
    FOREIGN KEY (`currency_code`) REFERENCES `currencies`(`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
