<?php
// admin/pedido_detalle.php
require_once '../config/db.php';
require_once 'includes/auth_check.php';
$active = 'pedidos';
$page_title = 'Detalle de Pedido';

if (!isset($_GET['id'])) {
    header("Location: pedidos.php");
    exit();
}

$order_id = intval($_GET['id']);

// Fetch Order Info
$sql_order = "SELECT o.*, u.nombre, u.apellido, u.email, u.telefono, u.direccion, u.ciudad, u.provincia 
              FROM orders o 
              JOIN users u ON o.user_id = u.id 
              WHERE o.id = $order_id";
$order = $conn->query($sql_order)->fetch_assoc();

if (!$order) {
    echo "Pedido no encontrado";
    exit();
}

// Fetch Items
$sql_items = "SELECT oi.*, p.nombre, p.codigo_barras 
              FROM order_items oi 
              JOIN products p ON oi.product_id = p.id 
              WHERE oi.order_id = $order_id";
$items = $conn->query($sql_items);

include 'includes/header.php';
?>

<div style="display: flex; gap: 20px; margin-bottom: 20px;">
    <a href="pedidos.php" class="btn-primary" style="background-color: #999; padding: 8px 15px; font-size: 14px;"><i
            class="fa-solid fa-arrow-left"></i> Volver</a>
</div>

<div class="form-row">
    <!-- Order Data -->
    <div class="form-col" style="flex:2;">
        <div class="form-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h4>Pedido #
                    <?php echo $order['id']; ?>
                </h4>
                <div style="font-size: 14px; color: #666;">
                    <?php echo date('d/m/Y H:i', strtotime($order['fecha_pedido'])); ?>
                </div>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cant</th>
                        <th>Precio Unit.</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = $items->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div>
                                    <?php echo $item['nombre']; ?>
                                </div>
                                <div style="font-size: 11px; color: #999;">
                                    <?php echo $item['codigo_barras']; ?>
                                </div>
                            </td>
                            <td>
                                <?php echo $item['cantidad']; ?>
                            </td>
                            <td>
                                <?php echo formatPrecio($item['precio_unitario']); ?>
                            </td>
                            <td>
                                <?php echo formatPrecio($item['precio_unitario'] * $item['cantidad']); ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr style="background-color: #fafafa;">
                        <td colspan="3" style="text-align: right; font-weight: bold;">Total:</td>
                        <td style="font-weight: bold; font-size: 16px; color: var(--primary-blue);">
                            <?php echo formatPrecio($order['total']); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Customer & Status -->
    <div class="form-col" style="flex:1;">
        <!-- Status Card -->
        <div class="form-card" style="margin-bottom: 20px;">
            <h4 style="margin-bottom: 15px;">Estado del Pedido</h4>
            <form action="pedidos.php" method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">

                <div class="form-group">
                    <select name="status" class="form-control" style="width: 100%; margin-bottom: 15px;">
                        <option value="pendiente" <?php echo ($order['estado'] == 'pendiente') ? 'selected' : ''; ?>>
                            Pendiente</option>
                        <option value="confirmado" <?php echo ($order['estado'] == 'confirmado') ? 'selected' : ''; ?>>
                            Confirmado</option>
                        <option value="enviado" <?php echo ($order['estado'] == 'enviado') ? 'selected' : ''; ?>>Enviado
                        </option>
                        <option value="cancelado" <?php echo ($order['estado'] == 'cancelado') ? 'selected' : ''; ?>>
                            Cancelado</option>
                    </select>
                    <button type="submit" class="btn-primary" style="width: 100%;">Actualizar Estado</button>
                </div>
            </form>

            <hr style="margin: 15px 0;">

            <div style="text-align: center;">
                <a href="send_order.php?id=<?php echo $order['id']; ?>" class="btn-primary"
                    style="background-color: #28a745; display: block; text-align: center; color: white; padding: 10px;">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Enviar a Sigma
                </a>
            </div>

        </div>

        <!-- Customer Card -->
        <div class="form-card">
            <h4>Datos del Cliente</h4>
            <div
                style="margin: 15px 0; padding: 10px; background: #f8f9fa; border-radius: 6px; border: 1px solid #e9ecef;">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_sigma_id">
                    <label style="display:block; font-size:12px; color:#666; margin-bottom:5px;">ID Sigma
                        (Vinculación)</label>
                    <div style="display: flex; gap: 5px;">
                        <input type="text" name="sigma_id" class="form-control"
                            value="<?php echo htmlspecialchars($order['sigma_id'] ?? ''); ?>" placeholder="Ej: 00123"
                            style="padding: 5px; font-size: 13px; font-family: monospace;">
                        <button type="submit" class="btn-primary"
                            style="padding: 5px 10px; font-size: 12px; white-space: nowrap;">
                            <i class="fa-solid fa-floppy-disk"></i> Guardar
                        </button>
                    </div>
                    <?php if (empty($order['sigma_id'])): ?>
                        <small style="color: #d32f2f; display: block; margin-top: 5px; font-size: 11px;">
                            <i class="fa-solid fa-triangle-exclamation"></i> Requerido para enviar a Sigma
                        </small>
                    <?php endif; ?>
                </form>
            </div>

            <p style="margin-top: 10px;"><strong>
                    <?php echo $order['nombre'] . ' ' . $order['apellido']; ?>
                </strong></p>
            <p><a href="mailto:<?php echo $order['email']; ?>">
                    <?php echo $order['email']; ?>
                </a></p>
            <p>
                <?php echo $order['telefono']; ?>
            </p>
            <hr style="margin: 10px 0; border-color: #eee;">
            <p style="font-size: 13px; color: #666;">Dirección de envío:</p>
            <p>
                <?php echo $order['direccion']; ?>
            </p>
            <p>
                <?php echo $order['ciudad'] . ', ' . $order['provincia']; ?>
            </p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>