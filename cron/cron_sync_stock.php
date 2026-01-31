<?php
// cron/cron_sync_stock.php

// 1. Settings
// Adjust path to root if needed
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/apisigma.php';
require_once __DIR__ . '/../config/cron_secret.php';

// 2. Authentication
$is_cli = (php_sapi_name() === 'cli');
$provided_key = isset($_GET['key']) ? $_GET['key'] : '';

if (!$is_cli && $provided_key !== CRON_SECRET_KEY) {
    header('HTTP/1.0 403 Forbidden');
    die("Acceso Denegado. Clave incorrecta.");
}

// 3. Setup Logging
$logFile = __DIR__ . '/logs_stock.txt';
function cronLog($msg)
{
    global $logFile;
    $date = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$date] $msg" . PHP_EOL, FILE_APPEND);
    echo "[$date] $msg<br>\n";
}

cronLog("Inicio de sincronización de Stock...");

// 4. Execution
$result = callSigmaApi('ExportStock');

if ($result['http_code'] == 200 && is_array($result['response'])) {
    $stockData = $result['response'];
    $count = 0;

    // Prepare Statement
    $sql = "UPDATE products SET stock = ?, stock_reservado = ? WHERE sigma_id = ?";
    $stmt = $conn->prepare($sql);

    // Batch Transaction
    $conn->begin_transaction();
    try {
        foreach ($stockData as $item) {
            $sid = $item['articuloId'];
            $stock = $item['stock'];
            $reservado = $item['stockReservado'] ?? 0;

            $stmt->bind_param("iis", $stock, $reservado, $sid);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $count++;
            }
        }
        $conn->commit();
        cronLog("Exito: Stock actualizado. Registros modificados: $count");

    } catch (Exception $e) {
        $conn->rollback();
        cronLog("Error SQL: " . $e->getMessage());
    }
} else {
    cronLog("Error API Sigma: Codigo HTTP " . $result['http_code']);
}

cronLog("Fin del proceso.");
?>