<?php
// admin/configuracion.php
require_once '../config/db.php';
require_once 'includes/auth_check.php';

$active = 'configuracion';
$page_title = 'Configuración del Sistema';
$mensaje = '';

// Handle Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($_POST['conf'] as $clave => $valor) {
        // Sanitize input
        $valor = $conn->real_escape_string($valor);
        $clave = $conn->real_escape_string($clave);

        $sql = "UPDATE configuracion SET valor = '$valor' WHERE clave = '$clave'";
        if (!$conn->query($sql)) {
            $mensaje .= '<div class="status-badge status-inactive">Error al actualizar ' . $clave . '</div>';
        }
    }
    if (empty($mensaje)) {
        $mensaje = '<div class="status-badge status-active">Configuración actualizada correctamente.</div>';
    }
}

// Fetch Configurations
$configs = [];
$result = $conn->query("SELECT * FROM configuracion");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $configs[$row['clave']] = $row;
    }
}

include 'includes/header.php';
?>

<?php echo $mensaje; ?>

<div class="form-card">
    <h4><i class="fa-solid fa-gears"></i> Configuración de Envíos</h4>
    <p class="text-muted">Ajusta los valores globales para el cálculo de envíos en la tienda.</p>

    <form method="POST">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Monto Mínimo para Envío Gratis</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="conf[minimo_envio_gratis]" class="form-control"
                            value="<?php echo htmlspecialchars($configs['minimo_envio_gratis']['valor'] ?? '50000'); ?>"
                            required>
                    </div>
                    <small style="color:#666;">
                        <?php echo $configs['minimo_envio_gratis']['descripcion'] ?? ''; ?>
                    </small>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Costo de Envío Estándar</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="conf[costo_envio]" class="form-control"
                            value="<?php echo htmlspecialchars($configs['costo_envio']['valor'] ?? '3000'); ?>"
                            required>
                    </div>
                    <small style="color:#666;">
                        <?php echo $configs['costo_envio']['descripcion'] ?? ''; ?>
                    </small>
                </div>
            </div>
        </div>

        <div class="form-group" style="margin-top: 20px;">
            <button type="submit" class="btn-primary">
                <i class="fa-solid fa-save"></i> Guardar Cambios
            </button>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>