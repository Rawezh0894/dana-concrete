#!/bin/bash

# Better backup script for dana_concrete_db
# سکریپتی باشتر بۆ باک ئەپ کردنی داتابەیس

# Set variables
DB_NAME="dana_concrete_db"
BACKUP_FILE="dana_concrete_db_backup_$(date +%Y%m%d_%H%M%S).sql"
REMOTE_HOST="root@31.97.79.253"

echo "Starting backup of $DB_NAME..."

# Create backup with proper character encoding and triggers
ssh $REMOTE_HOST "mysqldump -u root -p \
  --single-transaction \
  --routines \
  --triggers \
  --events \
  --default-character-set=utf8mb4 \
  --set-charset \
  --add-drop-database \
  --add-drop-table \
  --add-drop-trigger \
  --add-drop-procedure \
  --add-drop-function \
  --add-drop-event \
  --hex-blob \
  --complete-insert \
  --extended-insert=FALSE \
  --lock-tables=FALSE \
  $DB_NAME" > "database/$BACKUP_FILE"

# Check if backup was successful
if [ $? -eq 0 ]; then
    echo "Backup completed successfully: $BACKUP_FILE"
    echo "File size: $(du -h database/$BACKUP_FILE | cut -f1)"
else
    echo "Backup failed!"
    exit 1
fi 