<?php
// admin/pedidos.php
require_once '../config/db.php';
require_once 'includes/auth_check.php';
$active = 'pedidos';
$page_title = 'Gestión de Pedidos';

// Update Status Action
if (isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['status'];
    $conn->query("UPDATE orders SET estado = '$new_status' WHERE id = $order_id");
}

// Fetch Orders (Joined with Users)
$sql = "SELECT o.*, u.nombre, u.apellido, u.email 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.fecha_pedido DESC";
$pedidos = $conn->query($sql);

include 'includes/header.php';
?>

<div class="form-card">
    <h4>Listado de Pedidos</h4>
    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($p = $pedidos->fetch_assoc()): ?>
                    <tr>
                        <td>#
                            <?php echo $p['id']; ?>
                        </td>
                        <td>
                            <div style="font-weight: 500;">
                                <?php echo htmlspecialchars($p['nombre'] . ' ' . $p['apellido']); ?>
                            </div>
                            <div style="font-size: 12px; color: #888;">
                                <?php echo htmlspecialchars($p['email']); ?>
                            </div>
                        </td>
                        <td>
                            <?php echo date('d/m/Y H:i', strtotime($p['fecha_pedido'])); ?>
                        </td>
                        <td><strong>
                                <?php echo formatPrecio($p['total']); ?>
                            </strong></td>
                        <td>
                            <?php
                            $status_class = 'status-inactive'; // Default greyish/red
                            if ($p['estado'] == 'confirmado')
                                $status_class = 'status-active'; // Greenish
                            if ($p['estado'] == 'pendiente')
                                $status_class = 'status-inactive';
                            if ($p['estado'] == 'enviado')
                                $status_active = '#E3F2FD'; // Custom 
                            ?>
                            <span class="status-badge"
                                style="<?php echo ($p['estado'] == 'pendiente') ? 'background:#fff3e0; color:#e65100;' : (($p['estado'] == 'enviado') ? 'background:#e3f2fd; color:#1565c0;' : ''); ?>">
                                <?php echo ucfirst($p['estado']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="pedido_detalle.php?id=<?php echo $p['id']; ?>" class="action-btn"
                                style="background-color: #607D8B; text-decoration: none; display: inline-block;">
                                <i class="fa-solid fa-eye"></i> Ver
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>