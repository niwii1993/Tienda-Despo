<?php
require_once 'config/apisigma.php';

echo "Testing connection to ApiSigma (" . SIGMA_ENV . ")...\n";
echo "URL: " . getSigmaUrl('ExportArticulos') . "\n";

$full_cred = SIGMA_FULL_CREDENTIAL;
$parts = explode(':', $full_cred);
$user = $parts[0];
$hash = (count($parts) > 1) ? trim($parts[1]) : '';

$result = callSigmaApi('ExportArticulos?id=78694'); // Use the ID found in previous test to get a specific one, or just list

echo "HTTP Code: " . $result['http_code'] . "\n";

if ($result['http_code'] == 200) {
    $data = $result['response'];
    if (is_array($data) && count($data) > 0) {
        echo "Product Schema Sample:\n";
        print_r($data[0]);
    } else {
        echo "No data found.\n";
    }
} else {
    echo "Failed. Error: " . $result['error'] . "\n";
    echo "Response: " . substr($result['raw_response'], 0, 500) . "\n";
}
?>