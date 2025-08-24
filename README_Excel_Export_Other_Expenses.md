# Excel Export Functionality for Other Expenses System

## Overview
This document describes the Excel export functionality that has been added to the other expenses system in the Dana Concrete application.

## Features Added

### 1. Detailed Other Expenses Export
- **Button Location**: Header next to title and filter section
- **Function**: Exports all other expenses data to Excel format
- **Filters Applied**: Respects all current filters (date, month, car, employee, person, expense types)
- **File Format**: .xls (Excel 97-2003 compatible)
- **Filename**: `خەرجی_تر_YYYY-MM-DD.xls`

### 2. Summary Export
- **Button Location**: Fifth summary card (green gradient card)
- **Function**: Exports other expenses summary data to Excel
- **Data Included**: 
  - Car material costs
  - Car gas costs
  - Other expenses
  - Total expenses
  - Total count
- **File Format**: .xls (Excel 97-2003 compatible)
- **Filename**: `کورتەی_خەرجی_تر_YYYY-MM-DD.xls`

## Technical Implementation

### Files Created/Modified

#### New Files:
- `process/other_expenses/export_excel.php` - Main export logic

#### Modified Files:
- `pages/other_expenses.php` - Added export buttons and styling
- `assets/js/other_expenses/other_expenses.js` - Added export functions

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
2. مەبەست (Purpose)
3. کەس (Person)
4. کارمەند (Employee)
5. سەیارە (Car)
6. بڕی گاز (لیتر) (Gas Liters)
7. جۆری خەرجی (Expense Type)
8. کاڵا لە کۆگا (Material from Warehouse)
9. بڕی عەدەدی کاڵا (Material Quantity)
10. نرخی کڕینی کاڵا بە دینار (Material Purchase Price IQD)
11. نرخی کڕینی کاڵا بە دۆلار (Material Purchase Price USD)
12. کۆی نرخی کاڵای بەکارهاتوو (Total Material Cost)
13. ئینپوتی نرخی کڕینی گاز (Gas Purchase Price Input)
14. کۆی نرخی گازی بەکارهاتوو (Total Gas Cost)
15. جۆری مامەڵە (Payment Type)
16. جۆری پارە (Currency Type)
17. ژمارەی وەسڵ (Invoice Number)
18. بڕی دینار (Amount IQD)
19. بڕی دۆلار (Amount USD)
20. پارەی دراو دینار (Paid IQD)
21. پارەی دراو دۆلار (Paid USD)
22. نرخی 100 دۆلار (Exchange Rate)
23. ماوە دینار (Remaining IQD)
24. ماوە دۆلار (Remaining USD)
25. بەروار (Date)

#### Summary Export:
- بەروار (Date)
- خەرجی سەیارەکان (کاڵا) (Car Material Costs)
- خەرجی سەیارەکان (گاز) (Car Gas Costs)
- خەرجی تر (Other Expenses)
- کۆی گشتی (Total)
- کۆی خەرجی (Total Count)

## Usage Instructions

### For Detailed Export:
1. Navigate to the other expenses page
2. Apply any desired filters (date, month, car, employee, person, expense types)
3. Click the "ئیکسپۆرتی Excel" button in the header or filter section
4. File will automatically download

### For Summary Export:
1. Navigate to the other expenses page
2. Apply any desired filters
3. Click the "داگرتن" button in the green summary card
4. Summary Excel file will automatically download

## Filters Supported

### Date Filters:
- **Date From**: Start date for filtering
- **Date To**: End date for filtering
- **Month**: Specific month filter (YYYY-MM format)

### Entity Filters:
- **Car**: Filter by specific car
- **Employee**: Filter by specific employee
- **Person**: Filter by specific person

### Expense Type Filters:
- خەرجی تر (Other Expenses)
- بەکارهێنانی کاڵای کۆگا (Warehouse Material Usage)
- بەکارهێنانی گاز (Gas Usage)
- خواردنگە (Food)
- ئۆفیس (Office)

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
- **Permission Check**: Verifies user has 'view_other_expenses' permission
- **SQL Injection Protection**: Uses prepared statements
- **XSS Protection**: HTML entities encoding for output

## Error Handling

- **Loading States**: Shows loading spinner during export
- **Success Messages**: Confirms successful export
- **Error Messages**: Displays user-friendly error messages
- **Console Logging**: Detailed error logging for developers

## Data Relationships

The export system properly handles the following database relationships:
- `other_expenses` → `person_other_expenses` (Person details)
- `other_expenses` → `employees` (Employee details)
- `other_expenses` → `cars` (Car details)
- `other_expenses` → `materials` (Material details)

## Performance Considerations

- **Filtered Queries**: Only exports data matching current filters
- **Efficient Joins**: Uses LEFT JOINs for optional relationships
- **Indexed Fields**: Assumes proper indexing on date and filter fields

## Future Enhancements

- **PDF Export**: Add PDF export option
- **Custom Date Ranges**: Predefined date range buttons
- **Export Formats**: Support for .xlsx (modern Excel format)
- **Email Export**: Send exports via email
- **Scheduled Exports**: Automated export scheduling
- **Batch Export**: Export multiple expense types separately
