<?php
// admin/test_client_sigma.php
require_once __DIR__ . '/../config/apisigma.php';

// Dummy Client Data
$clientData = [
    [
        "id" => "WEB-TEST-" . time(), // Temporary ID sent from Web? Or maybe 0?
        "razonSocial" => "Test Web Client " . date('H:i'),
        "domicilio" => "Calle Falsa 123",
        "localidad" => "Ciudad Test",
        "provincia" => "Corrientes",
        "codigoPostal" => "3200",
        "telefono" => "123456789",
        "email" => "test_web_" . time() . "@example.com",
        "responsableInscripto" => false,
        "cuit" => "00000000000", // Generic CUIT/DNI
        "dni" => "12345678",
        "vendedor" => "002",
        "listaPrecios" => 1
    ]
];

echo "<h3>Testing ImportClientes...</h3>";
echo "<pre>";
print_r($clientData);
echo "</pre>";

$endpoints = ['ImportCliente', 'SaveClientes'];

foreach ($endpoints as $ep) {
    echo "<h3>Testing $ep...</h3>";
    $result = callSigmaApi($ep, [], 'POST', $clientData);
    file_put_contents("debug_test_$ep.txt", print_r($result, true));

    if ($result['http_code'] == 200) {
        echo "<b>SUCCESS found at $ep</b>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
        break;
    } else {
        echo "Failed $ep: " . $result['http_code'] . "<br>";
    }
}
?>