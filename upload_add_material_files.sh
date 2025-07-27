#!/bin/bash

# Script to upload add_material files to server
# Run this from the local dana-concrete directory

echo "Uploading add_material files to server..."

# Create directory structure on server
ssh root@31.97.79.253 "mkdir -p /var/www/dana-concrete/process/add_material"

# Upload files
scp process/add_material/add.php root@31.97.79.253:/var/www/dana-concrete/process/add_material/
scp process/add_material/select.php root@31.97.79.253:/var/www/dana-concrete/process/add_material/
scp process/add_material/update.php root@31.97.79.253:/var/www/dana-concrete/process/add_material/
scp process/add_material/delete.php root@31.97.79.253:/var/www/dana-concrete/process/add_material/

# Set permissions
ssh root@31.97.79.253 "chmod 644 /var/www/dana-concrete/process/add_material/*.php"
ssh root@31.97.79.253 "chown www-data:www-data /var/www/dana-concrete/process/add_material/*.php"

echo "Files uploaded successfully!"
echo "Now run on server:"
echo "cd /var/www/dana-concrete"
echo "php simple_test.php"
echo "mysql -u dana_user -p dana_concrete_db < create_list_materials.sql" 