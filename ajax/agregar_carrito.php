<?php
// ajax/agregar_carrito.php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $product_id = intval($_POST['id']);
    $cantidad = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 1;

    // Check availability
    $stmt = $conn->prepare("SELECT id, nombre, precio_venta, stock, imagen_url, es_oferta, descuento_porcentaje FROM products WHERE id = ? AND stock > 0");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();

        // 1. Calculate requested total quantity
        $current_qty_in_cart = isset($_SESSION['carrito'][$product_id]) ? $_SESSION['carrito'][$product_id]['cantidad'] : 0;
        $total_requested = $current_qty_in_cart + $cantidad;

        // 2. Validate against DB stock
        if ($total_requested > $product['stock']) {
            echo json_encode(['status' => 'error', 'message' => 'No hay suficiente stock disponible. (Stock: ' . $product['stock'] . ')']);
            exit();
        }

        // Calculate Price (Check Offer)
        $precio_original = $product['precio_venta'];
        $final_price = $precio_original;

        if ($product['es_oferta'] == 1 && $product['descuento_porcentaje'] > 0) {
            $final_price = $product['precio_venta'] * (1 - ($product['descuento_porcentaje'] / 100));
        }

        // Initialize cart if needed
        if (!isset($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }

        // Add or Update logic
        if (isset($_SESSION['carrito'][$product_id])) {
            $_SESSION['carrito'][$product_id]['cantidad'] += $cantidad;

            // Remove if quantity is zero or less
            if ($_SESSION['carrito'][$product_id]['cantidad'] <= 0) {
                unset($_SESSION['carrito'][$product_id]);
            } else {
                // Update prices data always to keep it fresh
                $_SESSION['carrito'][$product_id]['precio'] = $final_price;
                $_SESSION['carrito'][$product_id]['precio_original'] = $precio_original;
                $_SESSION['carrito'][$product_id]['es_oferta'] = $product['es_oferta'];
                $_SESSION['carrito'][$product_id]['descuento_porcentaje'] = $product['descuento_porcentaje'];
            }

        } else {
            $_SESSION['carrito'][$product_id] = [
                'id' => $product['id'],
                'nombre' => $product['nombre'],
                'precio' => $final_price, // This is the unit price to pay
                'precio_original' => $precio_original, // List price
                'es_oferta' => $product['es_oferta'],
                'descuento_porcentaje' => $product['descuento_porcentaje'],
                'img' => $product['imagen_url'],
                'cantidad' => $cantidad
            ];
        }

        // Return current cart count
        $count = 0;
        foreach ($_SESSION['carrito'] as $item) {
            $count += $item['cantidad'];
        }

        echo json_encode(['status' => 'success', 'count' => $count, 'message' => 'Producto agregado']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Producto sin stock o no encontrado']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Petición inválida']);
}
?>