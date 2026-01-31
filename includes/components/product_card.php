<?php
function renderProductCard($product, $show_actions = true)
{
    // Calcular precio
    $precio_original = $product['precio_venta'];
    $precio_final = $precio_original;
    $precio_display = '';

    if ($product['es_oferta'] == 1 && $product['descuento_porcentaje'] > 0) {
        $precio_final = $precio_original * (1 - ($product['descuento_porcentaje'] / 100));
        $precio_display = '<div class="price old">' . formatPrecio($precio_original) . '</div>
                           <div class="price offer">' . formatPrecio($precio_final) . '</div>';
    } else {
        $precio_display = '<div class="price">' . formatPrecio($precio_original) . '</div>';
    }

    // Badges
    $badges = '';
    if ($product['es_oferta']) {
        $badges = '<div class="badge discount">-' . intval($product['descuento_porcentaje']) . '%</div>';
    } elseif ($product['es_novedad']) {
        $badges = '<div class="badge new">Nuevo</div>';
    }

    // Low stock
    $lowStockHTML = '';
    if ($product['stock'] > 0 && $product['stock'] < 10) {
        $lowStockHTML = '<div class="badge low-stock-inline">¡Últimas Unidades!</div>';
    }

    // Imagen
    $img_url = !empty($product['imagen_url'])
        ? htmlspecialchars($product['imagen_url'])
        : 'https://via.placeholder.com/200x250/e0e0e0/333333?text=Sin+Imagen';

    // Render
    ?>
    <article class="product-card" data-id="<?php echo $product['id']; ?>">
        <?php echo $badges; ?>
        <div class="product-image">
            <img src="<?php echo $img_url; ?>" alt="<?php echo htmlspecialchars($product['nombre']); ?>">
        </div>
        <div class="product-info">
            <span class="brand-tag">
                <?php echo htmlspecialchars($product['categoria']); ?>
            </span>
            <h4>
                <?php echo htmlspecialchars($product['nombre']); ?>
            </h4>
            <?php echo $lowStockHTML; ?>

            <?php if ($show_actions): ?>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="price-container">
                        <?php echo $precio_display; ?>
                    </div>
                    <div class="card-actions">
                        <button class="btn-add-initial" onclick="initAddToCart(this)">
                            <i class="fa-solid fa-cart-desktop"></i> Agregar
                        </button>
                        <div class="quantity-control" style="display: none;">
                            <button type="button" class="qty-btn minus" onclick="updateQty(this, -1)">-</button>
                            <input type="number" class="qty-input" value="1" readonly>
                            <button type="button" class="qty-btn plus" onclick="updateQty(this, 1)">+</button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="login-price-container" style="text-align: center; margin-top: 15px; margin-bottom: 10px;">
                        <a href="login.php" class="btn-login-price"
                            style="background-color: #1565C0; color: white; padding: 8px 20px; border-radius: 50px; text-decoration: none; font-size: 14px; font-weight: 700; display: inline-block; text-align: center; box-shadow: 0 4px 10px rgba(21, 101, 192, 0.3); transition: all 0.3s ease; line-height: 1.2;">
                            INICIA SESIÓN<br>
                            <span style="font-size: 12px; font-weight: 500;">para ver precio</span>
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </article>
    <?php
}
?>