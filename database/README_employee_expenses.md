# دامەزراندنی سیستەمی خەرجی کارمەند

## پێش دامەزراندن

پێش دامەزراندنی ئەم سیستەمە، دڵنیابوونەوە لەوەی کە:
1. داتابەیس `dana_concrete_db` دامەزراوە
2. تەیبڵی `employees` و `users` هەیە
3. دەستگەیشتنی بە داتابەیس هەیە

## دامەزراندن

### 1. جێبەجێکردنی کوئری SQL

کوئرییەکانی لە فایلی `employee_expenses_setup.sql` جێبەجێ بکە لە داتابەیسەکەتدا:

```sql
-- دەتوانیت هەموو کوئرییەکان لە فایلەکە جێبەجێ بکەیت
```

ئەم کوئریانە:
- خانەکانی نوێ زیاد دەکەن بە تەیبڵی `employees`
- تەیبڵی `employee_expenses` دروست دەکەن
- Triggerەکان دروست دەکەن بۆ ئاپدەیتکردنی باڵانسەکان

### 2. تێبینی گرنگ

- کوئرییەکان بە شێوەیەکی خۆکار دەڕوانن کە خانەکان هەبن یان نا
- Triggerەکان بە شێوەیەکی خۆکار باڵانسەکانی کارمەند ئاپدەیت دەکەن
- تەیبڵی `employee_expenses` پشتگیری لە جۆرەکانی خوارەوە دەکات:
  - `salary` - مووچە
  - `bonus` - بەخشیش
  - `overtime` - کاروانحیسابی
  - `advance` - پێشەکی/قەرز
  - `deduction` - کەمکردنەوە
  - `penalty` - سزا

### 3. بەکارهێنان

دوای دامەزراندن:
1. بچۆ بۆ پەیجی "پارەدان بە کارمەندەکان"
2. دەتوانیت لە یەک کاتدا هەم مووچە و هەم بەخشیش و هەم کاروانحیسابی و هەم پێشەکی و هەم کەمکردنەوە و هەم سزا بنووسیت
3. کارتەکانی باڵانس بە شێوەیەکی خۆکار ئاپدەیت دەبن
4. فلتەرەکان بە کارمەند و مانگ کار دەکەن

## گۆڕانکارییەکان

### تەیبڵی employees
- زیادکراوەکان:
  - `full_name` - ناوی تەواو
  - `position` - پۆست
  - `status` - دۆخ (active, inactive, on_leave, resigned)
  - `join_date` - بەرواری بەشداری
  - `phone` - تەلەفۆن
  - `payable_balance` - باڵانسی قەرزی کۆمپانیا
  - `receivable_balance` - باڵانسی قەرزی کارمەند
  - `notes` - تێبینی
  - `salary_start_date` - بەرواری دەستپێکردنی مووچە

### تەیبڵی employee_expenses (نوێ)
- `id` - ID
- `employee_id` - IDی کارمەند
- `expense_type` - جۆری خەرجی
- `amount` - بڕ
- `notes` - تێبینی
- `created_by` - دروستکراوە لەلایەن
- `expense_date` - بەرواری خەرجی (YYYY-MM)
- `created_at` - بەرواری دروستکردن
- `updated_at` - بەرواری نوێکردنەوە

## Triggerەکان

سێ trigger دروست دەکرێن بۆ ئاپدەیتکردنی باڵانسەکان:
1. `trg_after_insert_employee_expense_balance` - دوای زیادکردن
2. `trg_after_update_employee_expense_balance` - دوای نوێکردنەوە
3. `trg_after_delete_employee_expense_balance` - دوای سڕینەوە

## لۆجیکی باڵانس

### مووچە/بەخشیش/کاروانحیسابی
- زیاد بە `payable_balance` (کۆمپانیا قەرزی کارمەندە)
- نموونە: مووچە 600000 → payable_balance = 600000

### پێشەکی (Advance)
- یەکەم لە `payable_balance` کەم دەکات (لە مووچە دەکەم)
- ئەگەر `payable_balance` کەم بوو، زیاد بە `receivable_balance`
- نموونە:
  - مووچە 600000 → payable_balance = 600000
  - پێشەکی 200000 → payable_balance = 400000 (600000 - 200000)
  - پێشەکی تر 500000 → payable_balance = 0, receivable_balance = 100000

### کەمکردنەوە/سزا
- یەکەم لە `payable_balance` کەم دەکات
- ئەگەر `payable_balance` کەم بوو، زیاد بە `receivable_balance`

## فایلەکانی نوێ

### PHP
- `process/employee_payments/add_expense.php` - زیادکردنی خەرجی نوێ
- `process/employee_payments/select_expenses.php` - وەرگرتنی خەرجیەکان
- `process/employee_payments/get_balances.php` - وەرگرتنی باڵانسەکان
- `process/employee_payments/delete_expense.php` - سڕینەوەی خەرجی

### JavaScript
- `assets/js/employee_payments/add_expense.js` - فۆرمی زیادکردن
- `assets/js/employee_payments/select_expenses.js` - پیشاندانی خەرجیەکان
- `assets/js/employee_payments/balances.js` - کارتەکانی باڵانس

## پشتیوانی

ئەگەر هەر کێشەیەکت هەبوو، تکایە پەیوەندی بە بەڕێوەبەری سیستەمەکە بکە.

