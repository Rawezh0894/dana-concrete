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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.rtl.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .notification-details {
            max-height: 200px;
            overflow-y: auto;
        }
        .old-values {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 10px;
            border-radius: 5px;
            margin: 5px 0;
        }
        .new-values {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            padding: 10px;
            border-radius: 5px;
            margin: 5px 0;
        }
        .additional-info {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            border-radius: 5px;
            margin: 5px 0;
        }
        .json-key {
            font-weight: bold;
            color: #495057;
        }
        .json-value {
            color: #6c757d;
        }
        .detail-row {
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: flex-start;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .json-key {
            font-weight: bold;
            color: #495057;
            min-width: 200px;
            display: inline-block;
            margin-right: 10px;
        }
        .json-value {
            color: #6c757d;
            flex: 1;
        }
        .nested-object {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px;
            margin-top: 5px;
        }
        .nested-item {
            padding: 4px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .nested-item:last-child {
            border-bottom: none;
        }
        .nested-key {
            font-weight: 600;
            color: #495057;
            margin-right: 8px;
        }
        .nested-value {
            color: #6c757d;
        }
        .text-muted {
            color: #6c757d !important;
            font-style: italic;
        }
    </style>
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

<!-- Notification Details Modal -->
<div class="modal fade" id="notificationDetailsModal" tabindex="-1" aria-labelledby="notificationDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notificationDetailsModalLabel">وردەکاری چالاکی</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>زانیاری گشتی</h6>
                        <table class="table table-sm">
                            <tr><td><strong>جۆری کردار:</strong></td><td id="modal-action"></td></tr>
                            <tr><td><strong>خشتە:</strong></td><td id="modal-table"></td></tr>
                            <tr><td><strong>ناسنامە:</strong></td><td id="modal-record-id"></td></tr>
                            <tr><td><strong>بەکارهێنەر:</strong></td><td id="modal-username"></td></tr>
                            <tr><td><strong>کات:</strong></td><td id="modal-created-at"></td></tr>
                            <tr><td><strong>IP:</strong></td><td id="modal-ip"></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>ڕوونکردنەوە</h6>
                        <p id="modal-description"></p>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <h6>بەهای کۆن</h6>
                        <div id="modal-old-values" class="old-values"></div>
                    </div>
                    <div class="col-md-6">
                        <h6>بەهای نوێ</h6>
                        <div id="modal-new-values" class="new-values"></div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <h6>زانیاری زیاتر</h6>
                        <div id="modal-additional-info" class="additional-info"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/swalAlert.js"></script>
<script src="../assets/js/comon/table-controler.js"></script>
<script src="../assets/js/notifications/select_notifications.js"></script>

</body>
</html> 