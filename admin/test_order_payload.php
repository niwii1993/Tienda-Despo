<?php
// admin/test_order_payload.php
require_once __DIR__ . '/../config/apisigma.php';

// Base payload that failed
$payload = [
    "id" => 4,
    "deviceId" => "WEB",
    "fecha" => date('Y-m-d'),
    "clienteId" => "772267",
    "vendedor" => "002",
    "items" => [
        [
            "articuloId" => "1013229",
            "cantidad" => 1,
            "precioUnitario" => 3275.26,
            "esPrecioFinal" => false,
            "descuento" => 0
        ]
    ],
    "hora" => date('H:i:s')
];

$variations = [
    // Hypothesis: Root is Array. Testing inside structure.
    'Minimal camelCase' => [
        [
            "clienteId" => "772267",
            "items" => []
        ]
    ],
    'Minimal PascalCase' => [
        [
            "ClienteId" => "772267",
            "Items" => []
        ]
    ],
    'Minimal Lowercase' => [
        [
            "cliente" => "772267",
            "items" => []
        ]
    ],
    'Sigma Style' => [
        [
            "id" => "9999",
            "clienteId" => "772267",
            "vendedorId" => "002" // trying vendedorId instead of vendedor
        ]
    ],
    'Array with Empty Object' => [(object) []],
    'Array with Integer ID' => [["id" => 123]],
    'Array with String ID' => [["id" => "123"]],
    'Root Pedido Wrapper' => ["pedido" => $payload],
    'Legacy Fields' => [
        "IdCliente" => "772267",
        "Items" => [
            ["IdArticulo" => "1013229", "Cantidad" => 1]
        ]
    ],
    'Original' => $payload,
    'No ID' => array_diff_key($payload, ['id' => '']),
    'No Device/Hora' => array_diff_key($payload, ['deviceId' => '', 'hora' => '']),
    'Fecha d/m/Y' => array_merge($payload, ['fecha' => date('d/m/Y')]),
    'Items as Detalles' => array_merge(array_diff_key($payload, ['items' => '']), ['detalles' => $payload['items']]),
    'ArticuloId as Codigo' => function ($p) {
        $p['items'][0]['codigo'] = $p['items'][0]['articuloId'];
        unset($p['items'][0]['articuloId']);
        return $p;
    },
    'Minimal' => [
        "clienteId" => "772267",
        "items" => [
            [
                "articuloId" => "1013229",
                "cantidad" => 1
            ]
        ]
    ]
];

$log = "TEST RESULT " . date('H:i:s') . "\n";
foreach ($variations as $name => $data) {
    if (is_callable($data))
        $data = $data($payload);

    $result = callSigmaApi('ImportPedidos', [], 'POST', $data);

    $log .= "VARIATION: $name\n";
    $log .= "HTTP: " . $result['http_code'] . "\n";
    if ($result['http_code'] != 200 && $result['http_code'] != 201) {
        $log .= "ERROR: " . substr($result['raw_response'], 0, 200) . "\n";
    } else {
        $log .= "SUCCESS!\n";
    }
    $log .= "PAYLOAD: " . json_encode($data) . "\n";
    $log .= str_repeat("-", 30) . "\n";
}
file_put_contents('debug_orders.txt', $log);
echo "Check debug_orders.txt";
?>