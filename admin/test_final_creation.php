<?php
// admin/test_final_creation.php
require_once __DIR__ . '/../config/apisigma.php';

$endpoints_to_test = [
    'ImportCliente',
    'ImportarCliente',
    'AltaCliente',
    'CrearCliente',
    'SaveCliente',
    'GrabarCliente',
    'Cliente',    // RESTful style
    'Clientes',   // RESTful style
    'ABMCliente',
    'PutCliente',
    'PostCliente'
];

$payload = [
    "id" => "99999", // Fake ID
    "nombre" => "Test User",
    "calle" => "Calle Falsa",
    "numero" => "123",
    "localidad" => "Ciudad",
    "cuit" => "20123456789",
    "email" => "test@test.com"
];

echo "<h3>Final Endpoint Test</h3>";
foreach ($endpoints_to_test as $ep) {
    // Try POST
    $result = callSigmaApi($ep, [], 'POST', $payload);

    echo "POST <strong>$ep</strong>: ";
    if ($result['http_code'] == 200 || $result['http_code'] == 201) {
        echo "<span style='color:green'>SUCCESS (" . $result['http_code'] . ")</span>";
        print_r($result['response']);
    } elseif ($result['http_code'] != 404) {
        echo "<span style='color:orange'>CODE " . $result['http_code'] . "</span>";
    } else {
        echo "<span style='color:red'>404 Not Found</span>";
    }
    echo "<br>";
    flush();
}
?>