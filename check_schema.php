<?php
require_once 'config/db.php';
$r = $conn->query("SHOW COLUMNS FROM products");
while ($row = $r->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>