# تریگەرەکانی بەکارهێنانی کاڵای کۆگا

## مەبەست
ئەم تریگەرانە بەردەست لە کۆگا بە شێوەیەکی ئۆتۆماتیکی بەڕێوە دەبەن کاتێک خەرجی بەکارهێنانی کاڵای کۆگا زیاد دەکرێت، نوێ دەکرێتەوە، یان سڕ دەکرێتەوە.

## تریگەرەکان

### 1. تریگەری زیادکردن (INSERT)
**ناو:** `trg_after_insert_other_expenses`  
**کاتی جێبەجێکردن:** دوای زیادکردنی خەرجی

```sql
-- Handle material consumption for warehouse material usage
IF NEW.expense_type = 'بەکارهێنانی کاڵای کۆگا' AND NEW.material_id IS NOT NULL AND NEW.material_quantity IS NOT NULL AND NEW.material_quantity > 0 THEN
    UPDATE list_materials
    SET quantity = quantity - NEW.material_quantity
    WHERE id = NEW.material_id;
END IF;
```

**کردار:**
- بەردەست لە `list_materials.quantity` کەم دەکات بە `material_quantity`
- تەنها کاتێک `expense_type = 'بەکارهێنانی کاڵای کۆگا'` و `material_quantity > 0`

### 2. تریگەری سڕینەوە (DELETE)
**ناو:** `trg_before_delete_other_expenses`  
**کاتی جێبەجێکردن:** پێش سڕینەوەی خەرجی

```sql
-- Handle material restoration
IF OLD.expense_type = 'بەکارهێنانی کاڵای کۆگا' AND OLD.material_id IS NOT NULL AND OLD.material_quantity IS NOT NULL AND OLD.material_quantity > 0 THEN
    UPDATE list_materials
    SET quantity = quantity + OLD.material_quantity
    WHERE id = OLD.material_id;
END IF;
```

**کردار:**
- بەردەست لە `list_materials.quantity` زیاد دەکات بە `material_quantity`
- بەکارهێنراوەکان بەردەگەڕێنرێتەوە بۆ کۆگا

### 3. تریگەری نوێکردنەوە (UPDATE)
**ناو:** `trg_before_update_other_expenses` و `trg_after_update_other_expenses`  
**کاتی جێبەجێکردن:** پێش و دوای نوێکردنەوەی خەرجی

#### پێش نوێکردنەوە:
```sql
-- Handle material changes (BEFORE UPDATE)
IF OLD.expense_type = 'بەکارهێنانی کاڵای کۆگا' AND OLD.material_id IS NOT NULL AND OLD.material_quantity IS NOT NULL AND OLD.material_quantity > 0 THEN
    -- Restore old material quantity
    UPDATE list_materials
    SET quantity = quantity + OLD.material_quantity
    WHERE id = OLD.material_id;
END IF;
```

#### دوای نوێکردنەوە:
```sql
-- Handle new material consumption (AFTER UPDATE)
IF NEW.expense_type = 'بەکارهێنانی کاڵای کۆگا' AND NEW.material_id IS NOT NULL AND NEW.material_quantity IS NOT NULL AND NEW.material_quantity > 0 THEN
    UPDATE list_materials
    SET quantity = quantity - NEW.material_quantity
    WHERE id = NEW.material_id;
END IF;
```

**کردار:**
1. **پێش نوێکردنەوە:** بەردەست زیاد دەکات بە بڕی کۆن
2. **دوای نوێکردنەوە:** بەردەست کەم دەکات بە بڕی نوێ
3. **ئەنجام:** تەنها جیاوازییەکە بەکار دەهێنرێت

## نموونەکان

### نموونەی 1: زیادکردنی خەرجی بەکارهێنانی کاڵای کۆگا
```
کاڵا: سیمەنت
بڕی بەکارهاتوو: 50 بەگ
بەردەست لە کۆگا: 1000 بەگ

دوای زیادکردن:
بەردەست لە کۆگا: 950 بەگ (1000 - 50)
```

### نموونەی 2: نوێکردنەوەی خەرجی بەکارهێنانی کاڵای کۆگا
```
کاڵا: سیمەنت
بڕی کۆن: 50 بەگ
بڕی نوێ: 80 بەگ
بەردەست لە کۆگا: 950 بەگ

پێش نوێکردنەوە:
بەردەست لە کۆگا: 1000 بەگ (950 + 50)

دوای نوێکردنەوە:
بەردەست لە کۆگا: 920 بەگ (1000 - 80)

ئەنجام: تەنها 30 بەگ زیاتر بەکارهێنرا
```

### نموونەی 3: سڕینەوەی خەرجی بەکارهێنانی کاڵای کۆگا
```
کاڵا: سیمەنت
بڕی بەکارهاتوو: 80 بەگ
بەردەست لە کۆگا: 920 بەگ

دوای سڕینەوە:
بەردەست لە کۆگا: 1000 بەگ (920 + 80)
```

## پشتڕاستکردنەوە

### پشتڕاستکردنەوەی PHP
پێش زیادکردن یان نوێکردنەوە، PHP کۆدەکە پشتڕاست دەکاتەوە کە بەردەست بەسە:

```php
// Check material availability for warehouse material usage
if ($expense_type === 'بەکارهێنانی کاڵای کۆگا' && $material_id && $material_quantity) {
    // Get current stock quantity for the material
    $stock_sql = "SELECT quantity, name FROM list_materials WHERE id = ?";
    $stock_stmt = $pdo->prepare($stock_sql);
    $stock_stmt->execute([$material_id]);
    $material_stock = $stock_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$material_stock) {
        echo json_encode(['success' => false, 'msg' => 'کاڵا نەدۆزرایەوە']);
        exit;
    }
    
    $available_quantity = floatval($material_stock['quantity']);
    $required_quantity = floatval($material_quantity);
    
    if ($available_quantity < $required_quantity) {
        echo json_encode([
            'success' => false, 
            'msg' => "بڕی پێویست لە کۆگا نەماوە. بڕی بەردەست: {$available_quantity}، بڕی پێویست: {$required_quantity}"
        ]);
        exit;
    }
}
```

## پێویستییەکان

### پێویستی داتابەیس
- تەیبڵی `other_expenses` بە ستوونی `expense_type`, `material_id`, `material_quantity`
- تەیبڵی `list_materials` بە ستوونی `id`, `quantity`

### پێویستی کۆد
- PHP validation بۆ پشتڕاستکردنەوەی بەردەست
- JavaScript بۆ پشتڕاستکردنەوەی بەردەست لە client-side

## هەڵەکان

### هەڵەی نەبوونی کاڵا
```
هەڵە: کاڵا نەدۆزرایەوە
```

### هەڵەی نەبوونی بەردەست
```
هەڵە: بڕی پێویست لە کۆگا نەماوە. بڕی بەردەست: X، بڕی پێویست: Y
```

## تێستکردن

### تێستکردنی زیادکردن
1. خەرجی بەکارهێنانی کاڵای کۆگا زیاد بکە
2. پشتڕاست بکەوە کە بەردەست لە `list_materials` کەم بووە

### تێستکردنی نوێکردنەوە
1. خەرجی بەکارهێنانی کاڵای کۆگا نوێ بکەوە
2. پشتڕاست بکەوە کە تەنها جیاوازییەکە بەکارهێنرا

### تێستکردنی سڕینەوە
1. خەرجی بەکارهێنانی کاڵای کۆگا بسڕەوە
2. پشتڕاست بکەوە کە بەردەست بەردەگەڕێنرایەوە

## سوودەکان

1. **ئۆتۆماتیکی:** بەردەست بە شێوەیەکی ئۆتۆماتیکی بەڕێوە دەبێت
2. **دەستەواژە:** هەموو کردارەکان دەستەواژەن - یان هەموو سەرکەوتوون یان هەموو شکست دەهێنن
3. **هیچ هەڵەیەکی دەستی:** ئەگەری لەبیرچوونی بەڕێوەبردنی بەردەست نییە
4. **گەڕانەوەی دروست:** کاتێک کردارەکان شکست دەهێنن، هەموو گۆڕانکارییەکان بە دروستی دەگەڕێنرێتەوە
5. **شوێنەوە:** هەموو گۆڕانکارییەکان بە شێوەیەکی دروست شوێنەوە دەکرێن 