<?php
session_start();
require_once '../../config/db_conected.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header('Location: ' . $project_root . '/pages/dashboard.php');
            exit;
        } else {
            $_SESSION['login_error'] = 'ناوی بەکارهێنەر یان وشەی نهێنی هەڵەیە!';
        }
    } else {
        $_SESSION['login_error'] = 'تکایە هەموو خانەکان پڕبکەوە!';
    }
} else {
    $_SESSION['login_error'] = 'داواکاری نادروست!';
}
redirectToLogin();
exit;
