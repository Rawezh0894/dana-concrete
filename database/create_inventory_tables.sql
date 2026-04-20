-- Item Definitions
CREATE TABLE IF NOT EXISTS inv_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category ENUM('Oil', 'Battery', 'Spare Part', 'Other') DEFAULT 'Other',
    unit VARCHAR(50), 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Purchase Master (Invoice Headers)
CREATE TABLE IF NOT EXISTS inv_purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(100) UNIQUE,
    supplier_name VARCHAR(255),
    purchase_date DATE NOT NULL,
    exchange_rate DECIMAL(15, 4) NOT NULL, -- The rate at the time of purchase
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Purchase Details (Line Items)
CREATE TABLE IF NOT EXISTS inv_purchase_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT,
    item_id INT,
    qty DECIMAL(15, 2) NOT NULL,
    unit_price DECIMAL(15, 2) NOT NULL,
    currency ENUM('USD', 'IQD') NOT NULL,
    unit_price_usd DECIMAL(15, 4) NOT NULL, -- Standardized price in USD
    FOREIGN KEY (purchase_id) REFERENCES inv_purchases(id),
    FOREIGN KEY (item_id) REFERENCES inv_items(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock Status & Moving Average Price (MAP)
CREATE TABLE IF NOT EXISTS inv_stock (
    item_id INT PRIMARY KEY,
    current_qty DECIMAL(15, 2) DEFAULT 0,
    avg_cost_usd DECIMAL(15, 4) DEFAULT 0, -- Standardized Average Cost
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inv_items(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Issuance to Vehicles (Maintenance Tracking)
CREATE TABLE IF NOT EXISTS inv_issuance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT,
    vehicle_id INT, -- Linked to existing 'cars' table
    qty DECIMAL(15, 2) NOT NULL,
    issued_date DATE NOT NULL,
    cost_usd_at_time DECIMAL(15, 4), -- Value recorded at the time of issuance
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inv_items(id),
    FOREIGN KEY (vehicle_id) REFERENCES cars(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
