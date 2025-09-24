# Customer Debt Payment System - Enhanced Implementation

## Overview
This document describes the enhanced customer debt payment system that properly handles different payment types (FIFO, Specific Sales, Opening Debt Only) with correct deletion and update logic.

## Payment Types

### 1. FIFO (First In, First Out)
- **Description**: Payments are allocated to the oldest debts first
- **Allocation Logic**: 
  - First reduces opening debt
  - Then reduces sales debts in chronological order (oldest first)
- **Deletion Logic**: Uses LIFO (Last In, First Out) to restore amounts
  - Restores to opening debt first
  - Then restores to sales in reverse chronological order (newest first)

### 2. Specific Sales
- **Description**: Payments are allocated to specific sales selected by the user
- **Allocation Logic**: 
  - Only reduces the selected sales
  - Tracks allocations in `customer_payment_allocations` table
- **Deletion Logic**: 
  - Restores exact amounts to the specific sales that were paid
  - Uses allocation records to determine which sales to restore

### 3. Opening Debt Only
- **Description**: Payments are allocated only to opening debt
- **Allocation Logic**: 
  - Only reduces opening debt
  - Does not affect sales debts
- **Deletion Logic**: 
  - Restores the exact amount to opening debt

## Database Schema

### customer_debt_payments Table
```sql
CREATE TABLE `customer_debt_payments` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `dolar_rate` decimal(10,2) DEFAULT 0.00,
  `paid_usd` decimal(14,2) DEFAULT 0.00,
  `paid_iqd` decimal(20,2) DEFAULT 0.00,
  `discount` decimal(14,4) DEFAULT 0.0000,
  `note` varchar(255) DEFAULT NULL,
  `payment_type` enum('fifo','specific_sales','opening_debt_only') NOT NULL DEFAULT 'fifo',
  `from_opening_debt_usd` decimal(14,2) DEFAULT 0.00,
  `from_sales_usd` decimal(14,2) DEFAULT 0.00
);
```

### customer_payment_allocations Table
```sql
CREATE TABLE `customer_payment_allocations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `debt_payment_id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `allocated_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
);
```

## Implementation Details

### Deletion Logic
The deletion logic now properly handles each payment type:

1. **Opening Debt Only**: Restores exact amount to `customers.opening_debt_usd`
2. **Specific Sales**: Restores exact amounts to specific sales based on allocation records
3. **FIFO**: Uses LIFO restoration (reverse of FIFO allocation)

### Update Logic
The update logic:
1. First restores the old allocation (like deletion)
2. Then applies new allocation (like insertion)
3. Uses FIFO for updates (can be enhanced later)

### Key Functions

#### handle_debt_payment_deletion()
- Handles deletion based on payment type
- Restores amounts to correct locations
- Uses LIFO for FIFO payments

#### handle_debt_payment_update()
- Handles updates by first restoring old allocations
- Then applying new allocations
- Maintains data integrity

## Usage Examples

### Adding a FIFO Payment
```javascript
// Payment will be allocated to oldest debts first
formData.append('payment_type', 'fifo');
```

### Adding a Specific Sales Payment
```javascript
// Payment will be allocated to selected sales
formData.append('payment_type', 'specific_sales');
formData.append('specific_sales', JSON.stringify({
    '123': 100.00,  // Sale ID 123 gets $100
    '124': 50.00    // Sale ID 124 gets $50
}));
```

### Adding an Opening Debt Only Payment
```javascript
// Payment will only reduce opening debt
formData.append('payment_type', 'opening_debt_only');
```

## Benefits

1. **Data Integrity**: Proper restoration of amounts when deleting/updating
2. **Flexibility**: Support for different payment allocation strategies
3. **Auditability**: Clear tracking of how payments are allocated
4. **Consistency**: LIFO deletion for FIFO payments maintains logical consistency
5. **User Control**: Users can choose how payments are allocated

## Migration Notes

- Existing records are automatically migrated to have proper payment types
- Records with allocations are marked as 'specific_sales'
- Records with only opening debt payments are marked as 'opening_debt_only'
- All other records default to 'fifo'

## Testing

The system includes comprehensive logging to track:
- Payment type selection
- Allocation amounts
- Restoration amounts during deletion/update
- Error conditions

Check the error logs for detailed operation tracking.
