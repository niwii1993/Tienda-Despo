<?php
// admin/ajax_sync_stock.php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/apisigma.php';

// Check auth silently (if not logged in, just die)
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$result = callSigmaApi('ExportStock');

if ($result['http_code'] == 200 && is_array($result['response'])) {
    $stockData = $result['response'];
    $count = 0;

    $sql = "UPDATE products SET stock = ?, stock_reservado = ? WHERE sigma_id = ?";
    $stmt = $conn->prepare($sql);

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
        echo json_encode(['status' => 'success', 'updated' => $count, 'timestamp' => date('H:i:s')]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'API Error: ' . $result['http_code']]);
}
?>