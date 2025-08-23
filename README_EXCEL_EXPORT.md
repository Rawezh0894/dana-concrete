# Excel Export Functionality Setup

## Overview
This system now includes Excel export functionality for purchase data. Users can export filtered purchase data to Excel format with professional styling.

## Features
- **Filtered Export**: Export data based on current filters (company, location, driver, material, date range)
- **Professional Styling**: Excel file includes headers, borders, alternating row colors, and proper formatting
- **Summary Information**: Includes total record count and applied filters
- **Automatic Filename**: Files are named with current date and time
- **Permission Control**: Only users with view_purchase permission can export

## Installation

### 1. Install Composer Dependencies
```bash
composer install
```

### 2. Verify Installation
Make sure the following files exist:
- `vendor/autoload.php`
- `vendor/phpoffice/phpspreadsheet/`

### 3. File Permissions
Ensure the web server has write permissions to the vendor directory.

## Usage

### Frontend
1. **Apply Filters**: Use the filter dropdowns to select specific data
2. **Click Export**: Click the "ئیکسپۆرت بۆ Excel" button
3. **Download**: File will automatically download with timestamp

### Backend
The export functionality is handled by:
- `process/purchase/export_excel.php` - Main export script
- Supports all filter parameters
- Generates professional Excel files

## File Structure
```
├── pages/
│   └── add_purchase.php          # Main page with export button
├── process/purchase/
│   └── export_excel.php          # Export backend script
├── composer.json                  # Dependencies configuration
└── vendor/                       # Composer packages (after install)
```

## Technical Details

### Dependencies
- **PhpSpreadsheet**: Professional Excel generation library
- **PHP 7.4+**: Required for modern PHP features
- **Composer**: Package management

### Export Features
- **Headers**: Professional styling with green background
- **Data Rows**: Alternating colors for readability
- **Column Widths**: Optimized for content
- **Borders**: Clean, professional appearance
- **Summary**: Record count and filter information

### Security
- **Session Validation**: User must be logged in
- **Permission Check**: Requires view_purchase permission
- **Input Validation**: All parameters are sanitized
- **SQL Injection Protection**: Uses prepared statements

## Troubleshooting

### Common Issues

#### 1. Composer Not Installed
```bash
# Install Composer globally
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

#### 2. Permission Denied
```bash
# Fix vendor directory permissions
sudo chown -R www-data:www-data vendor/
sudo chmod -R 755 vendor/
```

#### 3. Memory Limit
If you encounter memory issues with large exports:
```php
// Add to export_excel.php
ini_set('memory_limit', '512M');
```

#### 4. Timeout Issues
For large datasets, increase execution time:
```php
// Add to export_excel.php
set_time_limit(300); // 5 minutes
```

### Error Logging
All export errors are logged to the PHP error log. Check:
- Apache error log
- PHP error log
- System error log

## Customization

### Adding New Columns
To add new columns to the export:

1. **Update SQL Query** in `export_excel.php`
2. **Add Header** to the headers array
3. **Set Column Width** in columnWidths array
4. **Add Data Cell** in the data loop

### Styling Changes
Modify the style arrays in `export_excel.php`:
- `$headerStyle` - Header row styling
- `$dataStyle` - Data row styling
- `$summaryStyle` - Summary row styling

### File Format
The system exports to `.xlsx` format (Excel 2007+). To support older formats:
```php
use PhpOffice\PhpSpreadsheet\Writer\Xls;
$writer = new Xls($spreadsheet);
```

## Performance Considerations

### Large Datasets
- **Pagination**: Consider implementing chunked exports
- **Memory**: Monitor memory usage for large exports
- **Timeout**: Set appropriate execution time limits

### Caching
For frequently exported data, consider:
- **File Caching**: Store generated files temporarily
- **Database Caching**: Cache query results
- **CDN Integration**: Serve files from CDN

## Support

For issues or questions:
1. Check the error logs
2. Verify Composer installation
3. Check file permissions
4. Ensure PHP version compatibility

## Future Enhancements

Potential improvements:
- **PDF Export**: Add PDF generation capability
- **Email Integration**: Send exports via email
- **Scheduled Exports**: Automated export generation
- **Template System**: Customizable export templates
- **Multi-format Support**: CSV, JSON, XML exports
