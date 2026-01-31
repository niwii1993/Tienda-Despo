<?php
// admin/fetch_orders.php
require_once __DIR__ . '/../config/apisigma.php';

echo "<h3>Fetching Orders (ExportPedidos)</h3>";

// Try different endpoints for details
$endpoints = [
    'ExportPedidoDetalle',
    'ExportPedidoDetalles',
    'ExportDetallePedidos',
    'ExportDetallePedido',
    'PedidoDetalle',
    'PedidoDetalles',
    'DetallePedido',
    'ImportPedidoDetalle' // Maybe we import details separately? hope not
];

foreach ($endpoints as $ep) {
    echo "Endpoint: <strong>$ep</strong>... ";
    $result = callSigmaApi($ep, ['limit' => 5]);

    if ($result['http_code'] == 200) {
        $output = "ENDPOINT FOUND: " . $ep . "\n\n";
        $output .= print_r($result['response'], true);
        file_put_contents('debug_fetch_orders.txt', $output);
        echo "<span style='color:green'>SUCCESS. Check debug_fetch_orders.txt</span>";
        break;
    } else {
        echo "<span style='color:red'>" . $result['http_code'] . "</span><br>";
    }
}
?>