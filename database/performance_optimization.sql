-- Performance Optimization for Sales Table
-- Run these queries to improve database performance

-- 1. Add index on order_date for faster date filtering
CREATE INDEX IF NOT EXISTS idx_sales_order_date ON sales(order_date);

-- 2. Add index on customer_id for faster customer filtering
CREATE INDEX IF NOT EXISTS idx_sales_customer_id ON sales(customer_id);

-- 3. Add composite index for common queries (date + customer)
CREATE INDEX IF NOT EXISTS idx_sales_date_customer ON sales(order_date, customer_id);

-- 4. Add index on invoice_number for duplicate checking
CREATE INDEX IF NOT EXISTS idx_sales_invoice_number ON sales(invoice_number);

-- 5. Add index on remaining_amount for debt calculations
CREATE INDEX IF NOT EXISTS idx_sales_remaining_amount ON sales(remaining_amount);

-- 6. Optimize customers table
CREATE INDEX IF NOT EXISTS idx_customers_name ON customers(name);

-- 7. Optimize concrete_formulas table
CREATE INDEX IF NOT EXISTS idx_formulas_name ON concrete_formulas(name);

-- 8. Analyze tables for better query planning
ANALYZE TABLE sales;
ANALYZE TABLE customers;
ANALYZE TABLE concrete_formulas;
