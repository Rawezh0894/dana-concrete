# Revolutionary Warehouse Unit System - Dana Concrete

## Overview

This is a revolutionary warehouse management system for Dana Concrete that implements advanced unit conversion and automatic price calculation features. The system supports multiple unit types and automatically handles conversions between different measurement units.

## Key Features

### 🎯 Unit Types Supported

1. **کارتۆن (Carton)**
   - Contains multiple pieces
   - System asks: "How many pieces per carton?"
   - Automatic calculation: Piece price = Carton price ÷ Pieces per carton
   - Available quantity: Number of cartons × Pieces per carton

2. **دانە (Piece)**
   - Individual items
   - Direct pricing per piece
   - No conversion needed

3. **بەرمیل (Barrel)**
   - Contains multiple buckets
   - Each bucket contains multiple liters
   - System asks: "How many buckets per barrel?" and "How many liters per bucket?"
   - Automatic calculations:
     - Bucket price = Barrel price ÷ Buckets per barrel
     - Liter price = Bucket price ÷ Liters per bucket
     - Total liters = Barrels × Buckets per barrel × Liters per bucket

4. **دەبە (Bucket)**
   - Contains multiple liters
   - System asks: "How many liters per bucket?"
   - Automatic calculation: Liter price = Bucket price ÷ Liters per bucket

5. **لیتر (Liter)**
   - Direct measurement
   - No conversion needed

### 🔄 Automatic Conversions

The system automatically handles all unit conversions:

- **Carton → Pieces**: `Total pieces = Cartons × Pieces per carton`
- **Barrel → Buckets → Liters**: `Total liters = Barrels × Buckets per barrel × Liters per bucket`
- **Bucket → Liters**: `Total liters = Buckets × Liters per bucket`
- **Piece/Liter**: Direct conversion (1:1)

### 💰 Automatic Price Calculations

The system automatically calculates prices at different unit levels:

1. **Carton System**:
   - Enter carton price
   - System calculates piece price automatically
   - Available quantity in base units (pieces)

2. **Barrel System**:
   - Enter barrel price
   - System calculates bucket and liter prices automatically
   - Available quantity in base units (liters)

3. **Bucket System**:
   - Enter bucket price
   - System calculates liter price automatically
   - Available quantity in base units (liters)

4. **Piece/Liter System**:
   - Direct pricing
   - No automatic calculations needed

## Database Structure

### New Tables Created

1. **`unit_types`** - Defines available unit types
2. **`warehouse_materials`** - Materials with unit conversion support
3. **`warehouse_inventory`** - Current inventory levels
4. **`warehouse_purchases`** - Purchase records
5. **`warehouse_purchase_items`** - Individual purchase items
6. **`unit_conversion_rules`** - Conversion logic rules
7. **`warehouse_transactions`** - Transaction log

### Key Features

- **Automatic Inventory Updates**: Triggers update inventory when purchases are made
- **Transaction Logging**: All activities are logged with detailed information
- **Price Averaging**: System maintains average prices across all transactions
- **Unit Conversion**: Automatic conversion between different unit types
- **Audit Trail**: Complete history of all warehouse activities

## Usage Examples

### Example 1: Cement Cartons
```
Material: Cement
Unit Type: Carton
Pieces per carton: 50
Carton price: $100
Available cartons: 10

System calculates:
- Piece price: $2.00 (100 ÷ 50)
- Available pieces: 500 (10 × 50)
- Base unit: kg
```

### Example 2: Gas Barrels
```
Material: Gas
Unit Type: Barrel
Buckets per barrel: 4
Liters per bucket: 50
Barrel price: $200
Available barrels: 5

System calculates:
- Bucket price: $50 (200 ÷ 4)
- Liter price: $1.00 (50 ÷ 50)
- Available liters: 1000 (5 × 4 × 50)
- Base unit: liter
```

### Example 3: Water Buckets
```
Material: Water
Unit Type: Bucket
Liters per bucket: 20
Bucket price: $10
Available buckets: 25

System calculates:
- Liter price: $0.50 (10 ÷ 20)
- Available liters: 500 (25 × 20)
- Base unit: liter
```

## Implementation Files

### Frontend Files
- `pages/add_material.php` - Main warehouse management interface
- `assets/js/add_material/` - JavaScript files for dynamic functionality

### Backend Files
- `process/add_material/add.php` - Add new materials
- `process/add_material/get_material.php` - View/edit materials
- `process/add_material/delete.php` - Delete materials
- `process/add_material/update.php` - Update materials
- `process/add_material/get_unit_type_fields.php` - Dynamic field generation

### Database Files
- `database_migration_unit_system.sql` - Complete database setup

## Installation Instructions

1. **Run Database Migration**:
   ```sql
   -- Execute the migration file
   source database_migration_unit_system.sql;
   ```

2. **Update Permissions**:
   - Ensure users have appropriate permissions for warehouse operations
   - Required permissions: `view_materials`, `add_material`, `edit_material`, `delete_material`

3. **Test the System**:
   - Add sample materials with different unit types
   - Test automatic price calculations
   - Verify inventory updates

## Benefits

### For Users
- **Simplified Input**: Enter prices at purchase unit level
- **Automatic Calculations**: No manual price calculations needed
- **Flexible Units**: Support for various measurement units
- **Real-time Updates**: Instant inventory and price updates

### For Management
- **Accurate Pricing**: Automatic price calculations eliminate errors
- **Inventory Tracking**: Real-time inventory levels in base units
- **Audit Trail**: Complete history of all transactions
- **Reporting**: Detailed reports on inventory and pricing

### For System
- **Scalable**: Easy to add new unit types
- **Maintainable**: Clean, modular code structure
- **Reliable**: Database triggers ensure data consistency
- **Extensible**: Can be extended for sales and other operations

## Future Enhancements

1. **Sales Integration**: Extend to handle sales with automatic unit conversions
2. **Barcode Support**: Add barcode scanning for quick material identification
3. **Mobile App**: Create mobile interface for warehouse operations
4. **Advanced Reporting**: Add comprehensive reporting and analytics
5. **Multi-location Support**: Extend for multiple warehouse locations

## Support

For technical support or questions about the warehouse system, please contact the development team.

---

**Note**: This system represents a significant upgrade to the existing warehouse management capabilities and provides a solid foundation for future enhancements. 