# چارەسەرکردنی کێشەی تێکەڵبوونی ستوونەکان لە پەیجی کڕیارەکان (Customers Page)

## کێشە
**ستوونەکانی خشتەی کڕیارەکان تەنگ بوون و یەکتر دەپۆشینەوە**

## چارەسەر

### فایلی گۆڕدراو
`assets/js/customer/ag_grid_customer.js`

### کێشەی سەرەکی
کۆدی `params.api.sizeColumnsToFit()` هەوڵی دەدا هەموو ستوونەکان بخاتە ناو بەشی دیار لە شاشەکە، کە دەیبووە هۆی تەنگبوون و تێکەڵبوونی ستوونەکان.

### چارەسەرەکە
- لابردنی `sizeColumnsToFit()` 
- بەکارهێنانی `autoSizeAllColumns(false)` بۆ ڕێگەدان بە ستوونەکان کە پانی خۆیان بپارێزن
- ئێستا بەکارهێنەر دەتوانێت بە شێوەی ئاسۆیی scroll بکات بۆ بینینی هەموو ستوونەکان

### گۆڕانکاری
```javascript
onFirstDataRendered: function(params) {
    // Don't auto-size columns to fit - let them maintain their minWidth
    // This prevents columns from overlapping when there are many columns
    params.api.autoSizeAllColumns(false);
}
```

**پێش چارەسەر**:
```javascript
onFirstDataRendered: function(params) {
    // Auto-size columns
    params.api.sizeColumnsToFit();
}
```

## ئەنجام
✅ ستوونەکان ئێستا پانی تەواویان هەیە و تێکەڵ نابن
✅ بەکارهێنەر دەتوانێت بە ئاسانی scroll بکات بۆ بینینی هەموو ستوونەکان
✅ خشتەکە ئێستا بە شێوەی باشتر کار دەکات

## تێبینی
- ستوونەکان دەتوانرێن resize بکرێن بە ڕاکێشانی سنوورەکانیان
- هەموو ستوونەکان لانیکەم پانی 100px یان زیاترەیان هەیە
- ئەم چارەسەرە هەمان چارەسەرەیە کە بۆ پەیجی کڕین (Purchase) بەکارهێنرا

## پەیجە چارەسەرکراوەکان
1. ✅ `pages/add_purchase.php` - پەیجی کڕین
2. ✅ `pages/add_customers.php` - پەیجی کڕیارەکان
