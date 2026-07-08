<?php
$conn = new mysqli("localhost", "root", "", "grant_portal");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// 1. Define reviewer information
$name = "Dr. Myo Min";
$department = "Computer Science";
$email = "myomin@university.edu.mm";
$raw_password = "reviewer123"; // The plain password

// 2. Hash the password securely (Never store plain text!)
$hashed_password = password_hash($raw_password, PASSWORD_BCRYPT);

// 3. Insert into your reviewers table
$stmt = $conn->prepare("INSERT INTO reviewers (name, department, email, password) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $name, $department, $email, $hashed_password);

if ($stmt->execute()) {
    echo "🎉 Reviewer account created successfully!<br>";
    echo "Email: " . $email . "<br>";
    echo "Password: " . $raw_password;
} else {
    echo "❌ Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>