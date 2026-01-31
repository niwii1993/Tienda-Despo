<?php
require_once 'config/apisigma.php';

echo "<h2>Buscando 'MACUCAS'...</h2>";

$result = callSigmaApi('ExportArticulos');

if ($result['http_code'] == 200) {
    $items = $result['response'];
    foreach ($items as $item) {
        if (stripos($item['descripcion'] ?? '', 'MACUCAS') !== false) {
            print_r($item);
            echo "--------------------------------<br>";
        }
    }
}
?>