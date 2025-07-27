<?php
// Check error logs for add_material issues
echo "<h2>Error Logs Check</h2>";

// Check PHP error log
$phpErrorLog = ini_get('error_log');
echo "<h3>PHP Error Log:</h3>";
echo "Path: " . ($phpErrorLog ?: 'Not set') . "<br>";

if ($phpErrorLog && file_exists($phpErrorLog)) {
    echo "File exists: Yes<br>";
    $lastLines = shell_exec("tail -20 $phpErrorLog 2>/dev/null");
    if ($lastLines) {
        echo "<pre>Last 20 lines:\n$lastLines</pre>";
    } else {
        echo "No recent errors<br>";
    }
} else {
    echo "File does not exist or not accessible<br>";
}

// Check Apache error log
echo "<h3>Apache Error Log:</h3>";
$apacheLogs = [
    '/var/log/apache2/error.log',
    '/var/log/httpd/error_log',
    '/var/log/apache2/error.log.1'
];

foreach ($apacheLogs as $log) {
    if (file_exists($log)) {
        echo "Found: $log<br>";
        $lastLines = shell_exec("tail -10 $log 2>/dev/null | grep -i 'add_material\|dana-concrete'");
        if ($lastLines) {
            echo "<pre>Relevant errors:\n$lastLines</pre>";
        } else {
            echo "No relevant errors in this log<br>";
        }
        break;
    }
}

// Check Nginx error log
echo "<h3>Nginx Error Log:</h3>";
$nginxLogs = [
    '/var/log/nginx/error.log',
    '/var/log/nginx/error.log.1'
];

foreach ($nginxLogs as $log) {
    if (file_exists($log)) {
        echo "Found: $log<br>";
        $lastLines = shell_exec("tail -10 $log 2>/dev/null | grep -i 'add_material\|dana-concrete'");
        if ($lastLines) {
            echo "<pre>Relevant errors:\n$lastLines</pre>";
        } else {
            echo "No relevant errors in this log<br>";
        }
        break;
    }
}

// Check current directory error log
echo "<h3>Current Directory Error Log:</h3>";
if (file_exists('php-error.log')) {
    echo "Found: php-error.log<br>";
    $lastLines = shell_exec("tail -20 php-error.log 2>/dev/null");
    if ($lastLines) {
        echo "<pre>Last 20 lines:\n$lastLines</pre>";
    } else {
        echo "No recent errors<br>";
    }
} else {
    echo "php-error.log not found<br>";
}

// Check system logs
echo "<h3>System Logs:</h3>";
$systemLogs = shell_exec("journalctl -u apache2 --since '1 hour ago' 2>/dev/null | grep -i 'error\|fail' | tail -10");
if ($systemLogs) {
    echo "<pre>Recent system errors:\n$systemLogs</pre>";
} else {
    echo "No recent system errors<br>";
}
?> 