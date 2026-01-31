<?php
require_once 'config/apisigma.php';

echo "<h2>Consultando Lista de Precios 5...</h2>"; // Fixed encoding

$result = callSigmaApi('ExportArticulosPrecios', ['lista' => '5']);

if ($result['http_code'] == 200) {
    $items = $result['response'];
    echo "Total items in list: " . count($items) . "<br><br>";

    // Show first 5 items
    echo "<h3>Primeros 5 items:</h3><pre>";
    print_r(array_slice($items, 0, 5));
    echo "</pre>";

    // Search for the specific price mentioned by user to match ID
    $searchPrice = 296.0044;
    $found = false;
    echo "<h3>Buscando precio cercano a $searchPrice...</h3>";
    foreach ($items as $item) {
        $p = $item['precio'] ?? ($item['precioVenta'] ?? 0);
        if (abs($p - $searchPrice) < 1.0) { // Tolerance
            echo "<pre>Found Match (Expected): ";
            print_r($item);
            echo "</pre>";
            $found = true;
        }

        // Also check if valid price matches the 'wrong' price
        if (abs($p - 435.13) < 1.0) {
            echo "<pre>Found Match (Wrong One): ";
            print_r($item);
            echo "</pre>";
            $found = true;
        }
    }

    if (!$found)
        echo "No exact match found for $searchPrice or 435.13 in 'precio' field.";

} else {
    echo "Error: " . $result['http_code'];
    print_r($result);
}
?>