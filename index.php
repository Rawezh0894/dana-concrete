<?php
session_start();
require_once 'config/db_conected.php';
?>
<!DOCTYPE html>
<html lang="ku">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>چوونەژوورەوە - سیستەمی کۆنکرێت</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/login.css" rel="stylesheet">
    <!-- Font Awesome for eye icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body class="login-page" dir="rtl">
    <div class="container">
        <div class="login-container mt-5">
            <div class="text-center mb-3">
                <img src="assets/images/logo.png" alt="Dana Concrete Logo" style="width: 140px;">
            </div>
            
            <form method="post" action="process/login/sign_in.php">
                <?php if (!empty($_SESSION['login_error'])): ?>
                    <div class="alert alert-danger text-center" role="alert">
                        <?= htmlspecialchars($_SESSION['login_error']) ?>
                    </div>
                    <?php unset($_SESSION['login_error']); ?>
                <?php endif; ?>
                <div class="mb-3">
                    <label for="username" class="form-label">ناوی بەکارهێنەر</label>
                    <input type="text" class="form-control" id="username" name="username" required autofocus>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">وشەی نهێنی</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" required>
                        <span class="input-group-text" id="togglePassword" style="cursor:pointer;">
                            <i class="fa fa-eye" id="eyeIcon"></i>
                        </span>
                    </div>
                </div>
                <button type="submit" class="btn btn-login w-100">چوونەژوورەوە</button>
            </form>
        </div>
    </div>
    <script src="assets/js/login/show_hide_pass.js"></script>
</body>
</html>
