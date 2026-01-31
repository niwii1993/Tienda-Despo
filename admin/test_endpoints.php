<?php
// admin/test_endpoints.php
require_once __DIR__ . '/../config/apisigma.php';

$potential_endpoints = [
    'ExportComprobantes',
    'ExportCtaCte',
    'ExportSaldos',
    'ExportVendedores',
    'ExportRubros',
    'ExportMarcas',
    'ExportZonas',
    'ExportListasPrecios',
    'ExportStock'
];

$log = "TEST RESULT " . date('Y-m-d H:i:s') . "\n";
$log .= "=================================\n\n";

foreach ($potential_endpoints as $ep) {
    echo "Testing $ep...\n";
    $result = callSigmaApi($ep, ['limit' => 1]);

    $log .= "Endpoint: $ep\n";
    if ($result['http_code'] == 200) {
        $log .= "STATUS: FOUND (200)\n";
        $log .= "SAMPLE: " . print_r(array_slice($result['response'], 0, 1), true) . "\n";
    } else {
        $log .= "STATUS: FAILED (" . $result['http_code'] . ")\n";
    }
    $log .= str_repeat("-", 30) . "\n";
}

file_put_contents('debug_endpoints.txt', $log);
echo "Testing complete. Check debug_endpoints.txt\n";
?>