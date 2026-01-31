<?php
// checkout.php
session_start();
require_once 'config/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=checkout.php");
    exit();
}

// Redirect if cart is empty
if (empty($_SESSION['carrito'])) {
    header("Location: carrito.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get User Data for confirmation
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Fetch Shipping Configuration
$conf_min_envio = 50000;
$conf_costo_envio = 3000;

$res_conf = $conn->query("SELECT clave, valor FROM configuracion");
if ($res_conf) {
    while ($row = $res_conf->fetch_assoc()) {
        if ($row['clave'] == 'minimo_envio_gratis')
            $conf_min_envio = floatval($row['valor']);
        if ($row['clave'] == 'costo_envio')
            $conf_costo_envio = floatval($row['valor']);
    }
}

// Calculate Total
$total = 0;
foreach ($_SESSION['carrito'] as $item) {
    $total += $item['precio'] * $item['cantidad'];
}
$costo_envio = ($total > $conf_min_envio) ? 0 : $conf_costo_envio;
$total_final = $total + $costo_envio;

// Helper to format currency
function formatMoney($val)
{
    return '$' . number_format($val, 0, ',', '.');
}

// Handle Order Processing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmar'])) {

    // 1. Create Order
    // Start Transaction
    // Start Transaction
    $conn->begin_transaction();

    try {
        // 1. Verify Stock Logic (Locking)
        // Check ALL items stock before creating order
        $stmt_check = $conn->prepare("SELECT stock FROM products WHERE id = ? FOR UPDATE");

        foreach ($_SESSION['carrito'] as $prod_id => $item) {
            $stmt_check->bind_param("i", $item['id']);
            $stmt_check->execute();
            $result = $stmt_check->get_result();

            if ($result->num_rows == 0) {
                throw new Exception("Producto ID {$item['id']} no encontrado");
            }

            $current_stock = $result->fetch_assoc()['stock'];

            if ($current_stock < $item['cantidad']) {
                throw new Exception("Stock insuficiente para: {$item['nombre']}. Disponible: {$current_stock}");
            }
        }

        // 2. Create Order (Only if stock is guaranteed)
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total, estado) VALUES (?, ?, 'pendiente')");
        $stmt->bind_param("id", $user_id, $total_final);

        if (!$stmt->execute()) {
            throw new Exception("Error al crear la orden: " . $conn->error);
        }

        $order_id = $stmt->insert_id;

        // 3. Insert Items and Reduce Stock
        $sql_items = "INSERT INTO order_items (order_id, product_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
        $stmt_item = $conn->prepare($sql_items);

        $sql_stock = "UPDATE products SET stock = stock - ? WHERE id = ?";
        $stmt_stock = $conn->prepare($sql_stock);

        foreach ($_SESSION['carrito'] as $prod_id => $item) {
            // Insert Item
            $stmt_item->bind_param("iiid", $order_id, $item['id'], $item['cantidad'], $item['precio']);
            if (!$stmt_item->execute()) {
                throw new Exception("Error al guardar item: " . $stmt_item->error);
            }

            // Reduce Stock
            $stmt_stock->bind_param("ii", $item['cantidad'], $item['id']);
            if (!$stmt_stock->execute()) {
                throw new Exception("Error al actualizar stock: " . $conn->error);
            }
        }

        // 4. Commit Transaction
        $conn->commit();

        // 5. Clear Cart & Send Emails (After Commit)
        $cart_backup = $_SESSION['carrito'];
        unset($_SESSION['carrito']);

        // ... Email Logic ...
        $to_client = $user['email'];
        $subject_client = "Confirmación de Pedido #" . $order_id . " - Grupo Despo";

        $message_html = "
        <html>
        <head>
            <title>Confirmación de Pedido</title>
        </head>
        <body style='font-family: Arial, sans-serif;'>
            <h2 style='color: #004B8D;'>¡Gracias por tu compra, " . htmlspecialchars($user['nombre']) . "!</h2>
            <p>Tu pedido <strong>#" . $order_id . "</strong> ha sido recibido correctamente.</p>
            
            <h3>Resumen del Pedido:</h3>
            <table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%; max-width: 600px;'>
                <tr style='background-color: #f5f5f5;'>
                    <th>Producto</th>
                    <th>Cant</th>
                    <th>Precio</th>
                    <th>Subtotal</th>
                </tr>";

        foreach ($cart_backup as $item) {
            $message_html .= "
                <tr>
                    <td>" . htmlspecialchars($item['nombre']) . "</td>
                    <td>" . $item['cantidad'] . "</td>
                    <td>" . formatMoney($item['precio']) . "</td>
                    <td>" . formatMoney($item['precio'] * $item['cantidad']) . "</td>
                </tr>";
        }

        $message_html .= "
                <tr>
                    <td colspan='3' align='right'><strong>Total:</strong></td>
                    <td><strong>" . formatMoney($total_final) . "</strong></td>
                </tr>
            </table>

            <p>Nos pondremos en contacto contigo a la brevedad para coordinar el envío.</p>
            <p><em>Grupo Despo - Especialistas en Distribución</em></p>
        </body>
        </html>
        ";

        // Headers
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: no-reply@grupodespo.com" . "\r\n"; // Customize this

        // Send to Client
        @mail($to_client, $subject_client, $message_html, $headers);

        // Send to Admin (Hardcoded or config)
        $admin_email = "nicolas_benitez10@outlook.com"; // User provided email in previous context
        $subject_admin = "Nuevo Pedido #" . $order_id . " - Cliente: " . $user['nombre'];
        @mail($admin_email, $subject_admin, $message_html, $headers);

        // 6. Redirect to Success Page
        header("Location: checkout_success.php?order=" . $order_id);
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error al procesar el pedido: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Compra - Grupo Despo</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">

    <?php if (isset($error)): ?>
        <script>
            alert('<?php echo addslashes($error); ?>');
        </script>
    <?php endif; ?>
</head>

<body>
    <header class="main-header">
        <div class="container">
            <h2 style="color: var(--primary-blue);">Confirmación de Pedido</h2>
        </div>
    </header>

    <div class="container" style="padding: 40px 0;">
        <div style="display: flex; gap: 40px; flex-wrap: wrap;">

            <!-- User Details -->
            <div style="flex: 1; min-width: 300px;">
                <h3 style="margin-bottom: 20px;">Datos de Envío</h3>
                <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: var(--shadow-sm);">
                    <p><strong>Nombre:</strong>
                        <?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido']); ?>
                    </p>
                    <p><strong>Email:</strong>
                        <?php echo htmlspecialchars($user['email']); ?>
                    </p>
                    <p><strong>Teléfono:</strong>
                        <?php echo htmlspecialchars($user['telefono']); ?>
                    </p>
                    <hr style="margin: 15px 0; border: 0; border-top: 1px solid #eee;">
                    <p><strong>Dirección:</strong>
                        <?php echo htmlspecialchars($user['direccion']); ?>
                    </p>
                    <p><strong>Ciudad:</strong>
                        <?php echo htmlspecialchars($user['ciudad']); ?>
                    </p>
                    <p><strong>Provincia:</strong>
                        <?php echo htmlspecialchars($user['provincia']); ?>
                    </p>

                    <div style="margin-top: 20px; color: #666; font-size: 14px;">
                        <i class="fa-solid fa-circle-info"></i> Si estos datos son incorrectos, por favor actualízalos
                        en tu perfil antes de confirmar.
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div style="flex: 1; min-width: 300px;">
                <h3 style="margin-bottom: 20px;">Resumen de Compra</h3>
                <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: var(--shadow-sm);">
                    <ul style="margin-bottom: 20px;">
                        <?php foreach ($_SESSION['carrito'] as $item): ?>
                            <li
                                style="display: flex; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px solid #f5f5f5; padding-bottom: 5px;">
                                <span>
                                    <?php echo $item['cantidad']; ?>x
                                    <?php echo htmlspecialchars($item['nombre']); ?>
                                </span>
                                <span>
                                    <?php echo formatMoney($item['precio'] * $item['cantidad']); ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>Subtotal</span>
                        <span>
                            <?php echo formatMoney($total); ?>
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 20px; color: #2e7d32;">
                        <span>Envío</span>
                        <span>
                            <?php echo ($costo_envio == 0) ? 'Gratis' : formatMoney($costo_envio); ?>
                        </span>
                    </div>
                    <div
                        style="display: flex; justify-content: space-between; font-size: 24px; font-weight: bold; margin-bottom: 30px;">
                        <span>Total a Pagar</span>
                        <span>
                            <?php echo formatMoney($total_final); ?>
                        </span>
                    </div>

                    <form method="POST">
                        <div style="display: flex; gap: 15px; margin-top: 20px;">
                            <a href="index.php" class="btn-secondary"
                                style="flex: 1; text-align: center; border: 1px solid #ccc; background: white; color: #333; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: 600;">Volver
                                al Inicio</a>
                            <button type="submit" name="confirmar" class="btn-primary"
                                style="flex: 1; font-size: 18px;">Confirmar Pedido</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>