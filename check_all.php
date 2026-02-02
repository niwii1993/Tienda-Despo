<?php
require_once 'config/db.php';

function desc($conn, $table)
{
    echo "\nTABLE: $table\n";
    $r = $conn->query("DESCRIBE $table");
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            echo $row['Field'] . "\n";
        }
    } else {
        echo "Error: " . $conn->error;
    }
}

desc($conn, 'orders');
desc($conn, 'users');
?>