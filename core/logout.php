<?php
session_start();
require_once '../config/db_conected.php';
$_SESSION = [];
session_unset();
session_destroy();
redirectToLogin();
exit;
