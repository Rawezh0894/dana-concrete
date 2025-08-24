# Excel Export Functionality for Purchase System

## Overview
This document describes the Excel export functionality that has been added to the purchase system in the Dana Concrete application.

## Features Added

### 1. Detailed Purchase Export
- **Button Location**: Main header next to "وردەکاری شۆفێرەکان" button
- **Function**: Exports all purchase data to Excel format
- **Filters Applied**: Respects all current filters (company, location, driver, material, date range)
- **File Format**: .xls (Excel 97-2003 compatible)
- **Filename**: `کڕینەکان_YYYY-MM-DD.xls`

### 2. Summary Export
- **Button Location**: Fourth summary card (green gradient card)
- **Function**: Exports purchase summary data to Excel
- **Data Included**: 
  - Total debt amount
  - Total number of companies
  - Number of indebted companies
- **File Format**: .xls (Excel 97-2003 compatible)
- **Filename**: `کورتەی_کڕینەکان_YYYY-MM-DD.xls`

## Technical Implementation

### Files Created/Modified

#### New Files:
- `process/purchase/export_excel.php` - Main export logic

#### Modified Files:
- `pages/add_purchase.php` - Added export buttons and styling
- `assets/js/purchase/purchase.js` - Added detailed export function
- `assets/js/purchase/summary.js` - Added summary export function
- `assets/css/variables.css` - Added warning color variable

### Export Process

1. **User clicks export button**
2. **JavaScript collects current filter values**
3. **AJAX request sent to export_excel.php**
4. **PHP processes request with filters**
5. **Excel file generated with proper Kurdish headers**
6. **File downloaded automatically**

### Data Format

#### Detailed Export Columns:
1. # (Row number)
2. کۆمپانیا (Company)
3. شوێن (Location)
4. شۆفێر (Driver)
5. ژمارەی پسوڵە (Invoice Number)
6. مەواد (Material)
7. بەروار (Date)
8. جۆری پارەدان (Payment Type)
9. جۆری دراو (Currency Type)
10. کیلۆگرام (Kilograms)
11. نرخی یەک کیلۆ بە دۆلار (Price per KG in USD)
12. نرخی یەک کیلۆ بە دینار (Price per KG in IQD)
13. نرخ (Price)
14. بڕی پارە بە دینار (Amount in IQD)
15. نرخی 100 دۆلار بە دینار (Exchange Rate)
16. پارەی دراو بە دۆلار (Paid Amount in USD)
17. پارەی دراو بە دینار (Paid Amount in IQD)
18. پارەی ماوە بە دۆلار (Remaining Amount in USD)
19. پارەی ماوە بە دینار (Remaining Amount in IQD)
20. چاو/سایلۆ (Bin/Silo)

#### Summary Export:
- بەروار (Date)
- کۆی قەرزی ئێمە (Total Debt)
- کۆی ژمارەی کۆمپانیاکان (Total Companies)
- کۆمپانیاکانی قەرزدار (Indebted Companies)

## Usage Instructions

### For Detailed Export:
1. Navigate to the purchase page
2. Apply any desired filters (company, location, driver, material, date range)
3. Click the "ئیکسپۆرتی Excel" button in the header
4. File will automatically download

### For Summary Export:
1. Navigate to the purchase page
2. Apply any desired filters
3. Click the "داگرتن" button in the green summary card
4. Summary Excel file will automatically download

## Styling

### Export Button:
- **Color**: Warning yellow (#ffc107)
- **Hover Effect**: Darker yellow with slight upward movement
- **Icon**: Excel file icon

### Summary Export Card:
- **Background**: Green gradient (#28a745 to #20c997)
- **Button**: Light button with green text
- **Hover Effects**: Enhanced shadow and movement

## Browser Compatibility

- **Modern Browsers**: Full support (Chrome, Firefox, Safari, Edge)
- **File Download**: Uses HTML5 Blob API for automatic download
- **Excel Format**: Compatible with Excel 97-2003 and newer versions

## Security Features

- **Session Validation**: Checks user authentication
- **Permission Check**: Verifies user has 'view_purchase' permission
- **SQL Injection Protection**: Uses prepared statements
- **XSS Protection**: HTML entities encoding for output

## Error Handling

- **Loading States**: Shows loading spinner during export
- **Success Messages**: Confirms successful export
- **Error Messages**: Displays user-friendly error messages
- **Console Logging**: Detailed error logging for developers

## Future Enhancements

- **PDF Export**: Add PDF export option
- **Custom Date Ranges**: Predefined date range buttons
- **Export Formats**: Support for .xlsx (modern Excel format)
- **Email Export**: Send exports via email
- **Scheduled Exports**: Automated export scheduling
