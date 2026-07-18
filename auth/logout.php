<?php
session_start();
if (isset($_SESSION['student_id'])) {
    require_once '../config/db.php';
    $student_id = $_SESSION['student_id'];
    $stmt = $conn->prepare("DELETE FROM contact_locks WHERE student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}
session_unset();
session_destroy();
header("Location: ../user/index.php");
exit;
?>