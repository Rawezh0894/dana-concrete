# چارەسەرکردنی کێشەکانی پەیجی کڕین (Purchase Page)

## کێشەکان
1. **ستوونەکان تێکەڵ بوون و چوونە ناو یەکدا** - هەموو ستوونەکانی خشتەکە زۆر تەنگ بوون و یەکتر دەپۆشینەوە
2. **فلتەری بەروار بەتاڵ بوو** - پێویست بوو بە شێوەی دیفۆڵت فلتەر لە سەرەتای مانگەوە بۆ کۆتایی مانگی ئێستا دابنرێت

## چارەسەرەکان

### 1. چارەسەری تێکەڵبوونی ستوونەکان
**فایل**: `assets/js/purchase/ag_grid_purchase.js`

**کێشە**: 
- کۆدی `params.api.sizeColumnsToFit()` هەوڵی دەدا هەموو ستوونەکان بخاتە ناو بەشی دیار، کە دەیبووە هۆی تەنگبوون و تێکەڵبوونیان

**چارەسەر**:
- لابردنی `sizeColumnsToFit()` 
- بەکارهێنانی `autoSizeAllColumns(false)` بۆ ڕێگەدان بە ستوونەکان کە پانی خۆیان (minWidth: 150px) بپارێزن
- ئێستا بەکارهێنەر دەتوانێت بە شێوەی ئاسۆیی scroll بکات بۆ بینینی هەموو ستوونەکان

**گۆڕانکاری**:
```javascript
onFirstDataRendered: function(params) {
    // Don't auto-size columns to fit - let them maintain their minWidth
    // This prevents columns from overlapping when there are many columns
    params.api.autoSizeAllColumns(false);
}
```

### 2. دانانی فلتەری دیفۆڵت بۆ بەروار
**فایل**: `assets/js/purchase/ag_grid_purchase.js`

**چارەسەر**:
- زیادکردنی فەنکشنی `setDefaultDateFilter()` کە بە شێوەی ئۆتۆماتیک بەروارەکان دادەنێت
- فلتەری "لە بەروار" دادەنرێت بۆ یەکەمین ڕۆژی مانگی ئێستا
- فلتەری "بۆ بەروار" دادەنرێت بۆ کۆتایین ڕۆژی مانگی ئێستا
- ئەم فەنکشنە بە شێوەی ئۆتۆماتیک کار دەکات کاتێک پەیجەکە دەکرێتەوە

**گۆڕانکاری**:
```javascript
// Function to set default date range to current month
function setDefaultDateFilter() {
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    
    // Format dates as YYYY-MM-DD
    const formatDate = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };
    
    const filterFrom = document.getElementById('filter_from');
    const filterTo = document.getElementById('filter_to');
    
    if (filterFrom && filterTo) {
        filterFrom.value = formatDate(firstDay);
        filterTo.value = formatDate(lastDay);
    }
}

// Call on page load
document.addEventListener('DOMContentLoaded', function () {
    setDefaultDateFilter();
    // ... rest of initialization
});
```

## ئەنجام
✅ ستوونەکان ئێستا پانی تەواویان هەیە و تێکەڵ نابن
✅ بەکارهێنەر دەتوانێت بە ئاسانی scroll بکات بۆ بینینی هەموو ستوونەکان
✅ فلتەری بەروار بە شێوەی ئۆتۆماتیک دادەنرێت بۆ مانگی ئێستا
✅ بەکارهێنەر دەتوانێت فلتەرەکان بگۆڕێت یان پاکیان بکاتەوە بەپێی پێویست

## تێبینی
- ئەگەر بەکارهێنەر بیەوێت هەموو داتاکان ببینێت (بەبێ فلتەری بەروار)، دەتوانێت دوگمەی "پاککردنەوەی هەموو فلتەرەکان" بکات کلیک
- ستوونەکان دەتوانرێن resize بکرێن بە ڕاکێشانی سنوورەکانیان
- خشتەکە ئێستا بە شێوەی باشتر کار دەکات لەگەڵ ژمارەیەکی زۆر ستوون
