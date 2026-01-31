<?php
// admin/actualizar_margenes.php
require_once '../config/db.php';
require_once 'includes/auth_check.php';

$active = 'productos';
$page_title = 'Actualizar Márgenes Masivamente';
$mensaje = '';

// Handle Bulk Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_margin'])) {
    $categoria = $_POST['categoria'];
    $nuevo_margen = floatval($_POST['nuevo_margen']);

    if ($nuevo_margen > 0) {
        // Logic:
        // 1. Update Margin
        // 2. Recalculate Price (Price = Cost * (1 + Margin/100))

        if ($categoria == 'todas') {
            $sql = "UPDATE products SET 
                    margen = ?, 
                    precio_venta = precio_costo * (1 + (? / 100)) 
                    WHERE precio_costo > 0";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("dd", $nuevo_margen, $nuevo_margen);
        } else {
            $sql = "UPDATE products SET 
                    margen = ?, 
                    precio_venta = precio_costo * (1 + (? / 100)) 
                    WHERE categoria = ? AND precio_costo > 0";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("dds", $nuevo_margen, $nuevo_margen, $categoria);
        }

        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $mensaje = '<div class="status-badge status-active">Se actualizaron ' . $affected . ' productos correctamente.</div>';
        } else {
            $mensaje = '<div class="status-badge status-inactive">Error al actualizar: ' . $conn->error . '</div>';
        }
    } else {
        $mensaje = '<div class="status-badge status-inactive">Por favor ingrese un margen válido mayor a 0.</div>';
    }
}

// Fetch Categories for Dropdown
$cats = $conn->query("SELECT DISTINCT categoria FROM products WHERE categoria IS NOT NULL ORDER BY categoria ASC");

include 'includes/header.php';
?>

<div class="admin-header">
    <h2>Actualizar Márgenes</h2>
    <a href="productos.php" class="btn-secondary" style="font-size: 14px; padding: 8px 15px;"><i
            class="fa-solid fa-arrow-left"></i> Volver</a>
</div>

<div class="admin-content">
    <?php echo $mensaje; ?>

    <div class="form-card" style="max-width: 600px; margin: 0 auto;">
        <h3
            style="margin-bottom: 20px; color: var(--primary-blue); border-bottom: 2px solid #eee; padding-bottom: 10px;">
            <i class="fa-solid fa-tags"></i> Actualización Masiva
        </h3>
        <p style="color: #666; margin-bottom: 20px; font-size: 14px;">
            <i class="fa-solid fa-circle-info"></i> Esta herramienta actualizará el <strong>Margen</strong> y
            recalculará el <strong>Precio de Venta</strong> para todos los productos de la categoría seleccionada,
            basándose en su Costo actual.
        </p>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Seleccionar Categoría</label>
                <select name="categoria" class="form-control" required>
                    <option value="">-- Seleccione una Categoría --</option>
                    <option value="todas">TODAS LAS CATEGORÍAS (Cuidado)</option>
                    <?php while ($c = $cats->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($c['categoria']); ?>">
                            <?php echo htmlspecialchars($c['categoria']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Nuevo Margen de Ganancia (%)</label>
                <input type="number" step="0.01" name="nuevo_margen" class="form-control" placeholder="Ej: 30" required>
            </div>

            <div style="text-align: right; margin-top: 30px;">
                <button type="submit" name="update_margin" class="btn-primary"
                    onclick="return confirm('¿Está seguro? Esto modificará los precios de venta de todos los productos seleccionados.')">
                    <i class="fa-solid fa-bolt"></i> Actualizar Precios
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>