<?php
// admin/index.php
$active = 'dashboard';
$page_title = 'Panel de Control';

// Mock DB connection if file doesn't exist yet for seamless browsing, but we created it.
require_once '../config/db.php';
require_once 'includes/auth_check.php';

// Stats queries
$sql_prod = "SELECT COUNT(*) as total FROM products";
$total_productos = $conn->query($sql_prod)->fetch_assoc()['total'];

$sql_orders = "SELECT COUNT(*) as total FROM orders WHERE estado = 'pendiente'";
$pedidos_pendientes = $conn->query($sql_orders)->fetch_assoc()['total'];

include 'includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-info">
            <h3>
                <?php echo $total_productos; ?>
            </h3>
            <p>Productos Totales</p>
        </div>
        <div class="stat-icon">
            <i class="fa-solid fa-box"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <h3>
                <?php echo $pedidos_pendientes; ?>
            </h3>
            <p>Pedidos Pendientes</p>
        </div>
        <div class="stat-icon">
            <i class="fa-solid fa-clipboard-list"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <h3>$0</h3>
            <p>Ventas del Mes</p>
        </div>
        <div class="stat-icon">
            <i class="fa-solid fa-money-bill-wave"></i>
        </div>
    </div>
</div>

<div class="form-card">
    <h3>Bienvenido al sistema de gestión</h3>
    <p>Desde aquí podrás administrar el catálogo de Arcor, gestionar pedidos y clientes.</p>
    <br>
    <a href="productos.php" class="btn-primary">Gestionar Productos</a>
    <a href="cargar_masiva.php" class="btn-primary" style="background-color: var(--primary-blue); color: white;">Carga
        Masiva CSV</a>
</div>

<?php include 'includes/footer.php'; ?>