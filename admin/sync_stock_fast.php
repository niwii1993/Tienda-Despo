<?php
// admin/sync_stock_fast.php
require_once '../config/db.php';
require_once '../config/apisigma.php';
require_once 'includes/auth_check.php';

$page_title = 'Sincronización Rápida de Stock';
$active = 'productos';

$message = '';

if (isset($_POST['sync'])) {
    // 1. Fetch Stock Only
    $result = callSigmaApi('ExportStock');

    if ($result['http_code'] == 200 && is_array($result['response'])) {
        $stockData = $result['response'];
        $count = 0;

        $sql = "UPDATE products SET stock = ?, stock_reservado = ? WHERE sigma_id = ?";
        $stmt = $conn->prepare($sql);

        $conn->begin_transaction();
        try {
            foreach ($stockData as $item) {
                // API keys based on sample: articuloId, stock, stockReservado
                $sid = $item['articuloId'];
                $stock = $item['stock']; // Available stock usually
                $reservado = $item['stockReservado'] ?? 0;

                // Optional: Use stockFisico if preferred, but stock usually means available.
                // stock = stockFisico - stockReservado? 
                // Let's trust 'stock' as the main availability field.

                $stmt->bind_param("iis", $stock, $reservado, $sid);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    $count++;
                }
            }
            $conn->commit();
            $message = "<div class='alert alert-success'>Stock actualizado correctamente. $count productos modificados.</div>";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "<div class='alert alert-danger'>Error en la base de datos: " . $e->getMessage() . "</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Error en API Sigma: " . $result['http_code'] . "</div>";
    }
}

include 'includes/header.php';
?>

<div class="admin-header">
    <h2>Sincronización Rápida de Stock</h2>
    <a href="productos.php" class="btn-secondary">Volver</a>
</div>

<div class="admin-content">
    <?php echo $message; ?>

    <div class="form-card" style="text-align: center; padding: 40px;">
        <i class="fa-solid fa-bolt" style="font-size: 50px; color: #f1c40f; margin-bottom: 20px;"></i>
        <h3>Actualización Ultra-Rápida</h3>
        <p style="color: #666; width: 60%; margin: 0 auto 30px;">
            Esta herramienta solo descarga las cantidades de stock actuales desde Sigma y actualiza la base de datos
            local.
            Es mucho más rápida que la sincronización completa de productos.<br>
            <strong>Úsela varias veces al día para mantener el inventario al día.</strong>
        </p>

        <form method="POST">
            <button type="submit" name="sync" class="btn-primary" style="font-size: 18px; padding: 15px 40px;">
                <i class="fa-solid fa-sync"></i> Actualizar Ahora
            </button>
        </form>

        <div style="margin-top: 20px; font-size: 12px; color: #999;">
            Endpoint: ExportStock
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>