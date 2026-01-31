<?php
// ajax/eliminar_item.php
session_start();

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    if (isset($_SESSION['carrito'][$id])) {
        unset($_SESSION['carrito'][$id]);
    }
}

// Redirect back to cart
header("Location: ../carrito.php");
exit();
?>