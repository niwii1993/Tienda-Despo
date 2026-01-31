<?php
require_once 'config/apisigma.php';

echo "<h2>Consultando API Sigma (ExportStock)...</h2>";

// Try 1: No params (All products, all deposits?)
echo "<h3>Intento 1: Sin parámetros</h3>";
$result = callSigmaApi('ExportStock');

if ($result['http_code'] == 200) {
    $items = $result['response'];
    if (is_array($items)) {
        echo "Items retornados: " . count($items) . "<br>";
        echo "<pre>";
        // Show first 3 items
        print_r(array_slice($items, 0, 3));
        echo "</pre>";
    } else {
        echo "Respuesta vacía o no es array.<br>";
        var_dump($items);
    }
} else {
    echo "Error: " . $result['http_code'] . "<br>";
    if ($result['http_code'] == 429) {
        print_r($result['headers']);
    }
}

// Try 2: With depo=1 (Common)
echo "<h3>Intento 2: ?depo=1</h3>";
$result2 = callSigmaApi('ExportStock', ['depo' => '1']);
if ($result2['http_code'] == 200) {
    $items = $result2['response'];
    echo "Items retornados (Depo 1): " . (is_array($items) ? count($items) : 0) . "<br>";
} else {
    echo "Error Intento 2: " . $result2['http_code'] . "<br>";
}

?>