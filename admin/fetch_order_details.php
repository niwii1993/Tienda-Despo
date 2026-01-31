<?php
// admin/fetch_order_details.php
require_once __DIR__ . '/../config/apisigma.php';

echo "<h3>Fetching ExportFacturas (with Items) using client 772464</h3>";

// Try ExportFacturas because it contains items (per valid manual response)
$res = callSigmaApi('ExportFacturas', ['clienteId' => '772464', 'desde' => '2020-01-01', 'hasta' => date('Y-m-d'), 'items' => 'S']);

echo "HTTP: " . $res['http_code'] . "<br>";
if ($res['http_code'] == 200) {
    $count = count($res['response']);
    echo "Count: $count<br>";
    if ($count > 0 && !empty($res['response'][0])) {
        echo "Keys of first Invoice:<br>";
        echo "<pre>" . print_r(array_keys($res['response'][0]), true) . "</pre>";
        
        if (!empty($res['response'][0]['items'])) {
            echo "Keys of first ITEM:<br>";
            echo "<pre>" . print_r(array_keys($res['response'][0]['items'][0]), true) . "</pre>";
            echo "First ITEM full:<br>";
            echo "<pre>" . print_r($res['response'][0]['items'][0], true) . "</pre>";
        } else {
             echo "No items in invoice.<br>";
             // Dump full invoice to see if items key is different
             echo "<pre>" . print_r($res['response'][0], true) . "</pre>";
        }
    } else {
        echo "Response empty or invalid structure.";
    }
} else {
    echo "Error: " . $res['http_code'] . " " . print_r($res['response'], true);
}
?>