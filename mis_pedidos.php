<?php
// mis_pedidos.php
session_start();
require_once 'config/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get User Orders
$sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY fecha_pedido DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - Grupo Despo</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .order-card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 20px;
            overflow: hidden;
            transition: transform 0.2s;
        }

        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .order-header {
            padding: 20px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-body {
            padding: 20px;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pendiente {
            background-color: #fff3e0;
            color: #e65100;
        }

        .status-confirmado {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .status-enviado {
            background-color: #e3f2fd;
            color: #1565c0;
        }

        .status-cancelado {
            background-color: #ffebee;
            color: #c62828;
        }

        .order-items-preview {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <div class="container" style="padding: 40px 20px;">
        <h2 style="margin-bottom: 30px;">Mis Pedidos</h2>

        <?php if ($result->num_rows == 0): ?>
            <div style="text-align: center; padding: 60px; background: white; border-radius: 12px;">
                <i class="fa-solid fa-box-open" style="font-size: 50px; color: #ddd; margin-bottom: 20px;"></i>
                <p>Aún no has realizado ningún pedido.</p>
                <a href="index.php" class="btn-primary" style="margin-top: 20px; display: inline-block;">Comenzar a
                    comprar</a>
            </div>
        <?php else: ?>
            <div style="max-width: 800px; margin: 0 auto;">
                <?php while ($order = $result->fetch_assoc()):
                    // Determine Status Class
                    $status_class = 'status-pendiente';
                    if ($order['estado'] == 'confirmado')
                        $status_class = 'status-confirmado';
                    if ($order['estado'] == 'enviado')
                        $status_class = 'status-enviado';
                    if ($order['estado'] == 'cancelado')
                        $status_class = 'status-cancelado';

                    // Fetch few items for preview
                    $oid = $order['id'];
                    $items_sql = "SELECT COUNT(*) as count, (SELECT nombre FROM products p JOIN order_items oi ON p.id = oi.product_id WHERE oi.order_id = $oid LIMIT 1) as first_item FROM order_items WHERE order_id = $oid";
                    $items_data = $conn->query($items_sql)->fetch_assoc();
                    $item_count = $items_data['count'];
                    $first_item = $items_data['first_item'];
                    $preview_text = $first_item . (($item_count > 1) ? " y " . ($item_count - 1) . " más..." : "");
                    ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <span style="font-weight: 700; font-size: 18px;">Pedido #
                                    <?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>
                                </span>
                                <div style="font-size: 12px; color: #888; margin-top: 4px;">
                                    <?php echo date('d/m/Y H:i', strtotime($order['fecha_pedido'])); ?>
                                </div>
                            </div>
                            <div>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo ucfirst($order['estado']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="order-body">
                            <div class="order-items-preview">
                                <i class="fa-solid fa-basket-shopping" style="margin-right: 5px;"></i>
                                <?php echo $preview_text; ?>
                            </div>
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f0f0f0; padding-top: 15px; margin-top: 10px;">
                                <div style="font-size: 20px; font-weight: 700; color: var(--primary-dark);">
                                    <?php echo formatPrecio($order['total']); ?>
                                </div>
                                <!-- Currently linking to success page as a detailed view, could be a specific order-view page -->
                                <a href="checkout_success.php?order=<?php echo $order['id']; ?>" class="btn-secondary"
                                    style="color: var(--primary-blue); font-weight: 600; text-decoration: none;">
                                    Ver Detalle <i class="fa-solid fa-chevron-right" style="font-size: 12px;"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>