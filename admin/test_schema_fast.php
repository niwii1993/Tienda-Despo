<?php
// admin/test_schema_fast.php
require_once __DIR__ . '/../config/apisigma.php';

$outputFile = 'debug_schema_fast.txt';
file_put_contents($outputFile, "STARTING FAST TEST " . date('H:i:s') . "\n");

function logResult($msg)
{
    global $outputFile;
    file_put_contents($outputFile, $msg . "\n", FILE_APPEND);
}

// Correct headers for Client 772267
$base = [
    // "id" => 4, // REMOVED ID just in case
    "fecha" => date('Y-m-d'),
    "clienteId" => "772267",
    "vendedor" => "002",
    "empresa" => "0001",
    "surcursal" => "0001",
    "puntoVenta" => 13,
    "tipoPedido" => "PD",
    "hora" => date('H:i:s')
];

$item_base = [
    "cantidad" => 1,
    //"precioUnitario" => 3275.26, // Try sending without price? usually allowed
    //"esPrecioFinal" => false,
    //"descuento" => 0
];

// Rich Payload based on Client 772267 Data
$variations["Rich | Key:items | Id:articuloId"] = function () use ($base, $item_base) {
    $p = $base;
    $p["vendedor"] = "229"; // From client dump
    $p["listaDePrecio"] = "3"; // From client dump
    $p["condicionVenta"] = "CE"; // From client dump

    // Use standard items
    $i = $item_base;
    $i["articuloId"] = "1013229";
    $p["items"] = [$i];

    return [$p];
};

$variations["Rich | Key:articulos | Id:articuloId"] = function () use ($base, $item_base) {
    $p = $base;
    $p["vendedor"] = "229";
    $p["listaDePrecio"] = "3";
    $p["condicionVenta"] = "CE";

    $i = $item_base;
    $i["articuloId"] = "1013229";
    $p["articulos"] = [$i]; // Try articulos key

    return [$p];
};

// Header Only Test (No items)
$variations["Header Only (No Items)"] = function () use ($base) {
    return [$base];
};

// Header Only with empty items array
$variations["Header with empty items"] = function () use ($base) {
    $p = $base;
    $p['items'] = [];
    return [$p];
};

$keys = ['items', 'articulos'];
$id_fields = ['articuloId', 'id', 'codigo', 'IdArticulo', 'sku'];
$val = '1013229';

$sucursal_spellings = ['surcursal', 'sucursal', 'Sucursal'];

foreach ($sucursal_spellings as $suc) {
    // Update base with spelling variation
    $baseVar = $base;
    unset($baseVar['surcursal']); // remove default
    $baseVar[$suc] = "0001";

    foreach ($keys as $k) {
        foreach ($id_fields as $idK) {
            $variations["Suc:$suc | Key:$k | Id:$idK"] = function () use ($baseVar, $item_base, $k, $idK, $val) {
                $p = $baseVar;
                $i = $item_base;
                $i[$idK] = $val;
                $p[$k] = [$i];
                return [$p];
            };
        }
    }
}

// Add simple array of arrays (no keys for items?) - unlikely but possible
$variations["Key:items | FlatArray"] = function () use ($base, $val) {
    $p = $base;
    $p['items'] = [[$val, 1]]; // code, qty
    return [$p];
};

foreach ($variations as $name => $gen) {
    $payload = $gen();
    $res = callSigmaApi('ImportPedidos', [], 'POST', $payload);

    $status = $res['http_code'];
    logResult("$name => $status");

    if ($status == 200 || $status == 201) {
        logResult("SUCCESS FOUND! Response: " . print_r($res['response'], true));
        break;
    }
}
logResult("DONE");
?>