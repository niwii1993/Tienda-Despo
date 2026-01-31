<?php
// fix_schema_stock.php
require_once 'config/db.php';

// Check if column exists
$check = $conn->query("SHOW COLUMNS FROM products LIKE 'stock_reservado'");
if ($check->num_rows == 0) {
    $sql = "ALTER TABLE products ADD COLUMN stock_reservado INT DEFAULT 0 AFTER stock";
    if ($conn->query($sql) === TRUE) {
        echo "Column 'stock_reservado' added successfully.<br>";
    } else {
        echo "Error adding column: " . $conn->error . "<br>";
    }
} else {
    echo "Column 'stock_reservado' already exists.<br>";
}

// verify sigma_id index while we are at it
$checkIndex = $conn->query("SHOW INDEX FROM products WHERE Key_name = 'sigma_id'");
if ($checkIndex->num_rows == 0) {
    $conn->query("ALTER TABLE products ADD INDEX (sigma_id)");
    echo "Index added on sigma_id.<br>";
}
echo "Schema fix complete.";
?>