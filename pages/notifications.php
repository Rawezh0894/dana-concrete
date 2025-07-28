<?php
session_start();
require_once '../config/db_conected.php';
// require_once '../config/permissions.php';
// if (!isset($_SESSION['user_id'])) {
//     header('Location: ../index.php');
//     exit;
// }
// if (!in_array($_SESSION['role'], ['admin', 'manager'])) {
//     echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
//         .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
//         .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
//         .'</div>';
//     exit;
// }
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ئاگادارکردنەوەکان</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/select2_design.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.rtl.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container-fluid mt-4">
    <h3 class="mb-4">ئاگادارکردنەوەکان</h3>
    <div class="row mb-3">
        <div class="col-md-3 mb-2">
            <input type="text" id="notificationSearch" class="form-control" placeholder="گەڕان...">
        </div>
        <div class="col-md-2 mb-2">
            <select id="notificationTypeFilter" class="form-control">
                <option value="">-- جۆری کردار --</option>
                <option value="insert">زیادکردن</option>
                <option value="update">نوێکردنەوە</option>
                <option value="delete">سڕینەوە</option>
            </select>
        </div>
        <div class="col-md-2 mb-2">
            <select id="notificationSeenFilter" class="form-control">
                <option value="">-- هەڵەبژاردنی خوێندرا --</option>
                <option value="0">نەخوێندراو</option>
                <option value="1">خوێندرا</option>
            </select>
        </div>
        <div class="col-md-2 mb-2">
            <select id="notificationDateFilter" class="form-control">
                <option value="">-- هەموو ڕۆژەکان --</option>
                <option value="today">ئەمڕۆ</option>
                <option value="yesterday">دوێنێ</option>
            </select>
        </div>

        <div class="col-md-1 mb-2 d-flex align-items-center">
            <span id="notificationsTotal" class="text-secondary"></span>
        </div>
    </div>
    <button class="btn btn-danger mb-2" id="deleteSelectedNotifications" disabled>سڕینەوەی هەڵبژێردراو</button>
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="notificationsTable">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAllNotifications"></th>
                    <th>جۆر</th>
                    <th>خشتە</th>
                    <th>وردەکاری</th>
                    <th>بەکارهێنەر</th>
                    <th>کات</th>
                    <th>بار</th>
                    <th>کردار</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/comon/table-controler.js"></script>
<script src="../assets/js/notifications/select_notifications.js"></script>

</body>
</html> 