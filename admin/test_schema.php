<?php
// admin/test_schema.php
require_once __DIR__ . '/../config/apisigma.php';

$base = [
    "id" => 4,
    "fecha" => date('Y-m-d'),
    "clienteId" => "772267",
    "vendedor" => "002",
    "empresa" => "0001", // Correct
    "surcursal" => "0001", // Correct
    "puntoVenta" => 13, // Correct
    "tipoPedido" => "PD",
    "hora" => date('H:i:s')
];

$item_base = [
    "cantidad" => 1,
    "precioUnitario" => 3275.26,
    "esPrecioFinal" => false,
    "descuento" => 0
];

$keys = ['items', 'Items', 'articulos', 'Articulos', 'detalles', 'Detalles', 'rows', 'Rows', 'detallesPedido', 'DetallesPedido', 'itemsPedido'];
$id_fields = ['articuloId' => '1013229', 'id' => '1013229', 'codigo' => '1013229', 'IdArticulo' => '1013229']; // Pascal for IdArticulo

$dates = [
    'Y-m-d' => date('Y-m-d'),
    'd/m/Y' => date('d/m/Y'),
    'Ymd' => date('Ymd'),
    'd-m-Y' => date('d-m-Y')
];

$log = "TEST SCHEMA EXTENDED " . date('H:i:s') . "\n";

foreach ($dates as $dFmt => $dVal) {
    $base['fecha'] = $dVal; // Update date in base
    foreach ($keys as $key) {
        foreach ($id_fields as $idKey => $idVal) {
            $item = array_merge($item_base, [$idKey => $idVal]);

            // Try wrapping items in array (Standard)
            $payload = $base;
            $payload[$key] = [$item];

            $result = callSigmaApi('ImportPedidos', [], 'POST', [$payload]);
            if ($result['http_code'] == 200 || $result['http_code'] == 201) {
                $log .= "SUCCESS!!! Date:$dFmt | Key:$key | Id:$idKey\n";
                file_put_contents('debug_schema.txt', $log); // Save immediately
                exit; // Found it
            }
        }
    }
    $log .= "Date $dFmt failed for all keys.\n";
}
echo "Finished extended test.";

// Special case: clienteId as int
$baseInt = $base;
$baseInt['clienteId'] = 772267;
foreach ($keys as $key) {
    $item = array_merge($item_base, ['articuloId' => '1013229']);
    $payload = $baseInt;
    $payload[$key] = [$item];

    $result = callSigmaApi('ImportPedidos', [], 'POST', [$payload]);
    $log .= "KEY: $key (Int Client) | HTTP: " . $result['http_code'] . "\n";
}


file_put_contents('debug_schema.txt', $log);
echo "Check debug_schema.txt";
?>