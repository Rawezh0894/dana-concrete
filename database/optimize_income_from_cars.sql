-- Database optimization for income_from_cars performance
-- Run this script to add indexes for better query performance

-- Indexes for concrete_receipts table
CREATE INDEX IF NOT EXISTS idx_concrete_receipts_created_at ON concrete_receipts(created_at);
CREATE INDEX IF NOT EXISTS idx_concrete_receipts_customer_id ON concrete_receipts(customer_id);
CREATE INDEX IF NOT EXISTS idx_concrete_receipts_mixer_car_id ON concrete_receipts(mixer_car_id);
CREATE INDEX IF NOT EXISTS idx_concrete_receipts_mixer_driver_id ON concrete_receipts(mixer_driver_id);
CREATE INDEX IF NOT EXISTS idx_concrete_receipts_pump_car_id ON concrete_receipts(pump_car_id);
CREATE INDEX IF NOT EXISTS idx_concrete_receipts_pump_driver_id ON concrete_receipts(pump_driver_id);
CREATE INDEX IF NOT EXISTS idx_concrete_receipts_formulas_id ON concrete_receipts(formulas_id);

-- Composite indexes for common filter combinations
CREATE INDEX IF NOT EXISTS idx_concrete_receipts_date_customer ON concrete_receipts(created_at, customer_id);
CREATE INDEX IF NOT EXISTS idx_concrete_receipts_date_mixer_car ON concrete_receipts(created_at, mixer_car_id);
CREATE INDEX IF NOT EXISTS idx_concrete_receipts_date_pump_car ON concrete_receipts(created_at, pump_car_id);

-- Indexes for related tables
CREATE INDEX IF NOT EXISTS idx_customers_name ON customers(name);
CREATE INDEX IF NOT EXISTS idx_cars_name ON cars(name);
CREATE INDEX IF NOT EXISTS idx_employees_name ON employees(name);
CREATE INDEX IF NOT EXISTS idx_concrete_formulas_name ON concrete_formulas(name);

-- Analyze tables to update statistics
ANALYZE TABLE concrete_receipts;
ANALYZE TABLE customers;
ANALYZE TABLE cars;
ANALYZE TABLE employees;
ANALYZE TABLE concrete_formulas;

-- Show index information
SHOW INDEX FROM concrete_receipts;
