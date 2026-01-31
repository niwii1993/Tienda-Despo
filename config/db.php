<?php
// config/db.php

$host = 'localhost';
$user = 'root';
$password = ''; // Default XAMPP password is empty
$dbname = 'arcor_db';

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión a la base de datos: " . $conn->connect_error);
}

// Set charset to utf8mb4 to handle accents and ñ correctly
$conn->set_charset("utf8mb4");

// Function to calculate final price based on cost and margin
function calcularPrecioVenta($costo, $margen)
{
    return $costo * (1 + ($margen / 100));
}

// Function to format currency
function formatPrecio($precio)
{
    return '$' . number_format($precio, 0, ',', '.'); // Format like $1.200 (Argentina style)
}
?>