<?php
// admin/test_client.php
require_once __DIR__ . '/../config/apisigma.php';

echo "<h3>Fetching Client 772267</h3>";

// Try ExportClientes with filter if possible, otherwise fetch all limit
$endpoints = ['ExportClientes', 'Clientes', 'ExportarClientes'];

foreach ($endpoints as $ep) {
    echo "Endpoint: <strong>$ep</strong>... ";
    // Some APIs allow id filter?
    $result = callSigmaApi($ep, ['limit' => 10, 'clienteId' => '772267']);

    if ($result['http_code'] == 200) {
        $found = false;
        $log = "CLIENT SEARCH RESULT:\n";
        foreach ($result['response'] as $client) {
            $id = $client['id'] ?? 'N/A';
            $code = $client['codigo'] ?? 'N/A';
            // Check if ID matches loosely
            if ((string) $id === '772267' || (string) $code === '772267') {
                $log .= "FOUND 772267:\n" . print_r($client, true) . "\n";
                $found = true;
            }
        }
        if (!$found) {
            $log .= "Client 772267 NOT found in batch.\nDumping first item for structure:\n";
            $log .= print_r($result['response'][0] ?? 'Empty', true);
        }
        file_put_contents('debug_client.txt', $log);
        echo "<span style='color:green'>SUCCESS. Check debug_client.txt</span>";
        break;
    } else {
        echo "<span style='color:red'>" . $result['http_code'] . "</span><br>";
    }
}
?>