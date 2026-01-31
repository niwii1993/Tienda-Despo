<?php
// admin/productos.php
require_once '../config/db.php';
require_once 'includes/auth_check.php';

$active = 'productos';
$page_title = 'Gestión de Productos';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Optional: Delete image file from server too check if logic needed
    $conn->query("DELETE FROM products WHERE id = $id");
    header("Location: productos.php?msg=deleted");
    exit();
}

// Handle Form Submission (Add Product)
$mensaje = '';
if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
    $mensaje = '<div class="status-badge status-inactive" style="background:#ffebee; color:#c62828; display:inline-block; margin-bottom:10px;">Producto eliminado correctamente</div>';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $codigo = $_POST['codigo'];
    $nombre = $_POST['nombre'];
    $costo = $_POST['costo'];
    $margen = $_POST['margen'];
    $stock = $_POST['stock'];
    $categoria = $_POST['categoria'];

    // Calculate Final Price
    $precio_venta = calcularPrecioVenta($costo, $margen);

    // Checkboxes (handle as 1 or 0)
    $es_novedad = isset($_POST['es_novedad']) ? 1 : 0;
    $es_oferta = isset($_POST['es_oferta']) ? 1 : 0;
    $descuento = $_POST['descuento'] ?? 0;

    // Handle Image Upload
    $imagen_url = '';
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $target_dir = "../img/productos/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES["imagen"]["name"], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($file_extension, $allowed_ext)) {
            $new_filename = $codigo . '_' . time() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;

            if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $target_file)) {
                $imagen_url = "img/productos/" . $new_filename;
            } else {
                $mensaje = '<div class="status-badge status-inactive">Error al guardar la imagen.</div>';
            }
        }
    }

    $stmt = $conn->prepare("INSERT INTO products (codigo_barras, nombre, precio_costo, margen, precio_venta, stock, categoria, es_novedad, es_oferta, descuento_porcentaje, imagen_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdddisiids", $codigo, $nombre, $costo, $margen, $precio_venta, $stock, $categoria, $es_novedad, $es_oferta, $descuento, $imagen_url);

    if ($stmt->execute()) {
        $mensaje = '<div class="status-badge status-active" style="display:inline-block; margin-bottom:10px;">Producto agregado correctamente</div>';
    } else {
        $mensaje = '<div class="status-badge status-inactive">Error: ' . $conn->error . '</div>';
    }
}

// Fetch Products
// Search Logic with Prepared Statements
$search_query = "";
$where_parts = [];
$params = [];
$types = "";

if (isset($_GET['q']) && !empty($_GET['q'])) {
    $search_term = "%" . $_GET['q'] . "%";
    $where_parts[] = "(nombre LIKE ? OR codigo_barras LIKE ? OR sigma_id LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

if (isset($_GET['stock_only']) && $_GET['stock_only'] == '1') {
    $where_parts[] = "stock > 0";
}

$where_clause = "";
if (!empty($where_parts)) {
    $where_clause = "WHERE " . implode(" AND ", $where_parts);
}

// Pagination Logic
$items_per_page = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Count Total Items
$count_sql = "SELECT COUNT(*) as total FROM products $where_clause";
$stmt_count = $conn->prepare($count_sql);

if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}

$stmt_count->execute();
$total_row = $stmt_count->get_result()->fetch_assoc();
$total_items = $total_row['total'];
$total_pages = ceil($total_items / $items_per_page);

// Fetch Products with Limit
$sql = "SELECT * FROM products $where_clause ORDER BY id DESC LIMIT ?, ?";
$stmt = $conn->prepare($sql);

// Add Limit/Offset to params
$types_with_limit = $types . "ii";
$params_with_limit = $params;
$params_with_limit[] = $offset;
$params_with_limit[] = $items_per_page;

$stmt->bind_param($types_with_limit, ...$params_with_limit);
$stmt->execute();
$productos = $stmt->get_result();

include 'includes/header.php';
?>

<?php echo $mensaje; ?>

<!-- Add Product Form -->
<div class="form-card" style="margin-bottom: 30px;">
    <h4
        style="border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 20px; color: var(--primary-blue);">
        <i class="fa-solid fa-plus-circle"></i> Nuevo Producto
    </h4>
    <form method="POST" action="" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">

        <!-- Section 1: Info -->
        <h5 style="color: #666; font-size: 14px; margin-bottom: 10px; font-weight: 600;">Información General</h5>
        <div class="form-row">
            <div class="form-col" style="flex: 1;">
                <label class="form-label">Código Barra / ID</label>
                <input type="text" name="codigo" class="form-control" placeholder="Ej: 7791234..." required>
            </div>
            <div class="form-col" style="flex: 2;">
                <label class="form-label">Nombre del Producto</label>
                <input type="text" name="nombre" class="form-control" placeholder="Ej: Galletitas..." required>
            </div>
            <div class="form-col" style="flex: 1;">
                <label class="form-label">Categoría</label>
                <select name="categoria" class="form-control">
                    <option value="Almacén">Almacén</option>
                    <option value="Golosinas">Golosinas</option>
                    <option value="Chocolates">Chocolates</option>
                    <option value="Galletas">Galletas</option>
                    <option value="Varios">Varios</option>
                </select>
            </div>
        </div>

        <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">

        <!-- Section 2: Pricing -->
        <h5 style="color: #666; font-size: 14px; margin-bottom: 10px; font-weight: 600;">Precios y Stock</h5>
        <div class="form-row">
            <div class="form-col">
                <label class="form-label">Costo ($)</label>
                <input type="number" step="0.01" name="costo" class="form-control" placeholder="0.00" required>
            </div>
            <div class="form-col">
                <label class="form-label">Margen (%)</label>
                <input type="number" step="0.01" name="margen" class="form-control" value="30" required>
            </div>
            <div class="form-col"
                style="background: #e3f2fd; padding:10px; border-radius:6px; border:1px solid #bbdefb;">
                <label class="form-label" style="color: #1565c0; font-weight:bold;">Precio Venta Calc.</label>
                <input type="text" id="preview_precio" class="form-control" placeholder="$ 0.00" readonly
                    style="background:transparent; border:none; font-weight:bold; color:#1565c0; font-size:1.1em; padding:0;">
            </div>
            <div class="form-col">
                <label class="form-label">Stock Inicial</label>
                <input type="number" name="stock" class="form-control" value="0">
            </div>
        </div>

        <div class="form-row" style="margin-top: 15px;">
            <div class="form-col">
                <label class="form-label">Imagen</label>
                <input type="file" name="imagen" class="form-control" accept="image/*">
            </div>
            <div class="form-col" style="display: flex; align-items: center; gap: 20px; padding-top: 25px;">
                <label style="cursor:pointer;"><input type="checkbox" name="es_novedad"> Novedad</label>
                <label style="cursor:pointer;"><input type="checkbox" name="es_oferta"> Oferta</label>
            </div>
        </div>

        <div style="margin-top: 20px; text-align: right;">
            <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Guardar Producto</button>
        </div>
    </form>
</div>

<!-- Product List -->
<div class="form-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h4 style="margin: 0;">Listado de Productos</h4>
        <div style="display:flex; gap:10px;">
            <a href="sync_stock_fast.php" class="btn-primary"
                style="font-size: 13px; padding: 8px 15px; background: #f1c40f; color: #333; text-decoration:none; border-radius:4px; border:none;">
                <i class="fa-solid fa-bolt"></i> Actualizar Stock
            </a>
            <a href="actualizar_margenes.php" class="btn-secondary"
                style="font-size: 13px; padding: 8px 15px; border: 1px solid var(--primary-blue); color: var(--primary-blue); text-decoration:none; border-radius:4px;">
                <i class="fa-solid fa-percent"></i> Actualizar Márgenes
            </a>
            <form method="GET" action="productos.php" style="display: flex; gap: 10px; align-items: center;">
                <label
                    style="display: flex; align-items: center; gap: 5px; cursor: pointer; margin-right: 5px; font-size: 14px; color: #555;">
                    <input type="checkbox" name="stock_only" value="1" onchange="this.form.submit()" <?php echo (isset($_GET['stock_only']) && $_GET['stock_only'] == '1') ? 'checked' : ''; ?>>
                    Solo con Stock
                </label>
                <input type="text" name="q" placeholder="Buscar..." class="form-control"
                    style="width: 250px; padding: 8px;"
                    value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                <button type="submit" class="btn-primary" style="padding: 8px 15px;"><i
                        class="fa-solid fa-magnifying-glass"></i></button>
                <?php if ((isset($_GET['q']) && !empty($_GET['q'])) || (isset($_GET['stock_only']) && $_GET['stock_only'] == '1')): ?>
                    <a href="productos.php" class="btn-secondary"
                        style="padding: 8px 15px; border: 1px solid #ddd; border-radius: 4px; color: #666; text-decoration: none;"
                        title="Limpiar Filtro"><i class="fa-solid fa-xmark"></i></a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <!-- Pagination -->

    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="text-align: center; width: 60px;">Img</th>
                    <th style="text-align: center;">Códigos</th>
                    <th>Nombre</th>
                    <th style="text-align: center;">Categoría</th>
                    <th style="text-align: center;">Costo</th>
                    <th style="text-align: center;">Venta</th>
                    <th style="text-align: center;">Stock</th>
                    <th style="text-align: center;">U. Bulto</th>
                    <th style="text-align: center;">Estado</th>
                    <th style="text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($p = $productos->fetch_assoc()): ?>
                    <tr>
                        <td style="text-align: center;">
                            <?php if (!empty($p['imagen_url'])): ?>
                                <img src="../<?php echo $p['imagen_url']; ?>"
                                    style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; display:inline-block;">
                            <?php else: ?>
                                <div
                                    style="width: 40px; height: 40px; background: #eee; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center; color: #aaa;">
                                    <i class="fa-solid fa-image"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center; white-space: nowrap; font-weight: 500; color: #555;">
                            <div style="font-size:11px; color:#888;">ID: <?php echo $p['sigma_id']; ?></div>
                            <div style="font-size:12px; color:#333; font-weight:bold;"><?php echo $p['codigo_barras']; ?>
                            </div>
                        </td>
                        <td>
                            <div
                                style="font-weight: 600; color: #333; margin-bottom: 2px; line-height: 1.2; max-width: 200px; white-space: normal;">
                                <?php echo $p['nombre']; ?>
                            </div>
                            <?php if ($p['es_novedad'])
                                echo '<span class="status-badge" style="background:#E3F2FD; color:#1565C0; font-size:10px;">Nuevo</span>'; ?>
                            <?php if ($p['es_oferta'])
                                echo '<span class="status-badge" style="background:#ffebee; color:#c62828; font-size:10px;">-' . intval($p['descuento_porcentaje']) . '%</span>'; ?>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <?php echo htmlspecialchars($p['categoria']); ?>
                        </td>
                        <td style="text-align: center; white-space: nowrap; color: #777;">
                            $<?php echo number_format($p['precio_costo'], 2); ?></td>
                        <td style="text-align: center; white-space: nowrap;">
                            <strong
                                style="color: var(--primary-blue); font-size: 1.1em;"><?php echo formatPrecio($p['precio_venta']); ?></strong>
                        </td>
                        <td style="text-align: center; white-space: nowrap; font-weight: 500;">
                            <?php echo $p['stock']; ?>
                        </td>
                        <td style="text-align:center;"><?php echo $p['unidades_por_bulto'] ?? 1; ?></td>
                        <td style="text-align: center; white-space: nowrap;">
                            <span
                                class="status-badge <?php echo ($p['stock'] > 0) ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo ($p['stock'] > 0) ? 'En Stock' : 'Sin Stock'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="actions-cell" style="justify-content: center;">
                                <a href="editar_producto.php?id=<?php echo $p['id']; ?>" class="action-btn btn-edit"
                                    title="Editar"><i class="fa-solid fa-pen"></i></a>
                                <a href="productos.php?delete=<?php echo $p['id']; ?>" class="action-btn btn-delete"
                                    title="Eliminar"
                                    onclick="return confirm('¿Seguro que deseas eliminar este producto?');"><i
                                        class="fa-solid fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination (Bottom) -->
    <?php if ($total_pages > 1): ?>
        <div style="margin-top: 20px; display: flex; justify-content: center; gap: 5px;">
            <?php
            $range = 2; // Number of pages around current page
            $start = max(1, $page - $range);
            $end = min($total_pages, $page + $range);

            $qs = '';
            if (isset($_GET['q']))
                $qs .= '&q=' . urlencode($_GET['q']);
            if (isset($_GET['stock_only']))
                $qs .= '&stock_only=' . urlencode($_GET['stock_only']);

            // First page link
            if ($start > 1) {
                echo '<a href="?page=1' . $qs . '" class="btn-secondary" style="padding: 5px 10px; border: 1px solid #ddd;">1</a>';
                if ($start > 2) {
                    echo '<span style="padding: 5px;">...</span>';
                }
            }

            // Range loop
            for ($i = $start; $i <= $end; $i++): ?>
                <a href="?page=<?php echo $i; ?><?php echo $qs; ?>" class="btn-secondary"
                    style="padding: 5px 10px; text-decoration: none; <?php echo ($i == $page) ? 'background: var(--primary-blue); color: white; border-color: var(--primary-blue);' : 'background: white; border: 1px solid #ddd;'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor;

            // Last page link
            if ($end < $total_pages) {
                if ($end < $total_pages - 1) {
                    echo '<span style="padding: 5px;">...</span>';
                }
                echo '<a href="?page=' . $total_pages . $qs . '" class="btn-secondary" style="padding: 5px 10px; border: 1px solid #ddd;">' . $total_pages . '</a>';
            }
            ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Add Product Calc
        const costoInput = document.querySelector('input[name="costo"]');
        const margenInput = document.querySelector('input[name="margen"]');
        const previewInput = document.getElementById('preview_precio');

        function calculatePreview() {
            const costo = parseFloat(costoInput.value) || 0;
            const margen = parseFloat(margenInput.value) || 0;

            if (costo >= 0) {
                const precioVenta = costo * (1 + (margen / 100));
                previewInput.value = '$ ' + precioVenta.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        }

        if (costoInput && margenInput) {
            costoInput.addEventListener('input', calculatePreview);
            margenInput.addEventListener('input', calculatePreview);
        }
    });
</script>