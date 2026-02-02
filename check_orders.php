<?php
require_once 'config/db.php';
$r = $conn->query("DESCRIBE orders");
if ($r) {
    echo "Orders table:\n";
    while ($row = $r->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} else {
    echo "Error describing orders: " . $conn->error;
}
?>