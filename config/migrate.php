<?php
require_once 'db.php';

// Add reviewer_id column to notifications table if it doesn't exist
$result = $conn->query("SHOW COLUMNS FROM notifications LIKE 'reviewer_id'");
if ($result->num_rows === 0) {
    $conn->query("ALTER TABLE notifications ADD COLUMN reviewer_id INT DEFAULT NULL, ADD INDEX (reviewer_id)");
    echo "Added reviewer_id column to notifications table.\n";
} else {
    echo "reviewer_id column already exists.\n";
}

// Add admin_id column to notifications table if it doesn't exist
$result = $conn->query("SHOW COLUMNS FROM notifications LIKE 'admin_id'");
if ($result->num_rows === 0) {
    $conn->query("ALTER TABLE notifications ADD COLUMN admin_id INT DEFAULT NULL, ADD INDEX (admin_id)");
    echo "Added admin_id column to notifications table.\n";
} else {
    echo "admin_id column already exists.\n";
}

echo "Migration complete.\n";
$conn->close();
