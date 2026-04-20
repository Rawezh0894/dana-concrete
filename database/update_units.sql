-- Create Units Table
CREATE TABLE IF NOT EXISTS inv_units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_ku VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default units
INSERT INTO inv_units (name_ku) VALUES ('دانە'), ('کارتۆن'), ('بەرمیل'), ('دەبەیە'), ('لیتر'), ('کیلۆگرام');
