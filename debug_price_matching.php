<?php
require_once 'config/apisigma.php';

echo "<h2>Depurando Lógica de Precios para MACUCAS</h2>";

// 1. Fetch Product Data
echo "<h3>1. Buscando Producto en ExportArticulos...</h3>";
$rawProducts = callSigmaApi('ExportArticulos')['response'];
$targetProduct = null;

foreach ($rawProducts as $p) {
    if (stripos($p['descripcion'] ?? '', 'MACUCAS') !== false) {
        $targetProduct = $p;
        echo "<pre>Producto Encontrado:\n";
        echo "ID: " . $p['id'] . "\n";
        echo "EAN: " . ($p['eanUnidad'] ?? 'VACIO') . "\n";
        echo "Descripcion: " . $p['descripcion'] . "\n";
        echo "</pre>";
        break; // Stop at first
    }
}

if (!$targetProduct) {
    die("No se encontró MACUCAS en Artículos.");
}

$pid = (string) $targetProduct['id'];
$ean = (string) ($targetProduct['eanUnidad'] ?? '');

// 2. Fetch Price List
echo "<h3>2. Buscando Precios en Lista 5...</h3>";
$rawPrices = callSigmaApi('ExportArticulosPrecios', ['lista' => '5'])['response'];

$priceById = 0;
$priceByEan = 0;

foreach ($rawPrices as $pr) {
    $priceId = (string) ($pr['articuloId'] ?? ($pr['id'] ?? ''));
    $priceVal = $pr['precio'] ?? ($pr['precioVenta'] ?? ($pr['precioLista1'] ?? 0));

    // Check ID match
    if ($priceId === $pid) {
        echo "<pre>MATCH por ID ($priceId): $$priceVal</pre>";
        $priceById = $priceVal;
    }

    // Check EAN match
    if (!empty($ean) && $priceId === $ean) {
        echo "<pre>MATCH por EAN ($priceId): $$priceVal</pre>";
        $priceByEan = $priceVal;
    }
}

// 3. Simulate Logic
echo "<h3>3. Simulación de Lógica Actual:</h3>";
$finalPrice = 0;
$source = '';

// Step 1: ID
if ($priceById > 0) {
    $finalPrice = $priceById;
    $source = 'ID';
}

// Step 2: EAN Override
if ($priceByEan > 0) {
    $finalPrice = $priceByEan;
    $source = 'EAN (Override)';
}

echo "Precio Final Calculado: $$finalPrice (Fuente: $source)<br>";

if ($finalPrice > 300) {
    echo "<strong style='color:red'>ALERTA: El precio sigue siendo alto (>300). La lógica de EAN no funcionó o el EAN no está en la lista de precios.</strong>";
} else {
    echo "<strong style='color:green'>EXITO: El precio es el esperado (~296).</strong>";
}
?>