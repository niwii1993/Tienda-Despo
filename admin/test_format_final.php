<?php
// admin/test_format_final.php
require_once __DIR__ . '/../config/apisigma.php';

function testPayload($name, $data)
{
    echo "<hr><h3>$name</h3>";
    echo "Payload: <pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
    $result = callSigmaApi('ImportPedidos', [], 'POST', $data);
    echo "HTTP: " . $result['http_code'] . "<br>";
    echo "Raw Response: " . htmlspecialchars($result['raw_response']) . "<br>";
}

$id = rand(100000, 999999);
$date = date('Y-m-d');
$time = date('H:i:s');

// Base Item
$item = [
    "articuloId" => "1013229", // String
    "cantidad" => 1,
    "precioUnitario" => 3275.26,
    "esPrecioFinal" => false,
    "descuento" => 0
];

// 1. Minimal per Manual Example (stripped nulls)
$payload1 = [
    "id" => $id,
    "deviceId" => "API1",
    "fecha" => $date,
    "vendedor" => "002",
    "clienteId" => "772267",
    "hora" => $time,
    "listaPrecios" => "3",
    "empresa" => "0001", // Required? Example says null, but line 4608 says 0001.
    "items" => [$item]
];

testPayload("Test 1: Minimal (Manual Keys)", $payload1);

// 2. Add 'sucursal' (Correct Spelling)
$payload2 = $payload1;
$payload2['sucursal'] = "0001";
testPayload("Test 2: +sucursal", $payload2);

// 3. Add 'puntoVenta' (Required by Export logic)
$payload3 = $payload2;
$payload3['puntoVenta'] = 13;
testPayload("Test 3: +puntoVenta=13", $payload3);

// 4. Try 'surcursal' (Typo)
$payload4 = $payload1;
$payload4['surcursal'] = "0001";
testPayload("Test 4: +surcursal (Typo)", $payload4);

// 6. Valid Fields Wrapped in ARRAY (Old theory, correct keys)
$payload6 = [$payload1];
testPayload("Test 6: Array Wrapper [{...}]", $payload6);

// 7. Singular URL 'ImportPedido' (Singular Payload)
echo "<hr><h3>Test 7: Endpoint ImportPedido (Singular URL)</h3>";
$result = callSigmaApi('ImportPedido', [], 'POST', $payload1);
echo "HTTP: " . $result['http_code'] . "<br>";
echo "Raw Response: " . htmlspecialchars($result['raw_response']) . "<br>";

// 8. Singular URL 'ImportPedido' (Array Payload)
echo "<hr><h3>Test 8: Endpoint ImportPedido (Array Payload)</h3>";
$result = callSigmaApi('ImportPedido', [], 'POST', $payload6);
echo "HTTP: " . $result['http_code'] . "<br>";
echo "Raw Response: " . htmlspecialchars($result['raw_response']) . "<br>";
?>