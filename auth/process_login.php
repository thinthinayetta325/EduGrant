<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $conn = new mysqli("localhost", "root", "", "grant_portal");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $user = null;
    $role = null;

    // Try student table first
    $stmt = $conn->prepare("SELECT id, name, password FROM student WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $role = 'student';
        $stmt->close();
    }

    // Try reviewers table if not found
    if (!$user) {
        $stmt = $conn->prepare("SELECT id, name, password FROM reviewers WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $role = 'reviewer';
            $stmt->close();
        }
    }

    // Try admin table if not found
    if (!$user) {
        $stmt = $conn->prepare("SELECT id, name, password FROM admin WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $role = 'admin';
            $stmt->close();
        }
    }

    if ($user) {
        if (password_verify($password, $user['password']) || $password === $user['password']) {
            if ($role === 'reviewer') {
                $_SESSION['reviewer_id'] = $user['id'];
                $_SESSION['reviewer_name'] = $user['name'];
                header("Location: ../reviewer/dashboard.php");
            } elseif ($role === 'admin') {
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_name'] = $user['name'];
                header("Location: ../admin/dashboard.php");
            } else {
                $_SESSION['student_id'] = $user['id'];
                $_SESSION['fullname'] = $user['name'];
                if (!empty($_GET['redirect'])) {
                    header("Location: " . urldecode($_GET['redirect']));
                } else {
                    header("Location: ../user/home.php");
                }
            }
            exit();
        } else {
            header("Location: login.php?error=" . urlencode("Incorrect password entry."));
            exit();
        }
    } else {
        header("Location: login.php?error=" . urlencode("No account found with that email."));
        exit();
    }
}