# Trigger Fixes for Other Expenses Table

## Problem Identified
The original triggers in the `other_expenses` table were incomplete and didn't properly handle:
1. **Gas consumption** in `bins_silos` table when adding/updating gas usage expenses
2. **Material consumption** in `list_materials` table when adding/updating warehouse material usage expenses
3. **Proper restoration** of gas and materials when deleting or updating expenses

## Original Issues
- Only had gas restoration on delete (`trg_restore_gas_on_delete_other_expenses`)
- No gas consumption handling on insert/update
- No material consumption handling at all
- Manual gas handling in PHP code that could cause inconsistencies

## New Trigger Structure

### 1. `trg_after_insert_other_expenses` (AFTER INSERT)
**Purpose**: Handle operations after inserting a new expense record

**Actions**:
- **Cash Box Operations**: Insert cash box records for cash payments (IQD/USD)
- **Gas Consumption**: Decrease gas amount in `bins_silos` when `expense_type = 'بەکارهێنانی گاز'`
- **Material Consumption**: Decrease material quantity in `list_materials` when `expense_type = 'بەکارهێنانی کاڵای کۆگا'`

### 2. `trg_before_delete_other_expenses` (BEFORE DELETE)
**Purpose**: Handle operations before deleting an expense record

**Actions**:
- **Cash Box Reversal**: Delete cash box records for cash payments
- **Gas Restoration**: Increase gas amount in `bins_silos` when restoring gas usage
- **Material Restoration**: Increase material quantity in `list_materials` when restoring material usage

### 3. `trg_before_update_other_expenses` (BEFORE UPDATE)
**Purpose**: Handle operations before updating an expense record

**Actions**:
- **Cash Box Reversal**: Delete old cash box records
- **Gas Restoration**: Restore old gas amount to `bins_silos`
- **Material Restoration**: Restore old material quantity to `list_materials`

### 4. `trg_after_update_other_expenses` (AFTER UPDATE)
**Purpose**: Handle operations after updating an expense record

**Actions**:
- **Cash Box Operations**: Insert new cash box records for cash payments
- **Gas Consumption**: Decrease new gas amount in `bins_silos`
- **Material Consumption**: Decrease new material quantity in `list_materials`

## Key Features

### Automatic Gas Management
```sql
-- When expense_type = 'بەکارهێنانی گاز' and gas_liters > 0
UPDATE bins_silos
SET amount = amount - NEW.gas_liters
WHERE type = 'تەنکی' AND material_type = 'گاز'
LIMIT 1;
```

### Automatic Material Management
```sql
-- When expense_type = 'بەکارهێنانی کاڵای کۆگا' and material_quantity > 0
UPDATE list_materials
SET quantity = quantity - NEW.material_quantity
WHERE id = NEW.material_id;
```

### Smart Update Handling
- **Before Update**: Restores old values (gas + old_liters, material + old_quantity)
- **After Update**: Applies new values (gas - new_liters, material - new_quantity)
- **Result**: Only the difference is actually consumed/restored

## PHP Code Changes

### Removed Manual Gas Handling
- Removed manual gas tank updates from `add_expenses.php`
- Removed manual gas tank updates from `update_expenses.php`
- Added proper validation to check gas availability before allowing insert/update

### Added Validation
```php
// Check gas availability before allowing insert/update
if ($expense_type === 'بەکارهێنانی گاز' && $gas_liters && $gas_liters > 0) {
    // Check if enough gas is available in the tank
    // Prevent trigger failure due to insufficient gas
}
```

## Benefits

1. **Data Consistency**: All gas and material operations are handled automatically by triggers
2. **Atomic Operations**: Database operations are atomic - either all succeed or all fail
3. **No Manual Errors**: Eliminates possibility of forgetting to update gas/material quantities
4. **Proper Rollback**: When operations fail, all changes are properly rolled back
5. **Audit Trail**: All changes are properly tracked through triggers

## Usage Examples

### Adding Gas Usage Expense
1. User fills form with `expense_type = 'بەکارهێنانی گاز'` and `gas_liters = 100`
2. PHP validates gas availability (100 liters available)
3. Record is inserted into `other_expenses`
4. Trigger automatically decreases `bins_silos.amount` by 100 liters

### Updating Gas Usage Expense
1. User changes `gas_liters` from 100 to 150
2. PHP validates additional gas availability (50 more liters needed)
3. **Before Update Trigger**: Restores 100 liters to `bins_silos`
4. Record is updated in `other_expenses`
5. **After Update Trigger**: Consumes 150 liters from `bins_silos`
6. **Net Result**: Only 50 additional liters consumed

### Deleting Gas Usage Expense
1. User deletes expense with `gas_liters = 100`
2. **Before Delete Trigger**: Restores 100 liters to `bins_silos`
3. Record is deleted from `other_expenses`

## Database Schema Requirements

### Required Tables
- `other_expenses` - Main expense table
- `bins_silos` - Gas tank table with `type = 'تەنکی'` and `material_type = 'گاز'`
- `list_materials` - Materials table for warehouse materials
- `cash_box` - Cash transactions table

### Required Columns
- `other_expenses.expense_type` - Enum: 'بەکارهێنانی کاڵای کۆگا', 'بەکارهێنانی گاز', 'خەرجی تر'
- `other_expenses.gas_liters` - Decimal for gas quantity
- `other_expenses.material_id` - Foreign key to `list_materials.id`
- `other_expenses.material_quantity` - Decimal for material quantity
- `bins_silos.amount` - Current gas amount in tank
- `list_materials.quantity` - Current material quantity in warehouse

## Error Handling

### Insufficient Gas
- PHP validation prevents insertion/update if insufficient gas
- Clear error message: "بڕی گاز لە تەنکی کەمە. بڕی بەردەست: X لیتر، بڕی پێویست: Y لیتر"

### Insufficient Material
- PHP validation prevents insertion/update if insufficient material
- Clear error message: "بڕی پێویست لە کۆگا نەماوە. بڕی بەردەست: X، بڕی پێویست: Y"

### Missing Gas Tank
- PHP validation prevents operations if gas tank doesn't exist
- Clear error message: "تەنکی گاز لە سیستەمەکەدا نییە"

## Testing

### Test Scenarios
1. **Add gas usage expense** - Verify gas amount decreases in `bins_silos`
2. **Update gas usage expense** - Verify only difference is consumed
3. **Delete gas usage expense** - Verify gas amount is restored
4. **Add material usage expense** - Verify material quantity decreases in `list_materials`
5. **Update material usage expense** - Verify only difference is consumed
6. **Delete material usage expense** - Verify material quantity is restored
7. **Insufficient gas/material** - Verify proper error messages
8. **Cash payment handling** - Verify cash box records are created/deleted properly

### Debug Commands
```javascript
// Test foreign key constraint handling
OtherExpensesDebug.testForeignKeyHandling()

// Show all form data
OtherExpensesDebug.showAllFormData()

// Test field visibility
OtherExpensesDebug.testFieldVisibility()
``` 