<?php
// setup_sellers_table.php
require_once 'config/db.php';

// 1. Create Sellers Table
$sql = "CREATE TABLE IF NOT EXISTS sellers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sigma_id VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    sucursal VARCHAR(50),
    email VARCHAR(100),
    activo TINYINT(1) DEFAULT 1
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'sellers' created successfully.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// 2. Add seller_id to Users Table
$check = $conn->query("SHOW COLUMNS FROM users LIKE 'seller_id'");
if ($check->num_rows == 0) {
    // We store the sigma_id of the seller (string) or the local ID? 
    // Usually linking by local ID is cleaner, but linking by Sigma ID is easier for sync.
    // Let's link by Sigma ID (varchar) to match what comes from the API on the client side if any, 
    // OR we can just store the 'vendedor' code (e.g. '002') which is the sigma_id.

    $sql = "ALTER TABLE users ADD COLUMN seller_id VARCHAR(50) NULL AFTER sigma_id";
    if ($conn->query($sql) === TRUE) {
        echo "Column 'seller_id' added to 'users' table.<br>";
    } else {
        echo "Error adding column: " . $conn->error . "<br>";
    }
} else {
    echo "Column 'seller_id' already exists in 'users'.<br>";
}
echo "Setup Complete.";
?>