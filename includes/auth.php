<?php
function need_student() {
if (!isset($_SESSION['student_id'])) {
header('Location: ../index.php');
exit;
}
}
function need_manager() {
if (!isset($_SESSION['manager_id'])) {
header('Location: login.php');
exit;
}
}
function need_admin() {
if (!isset($_SESSION['admin_id'])) {
header('Location: login.php');
exit;
}
}
