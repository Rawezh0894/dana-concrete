#!/bin/bash
# Fix backup directory permissions script

echo "🔧 Fixing backup directory permissions..."

# Navigate to the project directory
cd "$(dirname "$0")"

# Create backups directory if it doesn't exist
if [ ! -d "backups" ]; then
    echo "📁 Creating backups directory..."
    mkdir -p backups
fi

# Set proper permissions
echo "🔐 Setting permissions..."
chmod 755 backups/

# Try to set ownership to web server user (common web server users)
WEB_USERS=("www-data" "apache" "nginx" "httpd" "www")

for user in "${WEB_USERS[@]}"; do
    if id "$user" &>/dev/null; then
        echo "👤 Found web server user: $user"
        chown -R "$user:$user" backups/
        echo "✅ Set ownership to $user:$user"
        break
    fi
done

# Test write permissions
echo "🧪 Testing write permissions..."
TEST_FILE="backups/permission_test_$(date +%s).txt"
echo "Test content" > "$TEST_FILE"

if [ -f "$TEST_FILE" ]; then
    echo "✅ Write test successful"
    rm "$TEST_FILE"
    echo "🧹 Cleaned up test file"
else
    echo "❌ Write test failed"
fi

# Show final status
echo "📊 Final status:"
ls -la backups/

echo "🎉 Permission fix completed!"
