<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$notif_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';

if ($notif_id > 0) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND student_id = ? AND is_read = 0");
    $stmt->bind_param("ii", $notif_id, $student_id);
    $stmt->execute();
    $stmt->close();
}

$conn->close();
header("Location: notifications.php?lang=" . urlencode($lang));
exit();
