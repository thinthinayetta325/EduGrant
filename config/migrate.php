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

// Create contact_messages table if it doesn't exist
$result = $conn->query("SHOW TABLES LIKE 'contact_messages'");
if ($result->num_rows === 0) {
    $conn->query("CREATE TABLE IF NOT EXISTS contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT DEFAULT NULL,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        subject VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES student(id) ON DELETE SET NULL
    )");
    echo "Created contact_messages table.\n";
} else {
    echo "contact_messages table already exists.\n";
}

echo "Migration complete.\n";
$conn->close();
