<?php
// admin/cargar_masiva.php
require_once '../config/db.php';
require_once 'includes/auth_check.php';

$active = 'productos';
$page_title = 'Carga Masiva de Productos';

$mensaje = '';
$registros_procesados = 0;
$errores = [];

// Handle Exports
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'export_clients') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=clientes_' . date('Y-m-d') . '.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, array('ID', 'Sigma ID', 'Nombre', 'Apellido', 'Email', 'Telefono', 'Direccion', 'Ciudad', 'Provincia', 'Vendedor ID'));

        $rows = $conn->query("SELECT id, sigma_id, nombre, apellido, email, telefono, direccion, ciudad, provincia, seller_id FROM users WHERE role='cliente'");
        while ($row = $rows->fetch_assoc()) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit();
    } elseif ($_GET['action'] == 'export_products') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=productos_' . date('Y-m-d') . '.csv');
        $output = fopen('php://output', 'w');
        // CSV Header as requested in import format + ID
        fputcsv($output, array('ID', 'Codigo', 'Nombre', 'Costo', 'Margen', 'Stock', 'Categoria', 'EsNovedad', 'EsOferta', 'Descuento'));

        $rows = $conn->query("SELECT id, codigo_barras, nombre, precio_costo, margen, stock, categoria, es_novedad, es_oferta, descuento_porcentaje FROM products");
        while ($row = $rows->fetch_assoc()) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit();
    } elseif ($_GET['action'] == 'export_db_full') {
        // Export Full DB
        $filename = 'arcor_db_backup_' . date('Y-m-d_H-i-s') . '.sql';

        // Disable output buffering to ensure clean download
        if (ob_get_level())
            ob_end_clean();

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');

        // Use mysqldump from XAMPP path
        // Force utf8mb4 to preserve accents and ñ
        $cmd = 'C:\xampp\mysql\bin\mysqldump.exe --host=localhost --user=root --opt --routines --events --hex-blob --default-character-set=utf8mb4 arcor_db';
        passthru($cmd);
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];

    // Check for errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $mensaje = '<div class="status-badge status-inactive">Error al subir archivo.</div>';
    } else {
        // Parse CSV
        if (($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
            // Skip header row
            fgetcsv($handle, 1000, ",");

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Expected CSV Format:
                // 0: CodigoBarra, 1: Nombre, 2: Costo, 3: Margen, 4: Stock, 5: Categoria, 6: EsNovedad(0/1), 7: EsOferta(0/1), 8: Descuento%

                // Basic Validation
                if (count($data) < 5)
                    continue;

                $codigo = $data[0] ?? '';
                $nombre = $data[1] ?? '';
                $costo = floatval($data[2] ?? 0);
                $margen = floatval($data[3] ?? 30);
                $stock = intval($data[4] ?? 0);
                $categoria = $data[5] ?? 'Varios';
                $es_novedad = intval($data[6] ?? 0);
                $es_oferta = intval($data[7] ?? 0);
                $descuento = floatval($data[8] ?? 0);

                // Calculate price
                $precio_venta = calcularPrecioVenta($costo, $margen);

                // Insert or Update (Upsert)
                // We use ON DUPLICATE KEY UPDATE to update existing products by Code
                $sql = "INSERT INTO products (codigo_barras, nombre, precio_costo, margen, precio_venta, stock, categoria, es_novedad, es_oferta, descuento_porcentaje) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                        nombre=VALUES(nombre), precio_costo=VALUES(precio_costo), margen=VALUES(margen), 
                        precio_venta=VALUES(precio_venta), stock=VALUES(stock), categoria=VALUES(categoria), 
                        es_novedad=VALUES(es_novedad), es_oferta=VALUES(es_oferta), descuento_porcentaje=VALUES(descuento_porcentaje)";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssdddisiid", $codigo, $nombre, $costo, $margen, $precio_venta, $stock, $categoria, $es_novedad, $es_oferta, $descuento);

                if ($stmt->execute()) {
                    $registros_procesados++;
                } else {
                    $errores[] = "Error en producto $codigo: " . $conn->error;
                }
            }
            fclose($handle);
            $mensaje = '<div class="status-badge status-active">Proceso completado. ' . $registros_procesados . ' productos procesados.</div>';
            if (!empty($errores)) {
                $mensaje .= '<br><div class="status-badge status-inactive">Hubo errores en algunos registros.</div>';
            }
        } else {
            $mensaje = '<div class="status-badge status-inactive">No se pudo abrir el archivo CSV.</div>';
        }
    }
}

include 'includes/header.php';
?>

<?php echo $mensaje; ?>

<style>
    .btn-export {
        text-decoration: none;
        padding: 10px 20px;
        border: 1px solid var(--primary-blue);
        color: var(--primary-blue);
        border-radius: 5px;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-export:hover {
        background-color: var(--primary-blue);
        color: white !important;
    }
</style>

<div class="form-card">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h4>Herramientas de Exportación</h4>
    </div>
    <p style="color:#666; margin-bottom:15px;">Descarga la base de datos actual en formato CSV.</p>
    <div style="display:flex; gap:15px;">
        <a href="?action=export_clients" class="btn-secondary btn-export">
            <i class="fa-solid fa-file-csv"></i> Exportar Clientes
        </a>
        <a href="?action=export_products" class="btn-secondary btn-export">
            <i class="fa-solid fa-boxes-packing"></i> Exportar Productos
        </a>
        <a href="?action=export_db_full" class="btn-secondary btn-export">
            <i class="fa-solid fa-database"></i> Backup Completo (SQL)
        </a>
    </div>
</div>

<div class="form-card">
    <h4>Importar Productos desde CSV</h4>
    <p style="margin-bottom: 20px; color: #666;">
        Sube un archivo CSV con el siguiente formato:<br>
        <code>Codigo, Nombre, Costo, Margen, Stock, Categoria, EsNovedad(1/0), EsOferta(1/0), Descuento</code>
    </p>

    <div style="background-color: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        <i class="fa-solid fa-download"></i> <a href="ejemplo_productos.csv" download
            style="color: #1976D2; font-weight: 500;">Descargar archivo modelo de ejemplo</a>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label class="form-label">Seleccionar Archivo (.csv)</label>
            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
        </div>

        <div class="form-group">
            <button type="submit" class="btn-primary">Procesar Archivo</button>
        </div>
    </form>
</div>

<!-- Errors List if any -->
<?php if (!empty($errores)): ?>
    <div class="form-card" style="margin-top: 20px; border-left: 4px solid #f44336;">
        <h4>Detalle de Errores</h4>
        <ul>
            <?php foreach ($errores as $err): ?>
                <li>
                    <?php echo $err; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>