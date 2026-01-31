<?php
// admin/editar_producto.php
require_once '../config/db.php';
require_once 'includes/auth_check.php';

$active = 'productos';
$page_title = 'Editar Producto';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: productos.php");
    exit();
}

$mensaje = '';

// Handle Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $codigo = $_POST['codigo'];
    $nombre = $_POST['nombre'];
    $costo = !empty($_POST['costo']) ? $_POST['costo'] : 0;
    $margen = !empty($_POST['margen']) ? $_POST['margen'] : 0;
    $stock = $_POST['stock'];
    $categoria = $_POST['categoria'];

    // Prioritize direct Price input
    if (isset($_POST['precio_venta']) && $_POST['precio_venta'] !== '') {
        $precio_venta = $_POST['precio_venta'];
    } else {
        // Fallback calc
        $precio_venta = calcularPrecioVenta($costo, $margen);
    }

    $es_novedad = isset($_POST['es_novedad']) ? 1 : 0;
    $es_oferta = isset($_POST['es_oferta']) ? 1 : 0;
    $activo = isset($_POST['activo']) ? 1 : 0;
    $descuento = $_POST['descuento'] ?? 0;

    // Handle Image
    $sql_img = "";
    $types = "ssdddisiiid";
    $params = [$codigo, $nombre, $costo, $margen, $precio_venta, $stock, $categoria, $es_novedad, $es_oferta, $activo, $descuento];

    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $target_dir = "../img/productos/";
        if (!file_exists($target_dir))
            mkdir($target_dir, 0777, true);

        $file_extension = strtolower(pathinfo($_FILES["imagen"]["name"], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($file_extension, $allowed)) {
            $new_filename = $codigo . '_' . time() . '.' . $file_extension;
            if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $target_dir . $new_filename)) {
                $imagen_url = "img/productos/" . $new_filename;
                $sql_img = ", imagen_url=?";
                $types .= "s";
                $params[] = $imagen_url;
            }
        }
    }

    // Prepare Update Query
    $sql = "UPDATE products SET codigo_barras=?, nombre=?, precio_costo=?, margen=?, precio_venta=?, stock=?, categoria=?, es_novedad=?, es_oferta=?, activo=?, descuento_porcentaje=? $sql_img WHERE id=?";

    // Add ID to params
    $types .= "i";
    $params[] = $id;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $mensaje = '<div class="status-badge status-active">Producto actualizado correctamente</div>';
    } else {
        $mensaje = '<div class="status-badge status-inactive">Error: ' . $conn->error . '</div>';
    }
}

// Fetch Product
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();

if (!$p) {
    echo "Producto no encontrado";
    exit();
}

include 'includes/header.php';
?>

<div class="admin-header">
    <h2>Editar Producto</h2>
    <a href="productos.php" class="btn-secondary" style="font-size: 14px; padding: 8px 15px;"><i
            class="fa-solid fa-arrow-left"></i> Volver</a>
</div>

<div class="admin-content">
    <?php echo $mensaje; ?>

    <div class="form-card">
        <h3
            style="margin-bottom: 20px; color: var(--primary-blue); border-bottom: 2px solid #eee; padding-bottom: 10px;">
            <i class="fa-solid fa-pen-to-square"></i> <?php echo htmlspecialchars($p['nombre']); ?>
        </h3>

        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">

            <!-- Section 1: Basic Info -->
            <h5 style="color: #666; margin-bottom: 15px; font-weight: 600;">Información Básica</h5>
            <div class="form-row">
                <div class="form-col" style="flex: 1;">
                    <label class="form-label">ID Sigma</label>
                    <input type="text" name="sigma_id" class="form-control"
                        value="<?php echo htmlspecialchars($p['sigma_id']); ?>"
                        style="background-color: #f0f0f0; font-family:monospace;">
                </div>
                <div class="form-col" style="flex: 1;">
                    <label class="form-label">Código Barra</label>
                    <input type="text" name="codigo" class="form-control"
                        value="<?php echo htmlspecialchars($p['codigo_barras']); ?>" required>
                </div>
                <div class="form-col" style="flex: 3;">
                    <label class="form-label">Nombre del Producto</label>
                    <input type="text" name="nombre" class="form-control"
                        value="<?php echo htmlspecialchars($p['nombre']); ?>" required>
                </div>
                <div class="form-col" style="flex: 1;">
                    <label class="form-label">Categoría</label>
                    <select name="categoria" class="form-control">
                        <option value="Almacén" <?php echo ($p['categoria'] == 'Almacén') ? 'selected' : ''; ?>>Almacén
                        </option>
                        <option value="Golosinas" <?php echo ($p['categoria'] == 'Golosinas') ? 'selected' : ''; ?>>
                            Golosinas</option>
                        <option value="Chocolates" <?php echo ($p['categoria'] == 'Chocolates') ? 'selected' : ''; ?>>
                            Chocolates</option>
                        <option value="Galletas" <?php echo ($p['categoria'] == 'Galletas') ? 'selected' : ''; ?>>Galletas
                        </option>
                        <option value="Varios" <?php echo ($p['categoria'] == 'Varios') ? 'selected' : ''; ?>>Varios
                        </option>
                    </select>
                </div>
            </div>

            <hr style="margin: 25px 0; border: 0; border-top: 1px solid #eee;">

            <!-- Section 2: Pricing & Stock -->
            <h5 style="color: #666; margin-bottom: 15px; font-weight: 600;">Precios y Stock</h5>
            <div class="form-row">
                <div class="form-col"
                    style="background: #e3f2fd; padding: 15px; border-radius: 8px; border: 1px solid #bbdefb;">
                    <label class="form-label" style="color: #0d47a1; font-weight: bold;">Precio Venta Final ($)</label>
                    <input type="number" step="0.01" name="precio_venta" class="form-control"
                        value="<?php echo $p['precio_venta']; ?>" required
                        style="font-weight: bold; font-size: 16px; color: #0d47a1;">
                    <small style="display:block; margin-top:5px; color:#555;">Este es el precio que verá el
                        cliente.</small>
                </div>

                <div class="form-col">
                    <label class="form-label">Costo ($) <small class="text-muted">(Opcional)</small></label>
                    <input type="number" step="0.01" name="costo" class="form-control"
                        value="<?php echo $p['precio_costo']; ?>">
                </div>
                <div class="form-col">
                    <label class="form-label">Margen (%) <small class="text-muted">(Opcional)</small></label>
                    <input type="number" step="0.01" name="margen" class="form-control"
                        value="<?php echo $p['margen']; ?>">
                </div>
                <div class="form-col">
                    <label class="form-label">Stock Actual</label>
                    <input type="number" name="stock" class="form-control" value="<?php echo $p['stock']; ?>"
                        style="font-weight: bold;">
                </div>
            </div>

            <hr style="margin: 25px 0; border: 0; border-top: 1px solid #eee;">

            <!-- Section 3: Settings & Media -->
            <h5 style="color: #666; margin-bottom: 15px; font-weight: 600;">Configuración y Media</h5>
            <div class="form-row">
                <div class="form-col">
                    <div style="background: #f5f5f5; padding: 15px; border-radius: 8px;">
                        <label class="form-label">Opciones de Visualización</label>
                        <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 10px;">
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="activo" <?php echo ($p['activo']) ? 'checked' : ''; ?>
                                    style="width: 18px; height: 18px;">
                                <strong>Producto Activo</strong>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="es_novedad" <?php echo ($p['es_novedad']) ? 'checked' : ''; ?> style="width: 18px; height: 18px;">
                                Marca como <strong>Novedad</strong>
                            </label>
                        </div>
                        <div style="display: flex; gap: 20px; align-items: center;">
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" name="es_oferta" <?php echo ($p['es_oferta']) ? 'checked' : ''; ?> style="width: 18px; height: 18px;">
                                Es <strong>Oferta</strong>
                            </label>
                            <div style="display: flex; align-items: center; gap: 10px; margin-left: auto;">
                                <span style="font-size: 13px;">Descuento %:</span>
                                <input type="number" step="0.01" name="descuento" class="form-control"
                                    style="width: 80px;" value="<?php echo $p['descuento_porcentaje']; ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-col">
                    <label class="form-label">Imagen del Producto</label>
                    <input type="file" name="imagen" class="form-control" accept="image/*">
                    <?php if ($p['imagen_url']): ?>
                        <div
                            style="margin-top: 10px; display: flex; align-items: center; gap: 10px; background: #fff; padding: 5px; border: 1px solid #eee; border-radius: 4px; width: fit-content;">
                            <img src="../<?php echo $p['imagen_url']; ?>"
                                style="height: 40px; width: auto; object-fit: contain;">
                            <div style="font-size: 12px;">
                                <span style="display: block; color: #999;">Imagen Actual</span>
                                <a href="../<?php echo $p['imagen_url']; ?>" target="_blank"
                                    style="text-decoration: none; color: var(--primary-blue);">Ver tamaño completo</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div style="margin-top: 30px; text-align: right; border-top: 1px solid #eee; padding-top: 20px;">
                <a href="productos.php" class="btn-secondary"
                    style="margin-right: 10px; text-decoration: none; padding: 10px 20px; font-size: 14px; background: #eee; color: #333; border: none;">Cancelar</a>
                <button type="submit" class="btn-primary" style="padding: 10px 30px; font-size: 16px;">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const costoInput = document.querySelector('input[name="costo"]');
        const margenInput = document.querySelector('input[name="margen"]');
        const precioInput = document.querySelector('input[name="precio_venta"]');

        function calculatePrice() {
            const costo = parseFloat(costoInput.value) || 0;
            const margen = parseFloat(margenInput.value) || 0;

            if (costo > 0) {
                const precioVenta = costo * (1 + (margen / 100));
                precioInput.value = precioVenta.toFixed(2);
            }
        }

        costoInput.addEventListener('input', calculatePrice);
        margenInput.addEventListener('input', calculatePrice);
    });
</script>

<?php include 'includes/footer.php'; ?>