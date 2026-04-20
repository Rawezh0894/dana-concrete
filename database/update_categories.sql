-- Create Categories Table
CREATE TABLE IF NOT EXISTS inv_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_ku VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default categories
INSERT INTO inv_categories (name_ku) VALUES ('ڕۆن'), ('پاتری'), ('پارچەی یەدەگ'), ('فلتەر'), ('تایە'), ('تر');

-- Update inv_items category column to allow flexible values (since it was ENUM)
ALTER TABLE inv_items MODIFY COLUMN category VARCHAR(100) DEFAULT 'تر';
