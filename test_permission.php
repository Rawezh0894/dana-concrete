<?php
session_start();
require_once 'config/permissions.php';

// تاقیکردنەوەی پێرمیشنەکە
echo "<h2>تاقیکردنەوەی پێرمیشن</h2>";

// بینینی داتای session
echo "<h3>داتای Session:</h3>";
echo "<pre>";
var_dump($_SESSION);
echo "</pre>";

// بینینی ڕۆڵی بەکارهێنەر
$user_role = $_SESSION['user_role'] ?? 'no role';
echo "<h3>ڕۆڵی بەکارهێنەر: " . $user_role . "</h3>";

// تاقیکردنەوەی پێرمیشنی show_add_sale_button
$has_permission = hasPermission('show_add_sale_button');
echo "<h3>پێرمیشنی show_add_sale_button: " . ($has_permission ? 'هەیە' : 'نییە') . "</h3>";

// تاقیکردنەوەی پێرمیشنێکی تر
$has_add_sale = hasPermission('add_sale');
echo "<h3>پێرمیشنی add_sale: " . ($has_add_sale ? 'هەیە' : 'نییە') . "</h3>";

// تاقیکردنەوەی پێرمیشنێکی نەموجود
$has_fake_permission = hasPermission('fake_permission');
echo "<h3>پێرمیشنی fake_permission: " . ($has_fake_permission ? 'هەیە' : 'نییە') . "</h3>";

// تاقیکردنەوەی کۆدی HTML
echo "<h3>کۆدی HTML:</h3>";
if (hasPermission('show_add_sale_button')) {
    echo '<a href="add_sale.php" class="btn btn-success" style="font-weight: bold;">
        <i class="fas fa-plus me-1"></i>زیادکردنی فرۆشتن
    </a>';
} else {
    echo '<p style="color: red;">دوگمەی زیادکردنی فرۆشتن نەدەردەکەوێت</p>';
}

// تاقیکردنەوەی داتابەیس
echo "<h3>تاقیکردنەوەی داتابەیس:</h3>";
try {
    require_once 'config/db_conected.php';
    
    // بینینی پێرمیشنەکە لە داتابەیس
    $stmt = $pdo->prepare("SELECT * FROM permissions WHERE name = 'show_add_sale_button'");
    $stmt->execute();
    $permission = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>پێرمیشن لە داتابەیس:</p>";
    echo "<pre>";
    var_dump($permission);
    echo "</pre>";
    
    // بینینی پێرمیشنەکانی ڕۆڵی بەکارهێنەر
    if (isset($_SESSION['user_role'])) {
        $stmt = $pdo->prepare("
            SELECT p.name, p.description 
            FROM permissions p 
            JOIN role_permissions rp ON p.id = rp.permission_id 
            WHERE rp.role = ? AND p.name = 'show_add_sale_button'
        ");
        $stmt->execute([$_SESSION['user_role']]);
        $role_permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>پێرمیشنەکانی ڕۆڵی " . $_SESSION['user_role'] . ":</p>";
        echo "<pre>";
        var_dump($role_permissions);
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>هەڵە لە پەیوەندی داتابەیس: " . $e->getMessage() . "</p>";
}
?> 