<?php
// admin/test_clone_order.php
require_once __DIR__ . '/../config/apisigma.php';

// Data from Order 256973 in debug_fetch_orders.txt
$payload = [
    // "id" => 4, // Leave empty?
    "fecha" => date('Y-m-d'),
    "empresa" => "0001",
    "surcursal" => "0001", // Preserve typo
    "puntoVenta" => 13,
    "tipoPedido" => "PD",
    "vendedor" => "012",
    "tipoIva" => "CF", // From export
    "clienteId" => "772464", // Using the ORIGINAL valid client 
    //"clienteNombre" => "DIAZ BENJAMIN MATIAS", // Should logic fill this?
    "items" => [
        [
            "articuloId" => "1013229", // Random product I know exists? Or hope it accepts valid struct
            "cantidad" => 1
        ]
    ]
];

echo "<h3>Test Clone Order 256973</h3>";
echo "Sending to ImportPedidos...<br>";

// Wrapped in array
$result = callSigmaApi('ImportPedidos', [], 'POST', [$payload]);

echo "HTTP Code: " . $result['http_code'] . "<br>";
echo "Response: <pre>" . print_r($result['response'], true) . "</pre>";
?>