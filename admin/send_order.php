<?php
// ================= INCLUDES =================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/apisigma.php';

// ================= VALIDACION =================
$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($orderId <= 0) {
    die("ID de pedido inválido");
}

// ================= CABECERA PEDIDO =================
// Use MySQLi and correct table specific to this project (orders joined with users)
$sql = "SELECT o.id, o.created_at as fecha, u.sigma_id as cliente_id, u.seller_id as vendedor
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$result = $stmt->get_result();
$pedido = $result->fetch_assoc();

if (!$pedido) {
    die("Pedido no encontrado");
}

if (empty($pedido['cliente_id'])) {
    die("El cliente no tiene ID de Sigma asignado.");
}

// ================= ITEMS =================
// Use order_items joined with products to get article ID (sigma_id)
$sqlItems = "SELECT oi.cantidad, oi.precio_unitario, p.sigma_id as articulo_id
             FROM order_items oi
             JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = ?";

$stmt = $conn->prepare($sqlItems);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$resultItems = $stmt->get_result();

$itemsDB = [];
while ($row = $resultItems->fetch_assoc()) {
    $itemsDB[] = $row;
}

if (empty($itemsDB)) {
    die("Pedido sin items");
}

// ================= ARMAR JSON SIGMA =================
// Prepare helpers for missing fields (using defaults based on previous context)
$fecha = !empty($pedido['fecha']) ? date("Y-m-d", strtotime($pedido['fecha'])) : date("Y-m-d");
$vendedor = !empty($pedido['vendedor']) ? str_pad($pedido['vendedor'], 3, "0", STR_PAD_LEFT) : "002"; // Fallback default
$listaPrecios = "3"; // Default from previous code
$condicionVenta = "CE"; // Default from previous code

$payload = [
    "id" => (int) $pedido['id'],
    "deviceId" => "API",
    "fecha" => $fecha,
    "vendedor" => $vendedor,
    "clienteId" => (string) $pedido['cliente_id'],
    "hora" => date("H:i:s"),
    "listaPrecios" => $listaPrecios,
    "condicionDeVenta" => $condicionVenta,
    "items" => []
];

foreach ($itemsDB as $i) {
    if (empty($i['articulo_id']))
        continue; // Skip if no sigma_id

    $payload['items'][] = [
        "articuloId" => (string) $i['articulo_id'],
        "cantidad" => (float) $i['cantidad'],
        "precioUnitario" => (float) $i['precio_unitario'],
        "esPrecioFinal" => false,
        "descuento" => 0 // Default 0 as not tracked in simple order items
    ];
}

// ================= ENVIAR A SIGMA =================
$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

$ch = curl_init(SIGMA_URL . "/ImportPedidos");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "X-Auth-Token: " . SIGMA_TOKEN
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// ================= OUTPUT DEBUG =================
echo "<pre>";
echo "Enviando Pedido #{$orderId} a Sigma\n";
echo "----------------------------------\n";
echo "HTTP Code: {$httpCode}\n\n";

if ($httpCode == 200) {
    // Update local status if success
    $conn->query("UPDATE orders SET estado = 'enviado' WHERE id = $orderId");
    echo "¡Pedido marcado como ENVIADO en base de datos local!\n\n";
}

if ($curlError) {
    echo "CURL ERROR:\n{$curlError}\n\n";
}

echo "PAYLOAD ENVIADO:\n";
echo $json . "\n\n";

echo "RESPUESTA SIGMA:\n";
echo $response . "\n";
echo "</pre>";

echo '<br><a href="pedidos.php" class="btn btn-secondary">Volver a Pedidos</a>';
