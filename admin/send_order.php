<?php
// admin/send_order.php
require_once '../config/db.php';
require_once '../config/apisigma.php';

// Check Admin Access
require_once 'includes/auth_check.php';

// Ensure an order ID is provided
if (!isset($_GET['id'])) {
    die("ID de pedido no especificado.");
}

$orderId = intval($_GET['id']);

// Fetch Order (MySQLi)
$sqlOrder = "SELECT o.*, u.sigma_id, u.seller_id FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = $orderId";
$resOrder = $conn->query($sqlOrder);
$order = $resOrder->fetch_assoc();

if (!$order) {
    die("Pedido no encontrado.");
}
if (empty($order['sigma_id'])) {
    die("Error: El cliente asociado no tiene un ID de Sigma configurado (sigma_id). Sincronice el cliente primero.");
}

// Fetch Items (MySQLi)
$sqlItems = "SELECT oi.*, p.sigma_id FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = $orderId";
$resItems = $conn->query($sqlItems);

$items = [];
while ($row = $resItems->fetch_assoc()) {
    $items[] = $row;
}

if (empty($items)) {
    die("El pedido no tiene items.");
}

// Build JSON Payload
$jsonItems = [];
foreach ($items as $item) {
    if (empty($item['sigma_id'])) {
        die("Error: El producto ID " . $item['product_id'] . " no está sincronizado con Sigma.");
    }

    $jsonItems[] = [
        "articuloId" => (string) $item['sigma_id'],
        "cantidad" => (float) $item['cantidad'],
        "precioUnitario" => (float) $item['precio_unitario'],
        "esPrecioFinal" => false,
        "descuento" => 0
    ];
}

$payload = [
    "id" => (int) $order['id'],
    "fecha" => date('Y-m-d'),
    "clienteId" => $order['sigma_id'],
    "vendedor" => (!empty($order['seller_id']) ? $order['seller_id'] : "002"),
    "empresa" => "0001", // Correct for 77xxxx clients
    "sucursal" => "0001", // Correct spelling per manual text
    "puntoVenta" => 13, // Correct for Company 0001
    "tipoPedido" => "PD", // Keep PD as per dump
    "listaPrecios" => "3", // Correct casing (listaPrecios)
    "condicionDeVenta" => "CE", // Correct casing (condicionDeVenta)
    "items" => $jsonItems,
    "hora" => date('H:i:s')
];

// Call API
echo "<h3>Enviando Pedido #$orderId a Sigma...</h3>";
// MANUAL (Line 11029) says Singular Object.
// We pass $payload directly. callSigmaApi handles json_encode.
$result = callSigmaApi('ImportPedidos', [], 'POST', $payload);

echo "<pre>";
if ($result['http_code'] == 200) {
    echo "¡Pedido enviado con éxito!\n";
    print_r($result['response']);

    // Update local status
    $conn->query("UPDATE orders SET estado = 'enviado' WHERE id = $orderId");
    echo "\nEstado local actualizado a 'Enviado'.";
} else {
    echo "Error al enviar pedido:\n";
    echo "HTTP Code: " . $result['http_code'] . "\n";
    echo "Error: " . ($result['error'] ?? '') . "\n";
    echo "Response: " . $result['raw_response'] . "\n";

    echo "\nPayload Sent:\n";
    echo json_encode($payload, JSON_PRETTY_PRINT);
}
echo "</pre>";

?>
<br>
<a href="pedidos.php" class="btn btn-secondary">Volver a Pedidos</a>