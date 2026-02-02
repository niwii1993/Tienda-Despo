<?php
// admin/sync_products.php
require_once '../config/db.php';
require_once '../config/apisigma.php';

// Check Admin Access
require_once 'includes/auth_check.php';

// INCREASE LIMITS for large sync
set_time_limit(0);
ini_set('memory_limit', '1024M');

$pageTitle = "Sincronizar con Sigma";
include 'includes/header.php';

// --- Helper Functions ---

function fetchApiData($endpoint, $params = [])
{
    global $message, $messageType;
    $result = callSigmaApi($endpoint, $params);

    // Simple 429 handling with sleep
    if ($result['http_code'] == 429) {
        $waitMs = $result['headers']['X-Retry-After-ms'] ?? 5000;
        $waitSec = ceil($waitMs / 1000) + 1;
        if ($waitSec < 30) {
            sleep($waitSec);
            $result = callSigmaApi($endpoint, $params); // Retry once
        }
    }

    if ($result['http_code'] == 200) {
        return $result['response'];
    }

    // Log Error
    return null;
}

// Variables for UI
$message = "";
$messageType = "";
$results = null;

if (isset($_POST['sync'])) {

    // 1. Fetch Products
    $rawProducts = fetchApiData('ExportArticulos');

    if ($rawProducts) {

        // Filter and Index Products by ID
        $productsById = [];
        $eanToId = []; // Map EAN -> Internal ID

        foreach ($rawProducts as $p) {
            // Filter Supplier 00992
            $supplier = $p['proveedorCodigo'] ?? ($p['proveedorId'] ?? '');
            if ((string) $supplier === '00992') {
                $p['final_stock'] = 0;
                $p['final_price'] = 0;
                $p['final_price_source'] = 'none';
                // Map Category from 'grupoDescripcion'
                $p['final_category'] = $p['grupoDescripcion'] ?? 'Varios';
                // Map Bultos
                $p['final_bulto'] = $p['unidadesPorBulto'] ?? 1;

                $pid = (string) $p['id'];
                $productsById[$pid] = $p;

                // Index by EAN for price lookup
                $ean = $p['eanUnidad'] ?? ($p['codigoBarras'] ?? '');
                if (!empty($ean)) {
                    $eanToId[(string) $ean] = $pid;
                }
            }
        }

        if (count($productsById) > 0) {

            // 2. Fetch Stock (Deposit 01)
            sleep(1);
            $rawStock = fetchApiData('ExportStock', ['depo' => '01']);

            if ($rawStock && is_array($rawStock)) {
                foreach ($rawStock as $s) {
                    // FIX: API uses 'articuloId' for Stock endpoint
                    $sid = (string) ($s['articuloId'] ?? ($s['id'] ?? ''));

                    if (isset($productsById[$sid])) {
                        $stock = $s['stock'] ?? ($s['stockFisico'] ?? 0);
                        $productsById[$sid]['final_stock'] = $stock;
                    }
                }
            }

            // 3. Fetch Prices (List 5) - DISABLED BY USER REQUEST
            // Reverting to "Cost" logic for Price (as first implementation)
/*
            sleep(1); 
            $rawPrices = fetchApiData('ExportArticulosPrecios', ['lista' => '5']);
            
            if ($rawPrices && is_array($rawPrices)) {
                // ... (Logic removed/commented) ...
            }
*/

            // 3. Prices
            foreach ($productsById as &$p) {
                // Use Cost as Final Cost
                $cost = $p['costoListaProveedor'] ?? ($p['costoTeorico'] ?? 0);
                $p['final_cost'] = $cost;
            }
            unset($p); // Break ref

            // 4. Update Database
            $count = 0;
            $updated = 0;
            $created = 0;

            $checkSql = "SELECT id FROM products WHERE sigma_id = ?";
            $stmtExists = $conn->prepare($checkSql);

            // Update: Set Cost, Recalculate Price based on Cost and Margin
            // Note: We use the new cost for both the cost column and the price calculation
            $updateSql = "UPDATE products SET nombre = ?, precio_costo = ?, precio_venta = ? * (1 + margen/100), stock = ?, codigo_barras = ?, categoria = ?, unidades_por_bulto = ? WHERE sigma_id = ?";
            $stmtUpdate = $conn->prepare($updateSql);

            // Insert: Set Cost, Default Margin 30, Calculate Price
            $insertSql = "INSERT INTO products (sigma_id, nombre, precio_costo, margen, precio_venta, stock, codigo_barras, categoria, unidades_por_bulto) VALUES (?, ?, ?, 30, ?, ?, ?, ?, ?)";
            $stmtInsert = $conn->prepare($insertSql);

            foreach ($productsById as $id => $data) {
                $name = $data['descripcion'] ?? 'Sin Nombre';
                $cost = $data['final_cost'];
                $stock = $data['final_stock'];
                $barcode = $data['eanUnidad'] ?? ($data['codigoBarras'] ?? null);
                $category = $data['final_category'];
                $bulto = $data['final_bulto'];

                $stmtExists->bind_param("s", $id);
                $stmtExists->execute();
                $existsRes = $stmtExists->get_result();

                if ($existsRes->num_rows > 0) {
                    // Update: Name, Cost, Cost (for price calc), Stock, Barcode, Category, Bulto, ID
                    $stmtUpdate->bind_param("sddissis", $name, $cost, $cost, $stock, $barcode, $category, $bulto, $id);
                    $stmtUpdate->execute();
                    $updated++;
                } else {
                    // Insert
                    $initial_price = $cost * 1.30; // Cost + 30% default
                    $stmtInsert->bind_param("ssddissi", $id, $name, $cost, $initial_price, $stock, $barcode, $category, $bulto);
                    $stmtInsert->execute();
                    $created++;
                }
                $count++;
            }

            $stmtExists->close();
            $stmtUpdate->close();
            $stmtInsert->close();

            $messageType = "success";
            $message = "Sincronización Avanzada completada.<br>Productos procesados: $count <br>(Stock/Precios combinados de múltiples fuentes).";

            $results = [
                'processed' => $count,
                'created' => $created,
                'updated' => $updated,
                'skipped' => (count($rawProducts) - count($productsById))
            ];

        } else {
            $messageType = "warning";
            $message = "No se encontraron productos del proveedor 00992.";
        }

    } else {
        $messageType = "danger";
        $message = "Falló la descarga del catálogo principal (ExportArticulos). Posible error de API.";
    }
}
?>

<div class="form-card" style="max-width: 800px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 30px;">
        <i class="fa-solid fa-rotate-right"
            style="font-size: 48px; color: var(--primary-blue); margin-bottom: 15px;"></i>
        <h2 style="color: var(--text-dark);">Sincronización con Sigma</h2>
        <p style="color: #666;">Actualiza tu catálogo local con los datos más recientes de ApiSigma.</p>
    </div>

    <!-- Status Messages -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 25px; border-radius: 8px;">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <!-- Results Stats -->
    <?php if ($results): ?>
        <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 30px;">
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
                <h3 style="color: var(--primary-blue); margin:0; font-size: 24px;"><?php echo $results['processed']; ?></h3>
                <span style="font-size: 12px; color: #666;">Procesados</span>
            </div>
            <div style="background: #e8f5e9; padding: 15px; border-radius: 8px; text-align: center;">
                <h3 style="color: #2e7d32; margin:0; font-size: 24px;"><?php echo $results['created']; ?></h3>
                <span style="font-size: 12px; color: #666;">Nuevos</span>
            </div>
            <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; text-align: center;">
                <h3 style="color: #1565c0; margin:0; font-size: 24px;"><?php echo $results['updated']; ?></h3>
                <span style="font-size: 12px; color: #666;">Actualizados</span>
            </div>
            <div
                style="background: #fafafa; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid #eee;">
                <h3 style="color: #999; margin:0; font-size: 24px;"><?php echo $results['skipped']; ?></h3>
                <span style="font-size: 12px; color: #666;">Otros Prov.</span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Actions -->
    <form method="POST" id="syncForm">
        <div style="display: flex; gap: 15px; justify-content: center;">
            <a href="productos.php" class="btn btn-secondary"
                style="background-color: #6c757d; color: white; padding: 12px 25px; border-radius: 8px; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </a>
            <button type="submit" name="sync" class="btn btn-primary"
                style="background-color: var(--primary-blue); color: white; padding: 12px 30px; border-radius: 8px; border: none; cursor: pointer; font-size: 16px; font-weight: 500;"
                onclick="showLoading()">
                <i class="fa-solid fa-cloud-arrow-down"></i> Iniciar Sincronización
            </button>
        </div>
    </form>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.8); z-index: 9999; justify-content: center; align-items: center; flex-direction: column;">
    <div class="spinner-border text-primary" role="status"
        style="width: 3rem; height: 3rem; border: 5px solid #f3f3f3; border-top: 5px solid var(--primary-blue); border-radius: 50%; animation: spin 1s linear infinite;">
    </div>
    <h4 style="margin-top: 20px; color: var(--text-dark);">Sincronizando...</h4>
    <p style="color: #666;">Esto puede tardar unos momentos (Descargando Stock y Precios)</p>
</div>

<style>
    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }
</style>

<script>
    function showLoading() {
        document.getElementById('loadingOverlay').style.display = 'flex';
    }
</script>