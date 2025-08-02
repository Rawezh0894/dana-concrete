# نظام یەکەکان - Unit System Documentation

## ناوەڕۆک (Contents)
1. [مەبەست (Purpose)](#مەبەست-purpose)
2. [جۆرەکانی یەکە (Unit Types)](#جۆرەکانی-یەکە-unit-types)
3. [گۆڕانکارییەکانی داتابەیس (Database Changes)](#گۆڕانکارییەکانی-داتابەیس-database-changes)
4. [گۆڕانکارییەکانی فرۆنت ئێند (Frontend Changes)](#گۆڕانکارییەکانی-فرۆنت-ئێند-frontend-changes)
5. [گۆڕانکارییەکانی بەک ئێند (Backend Changes)](#گۆڕانکارییەکانی-بەک-ئێند-backend-changes)
6. [نموونەی بەکارهێنان (Usage Examples)](#نموونەی-بەکارهێنان-usage-examples)
7. [هەنگاوەکانی جێبەجێکردن (Implementation Steps)](#هەنگاوەکانی-جێبەجێکردن-implementation-steps)

## مەبەست (Purpose)

ئەم سیستەمە نوێیە بۆ چارەسەرکردنی کێشەی یەکەکان لە کۆگای کەل و پەلەکانە. پێشتر سیستەمەکە تەنها یەک جۆری یەکەی هەبوو، بەڵام ئێستا دەتوانین ٥ جۆری یەکەی جیاواز بەکاربهێنین:

## جۆرەکانی یەکە (Unit Types)

### 1. کارتۆن (Carton)
- **وەسف**: کەڵەکەیەک کە چەند دانەیەکی تێدایە
- **نموونە**: کارتۆنێک کە ٢٤ دانەی تێدایە
- **نرخ**: نرخی کارتۆن دەنووسین، سیستەم خۆی نرخی دانەکان دەدۆزێتەوە

### 2. دانە (Piece)
- **وەسف**: یەک دانە بەتەنیا
- **نموونە**: یەک دانە بەتەنیا
- **نرخ**: نرخی دانە دەنووسین

### 3. بەرمیل (Barrel)
- **وەسف**: بەرمیلێک کە چەند دەبەیەکی تێدایە، هەر دەبەیەک چەند لیتر
- **نموونە**: بەرمیلێک کە ٤ دەبەی تێدایە، هەر دەبەیەک ٢٥ لیتر
- **نرخ**: نرخی بەرمیل دەنووسین، سیستەم خۆی نرخی لیترەکان دەدۆزێتەوە

### 4. دەبە (Bag)
- **وەسف**: دەبەیەک کە چەند لیترێکی تێدایە
- **نموونە**: دەبەیەک کە ٢٥ لیترە
- **نرخ**: نرخی دەبە دەنووسین، سیستەم خۆی نرخی لیترەکان دەدۆزێتەوە

### 5. لیتر (Liter)
- **وەسف**: یەک لیتر بەتەنیا
- **نموونە**: یەک لیتر بەتەنیا
- **نرخ**: نرخی لیتر دەنووسین

## گۆڕانکارییەکانی داتابەیس (Database Changes)

### خشتەی `materials`
```sql
ALTER TABLE `materials` 
ADD COLUMN `unit_type` ENUM('carton', 'piece', 'barrel', 'bag', 'liter') NOT NULL DEFAULT 'piece',
ADD COLUMN `pieces_per_carton` INT NULL DEFAULT NULL,
ADD COLUMN `bags_per_barrel` INT NULL DEFAULT NULL,
ADD COLUMN `liters_per_bag` DECIMAL(10,2) NULL DEFAULT NULL,
ADD COLUMN `liters_per_barrel` DECIMAL(10,2) NULL DEFAULT NULL;
```

### خشتەی `list_materials`
```sql
ALTER TABLE `list_materials` 
ADD COLUMN `unit_type` ENUM('carton', 'piece', 'barrel', 'bag', 'liter') NOT NULL DEFAULT 'piece',
ADD COLUMN `pieces_per_carton` INT NULL DEFAULT NULL,
ADD COLUMN `bags_per_barrel` INT NULL DEFAULT NULL,
ADD COLUMN `liters_per_bag` DECIMAL(10,2) NULL DEFAULT NULL,
ADD COLUMN `liters_per_barrel` DECIMAL(10,2) NULL DEFAULT NULL,
ADD COLUMN `price_per_piece` DECIMAL(15,2) DEFAULT 0.00,
ADD COLUMN `price_per_liter` DECIMAL(15,2) DEFAULT 0.00;
```

### خشتەی `purchase_materials`
```sql
ALTER TABLE `purchase_materials` 
ADD COLUMN `unit_type` ENUM('carton', 'piece', 'barrel', 'bag', 'liter') NOT NULL DEFAULT 'piece',
ADD COLUMN `pieces_per_carton` INT NULL DEFAULT NULL,
ADD COLUMN `bags_per_barrel` INT NULL DEFAULT NULL,
ADD COLUMN `liters_per_bag` DECIMAL(10,2) NULL DEFAULT NULL,
ADD COLUMN `liters_per_barrel` DECIMAL(10,2) NULL DEFAULT NULL,
ADD COLUMN `price_per_piece` DECIMAL(15,2) DEFAULT 0.00,
ADD COLUMN `price_per_liter` DECIMAL(15,2) DEFAULT 0.00;
```

### فەنکشنەکان (Functions)
```sql
-- Calculate piece price from carton
CREATE FUNCTION `calculate_piece_price_from_carton`(
    p_carton_price DECIMAL(15,2),
    p_pieces_per_carton INT
) RETURNS DECIMAL(15,2)

-- Calculate liter price from barrel
CREATE FUNCTION `calculate_liter_price_from_barrel`(
    p_barrel_price DECIMAL(15,2),
    p_bags_per_barrel INT,
    p_liters_per_bag DECIMAL(10,2)
) RETURNS DECIMAL(15,2)

-- Calculate liter price from bag
CREATE FUNCTION `calculate_liter_price_from_bag`(
    p_bag_price DECIMAL(15,2),
    p_liters_per_bag DECIMAL(10,2)
) RETURNS DECIMAL(15,2)
```

## گۆڕانکارییەکانی فرۆنت ئێند (Frontend Changes)

### 1. زیادکردنی کاڵا (`add_material.php`)
- زیادکردنی هەڵبژاردنی جۆری یەکە
- زیادکردنی خانەکانی تایبەت بۆ هەر جۆری یەکە
- ژماردنی ئۆتۆماتیکی نرخی یەکەکان

### 2. کڕینی کاڵاکان (`purchase_materila.php`)
- پیشاندانی جۆری یەکە لە خشتەکان
- ژماردنی ئۆتۆماتیکی نرخەکان
- پیشاندانی نرخی یەکەکان

### 3. JavaScript Functions
```javascript
// Update unit type display
function updateUnitTypeDisplay(row, material)

// Fill prices based on unit type
function fillPricesBasedOnUnitType(row, material)

// Calculate unit prices
function calculateUnitPrice()
```

## گۆڕانکارییەکانی بەک ئێند (Backend Changes)

### 1. زیادکردنی کاڵا (`process/add_material/add.php`)
- پشتگیری جۆرەکانی یەکە
- ژماردنی نرخی یەکەکان
- پشتگیری هەموو خانەکان

### 2. نوێکردنەوەی کاڵا (`process/add_material/update.php`)
- نوێکردنەوەی جۆری یەکە
- ژماردنی نرخی یەکەکان
- پشتگیری هەموو خانەکان

### 3. کڕینی کاڵاکان (`process/purchase_materilas/add_purchase.php`)
- پشتگیری جۆرەکانی یەکە لە کڕین
- ژماردنی نرخی یەکەکان
- پشتگیری هەموو خانەکان

## نموونەی بەکارهێنان (Usage Examples)

### نموونەی ١: کارتۆن
```
ناوی کاڵا: تایە
جۆری یەکە: کارتۆن
ژمارەی دانە لە کارتۆن: ٢٤
نرخی کڕین: ١٠٠ دۆلار
نرخی دانە: ٤.١٧ دۆلار (خۆی ژمێردراوە)
```

### نموونەی ٢: بەرمیل
```
ناوی کاڵا: دەرمان
جۆری یەکە: بەرمیل
ژمارەی دەبە لە بەرمیل: ٤
ژمارەی لیتر لە دەبە: ٢٥
نرخی کڕین: ٢٠٠ دۆلار
نرخی لیتر: ٢ دۆلار (خۆی ژمێردراوە)
```

### نموونەی ٣: دەبە
```
ناوی کاڵا: دەرمان
جۆری یەکە: دەبە
ژمارەی لیتر لە دەبە: ٢٥
نرخی کڕین: ٥٠ دۆلار
نرخی لیتر: ٢ دۆلار (خۆی ژمێردراوە)
```

## هەنگاوەکانی جێبەجێکردن (Implementation Steps)

### ١. جێبەجێکردنی داتابەیس
```bash
# Run the database migration
mysql -u username -p database_name < database_migration_unit_system.sql
```

### ٢. نوێکردنەوەی فایلەکان
- `pages/add_material.php` ✅
- `pages/purchase_materila.php` ✅
- `process/add_material/add.php` ✅
- `process/add_material/update.php` ✅
- `process/purchase_materilas/add_purchase.php` ✅
- `assets/js/purchase_materilas/add_purchase.js` ✅

### ٣. تاقیکردنەوە
1. زیادکردنی کاڵایەک بە جۆری یەکەی کارتۆن
2. زیادکردنی کاڵایەک بە جۆری یەکەی بەرمیل
3. زیادکردنی کاڵایەک بە جۆری یەکەی دەبە
4. کڕینی کاڵاکان بە جۆرەکانی یەکەی جیاواز
5. تاقیکردنەوەی ژماردنی نرخی یەکەکان

### ٤. پشتگیری دۆلار و دینار
سیستەمەکە پشتگیری هەردوو دراوەکە دەکات:
- دۆلار (USD)
- دینار (IQD)

## تایبەتمەندییەکان (Features)

### ✅ ئەوەی جێبەجێ کراوە
- [x] ٥ جۆری یەکەی جیاواز
- [x] ژماردنی ئۆتۆماتیکی نرخی یەکەکان
- [x] پشتگیری دۆلار و دینار
- [x] پیشاندانی جۆری یەکە لە خشتەکان
- [x] ژماردنی نرخی یەکەکان لە کڕین
- [x] پشتگیری هەموو خانەکان

### 🔄 ئەوەی دەکرێت زیاد بکرێت
- [ ] ژماردنی ئۆتۆماتیکی بڕی بەردەست
- [ ] هەشکردنی نرخی یەکەکان
- [ ] ڕاپۆرتەکان بۆ نرخی یەکەکان
- [ ] مێژووی گۆڕانکارییەکان

## کێشەکانی ناسراو (Known Issues)

هیچ کێشەیەک نەدۆزرایەوە لە کاتی پەرەپێدان.

## پشتگیری تەکنیکی (Technical Support)

ئەگەر کێشەیەکت هەیە، تکایە:
1. پڕۆبڵەمەکە بە وردەکاری بەردەست بکە
2. لۆگەکان بەردەست بکە
3. ناوی فایل و ژمارەی هێڵ بەردەست بکە

---

**دەستپێک**: ٢٠٢٥-٠١-٢٧  
**دواین نوێکردنەوە**: ٢٠٢٥-٠١-٢٧  
**وەشان**: ١.٠.٠ 