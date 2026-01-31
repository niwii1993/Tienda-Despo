<?php
// admin/test_structure_isolation.php
require_once __DIR__ . '/../config/apisigma.php';

// Valid keys from manual, but empty items
$payload = [
    "id" => rand(100000, 999999),
    "deviceId" => "API1",
    "fecha" => date('Y-m-d'),
    "clienteId" => "772267",
    "vendedor" => "002",
    "hora" => date('H:i:s'),
    "items" => [] // EMPTY ARRAY
];

echo "<h3>Testing Structure Isolation (Empty Items)</h3>";
echo "Payload: <pre>" . json_encode($payload, JSON_PRETTY_PRINT) . "</pre>";

$result = callSigmaApi('ApiImportPedidos', [], 'POST', $payload);
echo "HTTP: " . $result['http_code'] . "<br>";
echo "Raw Response: " . htmlspecialchars($result['raw_response']) . "<br>";

echo "<hr><h3>Testing ImportPedidos (With explicit Content-Length header check if possible - implicit in curl)</h3>";
// Retry ImportPedidos just to be sure
$result = callSigmaApi('ImportPedidos', [], 'POST', $payload);
echo "HTTP: " . $result['http_code'] . "<br>";
echo "Raw Response: " . htmlspecialchars($result['raw_response']) . "<br>";
?>