<?php
// admin/probe_import_endpoints.php
require_once __DIR__ . '/../config/apisigma.php';

$payload = [
    "fecha" => date('Y-m-d'),
    "clienteId" => "772464", // Known valid client
    "empresa" => "0001",
    "surcursal" => "0001",
    "puntoVenta" => 13,
    "tipoPedido" => "PD",
    "vendedor" => "012",
    "items" => [["articuloId" => "1013229", "cantidad" => 1]]
];

$endpoints = [
    'ImportPedido',
    'ImportarPedido',
    'ImportarPedidos',
    'Pedido/Importar',
    'Pedidos/Importar',
    'GrabarPedido',
    'AltaPedido'
];

echo "<h3>Probing Import Endpoints</h3>";

foreach ($endpoints as $ep) {
    // Try Object Payload
    $resObj = callSigmaApi($ep, [], 'POST', $payload);
    echo "<strong>$ep (Object)</strong>: " . $resObj['http_code'] . "<br>";

    // Try Array Payload
    $resArr = callSigmaApi($ep, [], 'POST', [$payload]);
    echo "<strong>$ep (Array)</strong>: " . $resArr['http_code'] . "<br>";

    if ($resObj['http_code'] == 200 || $resObj['http_code'] == 201) {
        echo "SUCCESS OBJECT!<br>";
        break;
    }
    if ($resArr['http_code'] == 200 || $resArr['http_code'] == 201) {
        echo "SUCCESS ARRAY!<br>";
        break;
    }
}
?>