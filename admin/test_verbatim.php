<?php
// admin/test_verbatim.php
require_once __DIR__ . '/../config/apisigma.php';

// Verbatim from Manual (Line 11032)
$payload = [
    "id" => rand(100000, 999999), // Unique
    "deviceId" => "API1",
    "fecha" => date('Y-m-d'), // "2015-07-21"
    "vendedor" => "002",
    "clienteId" => "772267", // Mine
    "hora" => date('H:i:s'), // "15:52:14"
    "nombre" => null,
    "documentoTipo" => null,
    "documentoNumero" => null,
    "direccion" => null,
    "eMail" => null,
    "tipoPedido" => null,
    "datosAdicionales" => (object) [], // Empty object, not []
    "listaPrecios" => "0",
    "observacion" => null,
    "empresa" => null,
    "lote" => "helados",
    "AjustaComprobanteId" => null,
    "motivoNotaCredito" => "1",
    "fechaEntrega" => null,
    "condicionDeVenta" => null,
    "items" => [
        [
            "articuloId" => "2968", // From Example (might typically fail validation but check structure error)
            "cantidad" => 15,
            "precioUnitario" => 15.441,
            "esPrecioFinal" => false,
            "descuento" => 10,
            "lote" => null
        ]
    ]
];

echo "<h3>Testing Verbatim Payload</h3>";
echo "<pre>" . json_encode($payload, JSON_PRETTY_PRINT) . "</pre>";

$result = callSigmaApi('ImportPedidos', [], 'POST', $payload);
echo "HTTP: " . $result['http_code'] . "<br>";
echo "Raw Response: " . htmlspecialchars($result['raw_response']) . "<br>";

// If that fails, try replacing articuloId with mine
echo "<hr><h3>Testing Verbatim Payload + My Article</h3>";
$payload['items'][0]['articuloId'] = "1013229"; // Mine
$result = callSigmaApi('ImportPedidos', [], 'POST', $payload);
echo "HTTP: " . $result['http_code'] . "<br>";
echo "Raw Response: " . htmlspecialchars($result['raw_response']) . "<br>";
?>