r<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

$stmt = $conn->prepare("SELECT id, filename FROM receipts WHERE student_id = ? AND (downloaded = 0 OR downloaded IS NULL) ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$receipt = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$receipt) {
    header("Location: dashboard.php");
    exit();
}

$file_path = '../uploads/receipts/' . $receipt['filename'];

if (!file_exists($file_path)) {
    header("Location: dashboard.php");
    exit();
}

$update = $conn->prepare("UPDATE receipts SET downloaded = 1 WHERE id = ?");
$update->bind_param("i", $receipt['id']);
$update->execute();
$update->close();
$conn->close();

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($receipt['filename']) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file_path));
readfile($file_path);
exit();
