<?php
require_once 'config/db.php';

// Check if column exists
$check = $conn->query("SHOW COLUMNS FROM users LIKE 'sigma_id'");
if ($check->num_rows == 0) {
    echo "Adding sigma_id column...\n";
    $sql = "ALTER TABLE users ADD COLUMN sigma_id VARCHAR(50) NULL AFTER id, ADD INDEX (sigma_id)";
    if ($conn->query($sql)) {
        echo "Column 'sigma_id' added successfully.\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
} else {
    echo "Column 'sigma_id' already exists.\n";
}
?>