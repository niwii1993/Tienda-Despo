<?php
// checkout_success.php
require_once 'config/db.php';
session_start();

if (!isset($_GET['order'])) {
    header("Location: index.php");
    exit();
}
$order_id = intval($_GET['order']);

// Fetch Order Details
$sql_order = "SELECT o.*, u.nombre, u.apellido, u.email, u.telefono, u.direccion, u.ciudad, u.provincia 
              FROM orders o 
              JOIN users u ON o.user_id = u.id 
              WHERE o.id = $order_id";
$result_order = $conn->query($sql_order);

if ($result_order->num_rows == 0) {
    header("Location: index.php");
    exit();
}
$order = $result_order->fetch_assoc();

// Check if current user owns this order (Security)
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $order['user_id']) {
    // Optional: Allow viewing if just created in this session? 
    // For now strict check, redirect if not logged in user
    header("Location: index.php");
    exit();
}

// Fetch Items
$sql_items = "SELECT oi.*, p.nombre FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = $order_id";
$items = $conn->query($sql_items);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Pedido #<?php echo $order_id; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background-color: #f0f2f5;
        }

        .receipt-card {
            max-width: 600px;
            margin: 40px auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            position: relative;
        }

        .receipt-header {
            background: var(--primary-blue);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .receipt-header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 100%;
            height: 20px;
            background: white;
            border-radius: 20px 20px 0 0;
        }

        .success-icon {
            font-size: 50px;
            margin-bottom: 10px;
            color: var(--accent-yellow);
        }

        .receipt-body {
            padding: 30px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .info-label {
            color: #888;
        }

        .info-value {
            font-weight: 500;
            text-align: right;
        }

        .items-table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
        }

        .items-table th {
            text-align: left;
            color: #888;
            font-weight: 400;
            font-size: 12px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .items-table td {
            padding: 12px 0;
            border-bottom: 1px solid #f9f9f9;
            font-size: 14px;
        }

        .items-table td:last-child {
            text-align: right;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px dashed #eee;
            font-size: 20px;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .print-btn {
            display: block;
            width: 100%;
            text-align: center;
            background: #fff;
            border: 2px solid #eee;
            padding: 12px;
            border-radius: 8px;
            margin-top: 20px;
            color: #555;
            text-decoration: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }

        .print-btn:hover {
            border-color: var(--primary-blue);
            color: var(--primary-blue);
        }

        .home-btn {
            display: block;
            width: 100%;
            text-align: center;
            background: var(--primary-blue);
            color: white;
            padding: 14px;
            border-radius: 8px;
            margin-top: 15px;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="receipt-card">
            <div class="receipt-header">
                <div class="success-icon"><i class="fa-solid fa-check-circle"></i></div>
                <h2>¡Pedido Confirmado!</h2>
                <p style="opacity: 0.9;">Gracias por tu compra</p>
            </div>

            <div class="receipt-body">
                <div style="text-align: center; margin-bottom: 30px;">
                    <p style="color: #888; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">Orden N°
                    </p>
                    <p style="font-size: 32px; font-weight: 700; color: #333;">
                        <?php echo str_pad($order_id, 8, '0', STR_PAD_LEFT); ?></p>
                    <p style="color: #888; font-size: 12px; margin-top: 5px;">
                        <?php echo date('d/m/Y H:i', strtotime($order['fecha_pedido'])); ?></p>
                </div>

                <div class="info-row">
                    <span class="info-label">Cliente</span>
                    <span
                        class="info-value"><?php echo htmlspecialchars($order['nombre'] . ' ' . $order['apellido']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email</span>
                    <span class="info-value"><?php echo htmlspecialchars($order['email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Dirección de Entrega</span>
                    <span
                        class="info-value"><?php echo htmlspecialchars($order['direccion']); ?><br><?php echo htmlspecialchars($order['ciudad']); ?></span>
                </div>

                <table class="items-table">
                    <thead>
                        <tr>
                            <th>PRODUCTO</th>
                            <th style="text-align: center;">CANT</th>
                            <th>TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $subtotal_items = 0;
                        while ($item = $items->fetch_assoc()):
                            $item_total = $item['precio_unitario'] * $item['cantidad'];
                            $subtotal_items += $item_total;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                                <td style="text-align: center;">x<?php echo $item['cantidad']; ?></td>
                                <td><?php echo formatPrecio($item_total); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <!-- Calculate Shipping based on logic (reverse engineer from total if needed, or re-calc) -->
                <?php
                // Simple logic used in checkout
                $costo_envio = ($order['total'] - $subtotal_items);
                ?>

                <div class="info-row">
                    <span class="info-label">Subtotal</span>
                    <span class="info-value"><?php echo formatPrecio($subtotal_items); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Envío</span>
                    <span
                        class="info-value"><?php echo ($costo_envio <= 0) ? 'Gratis' : formatPrecio($costo_envio); ?></span>
                </div>

                <div class="total-row">
                    <span>Total Pagado</span>
                    <span><?php echo formatPrecio($order['total']); ?></span>
                </div>

                <button onclick="window.print()" class="print-btn"><i class="fa-solid fa-print"></i> Imprimir
                    Comprobante</button>
                <a href="index.php" class="home-btn">Volver a la Tienda</a>
            </div>
        </div>
    </div>

</body>

</html>