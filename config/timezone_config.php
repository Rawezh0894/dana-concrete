<?php
/**
 * Timezone Configuration File
 * ئەم فایلە بۆ ڕێکخستنی timezone بەکارهێنراوە
 */

// Set default timezone for PHP
date_default_timezone_set('Asia/Baghdad'); // Iraq timezone (UTC+3)

// Function to get current Iraq time
function getIraqTime() {
    $date = new DateTime('now', new DateTimeZone('Asia/Baghdad'));
    return $date->format('Y-m-d H:i:s');
}

// Function to convert UTC time to Iraq time
function convertUTCToIraqTime($utcTime) {
    $date = new DateTime($utcTime, new DateTimeZone('UTC'));
    $date->setTimezone(new DateTimeZone('Asia/Baghdad'));
    return $date->format('Y-m-d H:i:s');
}

// Function to convert Iraq time to UTC
function convertIraqTimeToUTC($iraqTime) {
    $date = new DateTime($iraqTime, new DateTimeZone('Asia/Baghdad'));
    $date->setTimezone(new DateTimeZone('UTC'));
    return $date->format('Y-m-d H:i:s');
}

// Function to get timezone offset
function getTimezoneOffset() {
    $date = new DateTime('now', new DateTimeZone('Asia/Baghdad'));
    return $date->format('P'); // Returns +03:00
}

// Function to set MySQL timezone
function setMySQLTimezone($pdo) {
    try {
        $pdo->exec("SET time_zone = '+03:00'");
        return true;
    } catch (Exception $e) {
        error_log("Error setting MySQL timezone: " . $e->getMessage());
        return false;
    }
}

// Function to get current timestamp in Iraq timezone
function getCurrentTimestamp() {
    return date('Y-m-d H:i:s');
}

// Function to format date for display
function formatDateForDisplay($dateString, $format = 'Y-m-d H:i:s') {
    $date = new DateTime($dateString);
    return $date->format($format);
}

// Function to validate if date is in correct format
function isValidDate($dateString) {
    $date = DateTime::createFromFormat('Y-m-d H:i:s', $dateString);
    return $date && $date->format('Y-m-d H:i:s') === $dateString;
}
?>
