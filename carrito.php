<?php
// carrito.php
session_start();
require_once 'config/db.php';

$carrito = isset($_SESSION['carrito']) ? $_SESSION['carrito'] : [];

// Initial values (fallbacks)
$conf_min_envio = 50000;
$conf_costo_envio = 3000;

// Fetch config from DB
if (isset($conn)) {
    $res = $conn->query("SELECT clave, valor FROM configuracion");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            if ($row['clave'] == 'minimo_envio_gratis')
                $conf_min_envio = floatval($row['valor']);
            if ($row['clave'] == 'costo_envio')
                $conf_costo_envio = floatval($row['valor']);
        }
    }
}

$subtotal_real = 0; // Sum of original prices
$total_final = 0;   // Sum of final prices (what user pays)
$total_ahorro = 0;  // Difference

foreach ($carrito as $item) {
    // If 'precio_original' is not set (old session data), fallback to 'precio'
    $p_orig = isset($item['precio_original']) ? $item['precio_original'] : $item['precio'];
    $p_final = $item['precio'];
    $qty = $item['cantidad'];

    $subtotal_real += ($p_orig * $qty);
    $total_final += ($p_final * $qty);
}

$total_ahorro = $subtotal_real - $total_final;
$total_ahorro = $subtotal_real - $total_final;
$costo_envio = ($total_final > $conf_min_envio) ? 0 : $conf_costo_envio;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Carrito - Grupo Despo</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <div class="container" style="padding: 40px 20px;">
        <h2 style="margin-bottom: 30px;">Tu Carrito</h2>

        <?php if (empty($carrito)): ?>
            <div style="text-align: center; padding: 50px; background: white; border-radius: 12px;">
                <i class="fa-solid fa-cart-shopping" style="font-size: 60px; color: #ddd; margin-bottom: 20px;"></i>
                <p>Tu carrito está vacío.</p>
                <a href="index.php" class="btn-primary" style="margin-top: 20px; display: inline-block;">Ir a comprar</a>
            </div>
        <?php else: ?>
            <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                <!-- Cart Items -->
                <div style="flex: 2; min-width: 300px;">
                    <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: var(--shadow-sm);">
                        <?php foreach ($carrito as $id => $item):
                            $p_orig = isset($item['precio_original']) ? $item['precio_original'] : $item['precio'];
                            $p_final = $item['precio'];
                            $qty = $item['cantidad'];
                            $row_subtotal = $p_final * $qty;

                            $has_discount = ($p_orig > $p_final);
                            ?>
                            <div style="display: flex; padding: 20px; border-bottom: 1px solid #eee; align-items: center;">
                                <div
                                    style="width: 80px; height: 80px; background: #f9f9f9; display: flex; align-items: center; justify-content: center; border-radius: 8px;">
                                    <img src="<?php echo !empty($item['img']) ? $item['img'] : 'https://via.placeholder.com/80'; ?>"
                                        style="max-width: 60px; max-height: 60px; object-fit: contain;">
                                </div>
                                <div style="flex: 1; padding: 0 20px;">
                                    <h4 style="margin-bottom: 5px;">
                                        <?php echo htmlspecialchars($item['nombre']); ?>
                                        <?php if ($has_discount): ?>
                                            <span class="status-badge"
                                                style="background:var(--badge-red); color:white; font-size:10px; padding: 2px 6px;">Oferta</span>
                                        <?php endif; ?>
                                    </h4>
                                    <div style="color: var(--text-gray); font-size: 14px;">
                                        Precio Unit:
                                        <?php if ($has_discount): ?>
                                            <span
                                                style="text-decoration: line-through; color: #999; margin-right: 5px;"><?php echo formatPrecio($p_orig); ?></span>
                                            <span
                                                style="color: var(--text-dark); font-weight: 600;"><?php echo formatPrecio($p_final); ?></span>
                                        <?php else: ?>
                                            <?php echo formatPrecio($p_final); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="quantity-control" data-id="<?php echo $id; ?>">
                                    <button type="button" class="qty-btn minus" onclick="updateCartItemQty(this, -1)">-</button>
                                    <input type="number" class="qty-input" value="<?php echo $qty; ?>" readonly>
                                    <button type="button" class="qty-btn plus" onclick="updateCartItemQty(this, 1)">+</button>
                                </div>
                                <div style="width: 120px; text-align: right; font-weight: bold; font-size: 18px;">
                                    <?php echo formatPrecio($row_subtotal); ?>
                                </div>
                                <div style="width: 50px; text-align: right;">
                                    <a href="ajax/eliminar_item.php?id=<?php echo $id; ?>" style="color: #F44336;"
                                        title="Eliminar"><i class="fa-solid fa-trash"></i></a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Summary -->
                <div style="flex: 1; min-width: 300px;">
                    <div
                        style="background: white; padding: 25px; border-radius: 12px; box-shadow: var(--shadow-sm); position: sticky; top: 100px;">
                        <h3 style="border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px;">Resumen del
                            Pedido</h3>

                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span style="color: #666;">Subtotal (Precio Lista)</span>
                            <span>
                                <?php echo formatPrecio($subtotal_real); ?>
                            </span>
                        </div>

                        <?php if ($total_ahorro > 0): ?>
                            <div
                                style="display: flex; justify-content: space-between; margin-bottom: 10px; color: var(--badge-red);">
                                <span>Descuentos</span>
                                <span>
                                    - <?php echo formatPrecio($total_ahorro); ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px; color: #2e7d32;">
                            <span>Envío</span>
                            <span>
                                <?php echo ($costo_envio == 0) ? 'Gratis' : formatPrecio($costo_envio); ?>
                            </span>
                        </div>

                        <div
                            style="border-top: 2px solid #eee; padding-top: 15px; display: flex; justify-content: space-between; font-size: 24px; font-weight: bold; margin-bottom: 25px;">
                            <span>Total</span>
                            <span>
                                <?php echo formatPrecio($total_final + $costo_envio); ?>
                            </span>
                        </div>

                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="checkout.php" class="btn-primary"
                                style="width: 100%; text-align: center; display: block;">Finalizar Compra</a>
                        <?php else: ?>
                            <a href="login.php?redirect=carrito.php" class="btn-primary"
                                style="width: 100%; text-align: center; display: block; background-color: var(--primary-dark); color: white;">Inicia
                                sesión para continuar</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Script JS -->
    <script src="js/main.js"></script>
</body>

</html>