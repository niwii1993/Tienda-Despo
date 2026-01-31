<?php
require_once 'config/apisigma.php';

echo "<h2>Consultando API Sigma...</h2>";

$result = callSigmaApi('ExportArticulos');

if ($result['http_code'] == 200) {
    $items = $result['response'];
    echo "Total items: " . count($items) . "<br><br>";

    foreach ($items as $item) {
        // Filter for the specific supplier to see a relevant example
        $supplierCode = $item['proveedorCodigo'] ?? ($item['proveedorId'] ?? '');

        if ((string) $supplierCode === '00992') {
            echo "<h3>Ejemplo de Producto (Proveedor 00992):</h3>";
            echo "<pre>";
            print_r($item);
            echo "</pre>";
            break; // Show only one
        }
    }
} else {
    echo "Error: " . $result['http_code'];
    print_r($result);
}
?>