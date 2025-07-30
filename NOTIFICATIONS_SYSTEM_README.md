# Detailed Notifications System

## Overview
ئەم system یە بۆ نیشاندانی وردەکاری چالاکییەکان لە سیستەمەکە دروست کراوە. ئێستا دەتوانین ببینین چی گۆڕدراوە، چی زیادکراوە، و زانیاری زیاتر لە هەر چالاکییەک.

## Features

### 1. **زانیاری زیاتر لە Notifications**
- **Old Values**: بەهای کۆن پێش گۆڕانکاری
- **New Values**: بەهای نوێ دوای گۆڕانکاری  
- **Additional Info**: زانیاری زیاتر لە چالاکییەکە
- **IP Address**: ناونیشانی IP ی بەکارهێنەر

### 2. **UI ی نوێ**
- **Modal بۆ وردەکاری**: کلیک لەسەر ئایکۆنی چاو بۆ بینینی وردەکاری
- **JSON Formatter**: نیشاندانی زانیاری بە شێوەیەکی ڕێکوپێک
- **Color Coding**: ڕەنگی جیاواز بۆ old/new values

### 3. **Helper Functions**
- `createDetailedNotification()`: بۆ زیادکردنی notification بە وردەکاری
- `getUserIP()`: بۆ وەرگرتنی IP ی بەکارهێنەر

## Usage

### 1. **بۆ Insert Operations**
```php
$new_values = [
    'customer_id' => $customer_id,
    'customer_name' => $customer_name,
    'amount' => $amount,
    // ... more fields
];

$additional_info = [
    'action_type' => 'sale_creation',
    'payment_status' => 'paid',
    'currency_used' => 'USD'
];

createDetailedNotification(
    $pdo,
    $_SESSION['user_id'],
    'insert',
    'sales',
    $sale_id,
    "فرۆشتنێکی نوێ زیادکرا (invoice: $invoice_number)",
    null, // No old values for insert
    $new_values,
    $additional_info,
    getUserIP()
);
```

### 2. **بۆ Update Operations**
```php
// Get old values before update
$old_values = [
    'amount' => $old_amount,
    'status' => $old_status
];

// Get new values after update
$new_values = [
    'amount' => $new_amount,
    'status' => $new_status
];

createDetailedNotification(
    $pdo,
    $_SESSION['user_id'],
    'update',
    'sales',
    $sale_id,
    "فرۆشتنەکە نوێکرایەوە (invoice: $invoice_number)",
    $old_values,
    $new_values,
    $additional_info,
    getUserIP()
);
```

### 3. **بۆ Delete Operations**
```php
// Get values before deletion
$old_values = [
    'customer_name' => $customer_name,
    'amount' => $amount,
    'invoice_number' => $invoice_number
];

createDetailedNotification(
    $pdo,
    $_SESSION['user_id'],
    'delete',
    'sales',
    $sale_id,
    "فرۆشتنەکە سڕایەوە (invoice: $invoice_number)",
    $old_values,
    null, // No new values for delete
    $additional_info,
    getUserIP()
);
```

## Database Changes

### 1. **Notifications Table Structure**
```sql
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `old_values` text DEFAULT NULL COMMENT 'JSON format of old values before change',
  `new_values` text DEFAULT NULL COMMENT 'JSON format of new values after change',
  `additional_info` text DEFAULT NULL COMMENT 'Additional context information',
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `seen` tinyint(1) DEFAULT 0
);
```

### 2. **Run Update Script**
```bash
mysql -u root -p dana_concrete_db < update_notifications_structure.sql
```

## UI Features

### 1. **Notifications List**
- **Action Badges**: ڕەنگی جیاواز بۆ insert/update/delete
- **Table Names**: ناوی کوردی بۆ خشتەکان
- **View Details Button**: کلیک بۆ بینینی وردەکاری

### 2. **Details Modal**
- **General Information**: زانیاری گشتی لە چالاکییەکە
- **Old Values**: بەهای کۆن بە ڕەنگی زەرد
- **New Values**: بەهای نوێ بە ڕەنگی شین
- **Additional Info**: زانیاری زیاتر بە ڕەنگی خۆڵەمێش

## Benefits

1. **Transparency**: دەتوانین ببینین چی گۆڕدراوە
2. **Audit Trail**: مێژووی تەواو لە چالاکییەکان
3. **Debugging**: ئاسانتر بۆ دۆزینەوەی کێشەکان
4. **Security**: IP tracking بۆ ئاسایش
5. **User Experience**: وردەکاری زیاتر بۆ بەکارهێنەران

## Implementation Steps

1. **Run SQL Update**: `update_notifications_structure.sql`
2. **Update PHP Files**: بەکارهێنانی `createDetailedNotification()`
3. **Test System**: تاقیکردنەوەی system یە نوێ
4. **Deploy**: بڵاوکردنەوەی بۆ production

## Notes

- **JSON Encoding**: هەموو values بە JSON format خەزن دەکرێن
- **Unicode Support**: پشتگیری کوردی هەیە
- **Error Handling**: هەڵەکان لە error log خەزن دەکرێن
- **Performance**: Indexes بۆ performance باشتر 