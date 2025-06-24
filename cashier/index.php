<!-- dashboard.php -->
<?php
session_start();
require '../database/db.php'; 
$content = 'components/dashboard_content.php';
include '../cashier/layouts/dashboard.php'; // master
?>
