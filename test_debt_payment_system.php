<?php
/**
 * Test script for the enhanced customer debt payment system
 * This script tests the different payment types and their deletion/update logic
 */

require_once 'config/db_conected.php';

echo "<h1>Customer Debt Payment System Test</h1>\n";

try {
    // Test 1: Check if payment_type column exists
    echo "<h2>Test 1: Database Schema Check</h2>\n";
    $stmt = $pdo->query("DESCRIBE customer_debt_payments");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasPaymentType = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'payment_type') {
            $hasPaymentType = true;
            echo "✓ payment_type column exists with type: " . $column['Type'] . "<br>\n";
            break;
        }
    }
    
    if (!$hasPaymentType) {
        echo "✗ payment_type column missing!<br>\n";
    }
    
    // Test 2: Check if customer_payment_allocations table exists
    echo "<h2>Test 2: Payment Allocations Table Check</h2>\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'customer_payment_allocations'");
    if ($stmt->rowCount() > 0) {
        echo "✓ customer_payment_allocations table exists<br>\n";
        
        // Show table structure
        $stmt = $pdo->query("DESCRIBE customer_payment_allocations");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Table structure:<br>\n";
        foreach ($columns as $column) {
            echo "- " . $column['Field'] . " (" . $column['Type'] . ")<br>\n";
        }
    } else {
        echo "✗ customer_payment_allocations table missing!<br>\n";
    }
    
    // Test 3: Check existing debt payments and their types
    echo "<h2>Test 3: Existing Debt Payments Analysis</h2>\n";
    $stmt = $pdo->query("SELECT id, customer_id, payment_type, from_opening_debt_usd, from_sales_usd, paid_usd, paid_iqd FROM customer_debt_payments ORDER BY id");
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($payments)) {
        echo "No debt payments found in database.<br>\n";
    } else {
        echo "Found " . count($payments) . " debt payments:<br>\n";
        echo "<table border='1' style='border-collapse: collapse;'>\n";
        echo "<tr><th>ID</th><th>Customer ID</th><th>Payment Type</th><th>From Opening Debt</th><th>From Sales</th><th>Paid USD</th><th>Paid IQD</th></tr>\n";
        
        foreach ($payments as $payment) {
            echo "<tr>";
            echo "<td>" . $payment['id'] . "</td>";
            echo "<td>" . $payment['customer_id'] . "</td>";
            echo "<td>" . $payment['payment_type'] . "</td>";
            echo "<td>" . $payment['from_opening_debt_usd'] . "</td>";
            echo "<td>" . $payment['from_sales_usd'] . "</td>";
            echo "<td>" . $payment['paid_usd'] . "</td>";
            echo "<td>" . $payment['paid_iqd'] . "</td>";
            echo "</tr>\n";
        }
        echo "</table><br>\n";
    }
    
    // Test 4: Check payment allocations
    echo "<h2>Test 4: Payment Allocations Check</h2>\n";
    $stmt = $pdo->query("SELECT cpa.*, cdp.payment_type FROM customer_payment_allocations cpa JOIN customer_debt_payments cdp ON cpa.debt_payment_id = cdp.id ORDER BY cpa.id");
    $allocations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($allocations)) {
        echo "No payment allocations found.<br>\n";
    } else {
        echo "Found " . count($allocations) . " payment allocations:<br>\n";
        echo "<table border='1' style='border-collapse: collapse;'>\n";
        echo "<tr><th>ID</th><th>Debt Payment ID</th><th>Sale ID</th><th>Allocated Amount</th><th>Payment Type</th></tr>\n";
        
        foreach ($allocations as $allocation) {
            echo "<tr>";
            echo "<td>" . $allocation['id'] . "</td>";
            echo "<td>" . $allocation['debt_payment_id'] . "</td>";
            echo "<td>" . $allocation['sale_id'] . "</td>";
            echo "<td>" . $allocation['allocated_amount'] . "</td>";
            echo "<td>" . $allocation['payment_type'] . "</td>";
            echo "</tr>\n";
        }
        echo "</table><br>\n";
    }
    
    // Test 5: Check customers with opening debt
    echo "<h2>Test 5: Customers with Opening Debt</h2>\n";
    $stmt = $pdo->query("SELECT id, name, opening_debt_usd, opening_debt_iqd FROM customers WHERE opening_debt_usd > 0 OR opening_debt_iqd > 0");
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($customers)) {
        echo "No customers with opening debt found.<br>\n";
    } else {
        echo "Found " . count($customers) . " customers with opening debt:<br>\n";
        echo "<table border='1' style='border-collapse: collapse;'>\n";
        echo "<tr><th>ID</th><th>Name</th><th>Opening Debt USD</th><th>Opening Debt IQD</th></tr>\n";
        
        foreach ($customers as $customer) {
            echo "<tr>";
            echo "<td>" . $customer['id'] . "</td>";
            echo "<td>" . $customer['name'] . "</td>";
            echo "<td>" . $customer['opening_debt_usd'] . "</td>";
            echo "<td>" . $customer['opening_debt_iqd'] . "</td>";
            echo "</tr>\n";
        }
        echo "</table><br>\n";
    }
    
    // Test 6: Check sales with remaining amounts
    echo "<h2>Test 6: Sales with Remaining Amounts</h2>\n";
    $stmt = $pdo->query("SELECT id, customer_id, invoice_number, total_price, remaining_amount, order_date FROM sales WHERE remaining_amount > 0 ORDER BY customer_id, order_date");
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($sales)) {
        echo "No sales with remaining amounts found.<br>\n";
    } else {
        echo "Found " . count($sales) . " sales with remaining amounts:<br>\n";
        echo "<table border='1' style='border-collapse: collapse;'>\n";
        echo "<tr><th>ID</th><th>Customer ID</th><th>Invoice Number</th><th>Total Price</th><th>Remaining Amount</th><th>Order Date</th></tr>\n";
        
        foreach ($sales as $sale) {
            echo "<tr>";
            echo "<td>" . $sale['id'] . "</td>";
            echo "<td>" . $sale['customer_id'] . "</td>";
            echo "<td>" . $sale['invoice_number'] . "</td>";
            echo "<td>" . $sale['total_price'] . "</td>";
            echo "<td>" . $sale['remaining_amount'] . "</td>";
            echo "<td>" . $sale['order_date'] . "</td>";
            echo "</tr>\n";
        }
        echo "</table><br>\n";
    }
    
    echo "<h2>Test Summary</h2>\n";
    echo "✓ Database schema check completed<br>\n";
    echo "✓ Payment allocations table check completed<br>\n";
    echo "✓ Existing data analysis completed<br>\n";
    echo "<br>\n";
    echo "<strong>Next Steps:</strong><br>\n";
    echo "1. Run the database migration script manually if needed<br>\n";
    echo "2. Test the payment creation with different payment types<br>\n";
    echo "3. Test the deletion logic for each payment type<br>\n";
    echo "4. Test the update logic<br>\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>\n";
}
?>
