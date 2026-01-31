<?php
// ajax/filtrar_productos.php
// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/db.php';

$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : 'todas';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = 20;

// Base SQL
$sql = "SELECT * FROM products WHERE stock > 0 AND activo = 1";

// Filter Logic: Category
if ($categoria !== 'todas') {
    $categoria = $conn->real_escape_string($categoria);
    // Use LIKE for partial matches if categories are messy, or = if strict
    $sql .= " AND categoria = '$categoria'";
}

// Filter Logic: Search
if (!empty($q)) {
    $q = $conn->real_escape_string($q);
    $sql .= " AND (nombre LIKE '%$q%' OR codigo_barras LIKE '%$q%' OR categoria LIKE '%$q%')";
}

// Order Logic (Offers first, then new, then ID for stability)
$sql .= " ORDER BY es_oferta DESC, es_novedad DESC, id DESC LIMIT $offset, $limit";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($product = $result->fetch_assoc()) {
        // Calculate price
        $precio_display = '';
        if ($product['es_oferta'] == 1 && $product['descuento_porcentaje'] > 0) {
            $precio_final = $product['precio_venta'] * (1 - ($product['descuento_porcentaje'] / 100));
            $precio_display = '<div class="price old">' . formatPrecio($product['precio_venta']) . '</div>
                               <div class="price offer">' . formatPrecio($precio_final) . '</div>';
        } else {
            $precio_display = '<div class="price">' . formatPrecio($product['precio_venta']) . '</div>';
        }

        // Badges
        $badges = '';
        if ($product['es_oferta']) {
            $badges = '<div class="badge discount">-' . intval($product['descuento_porcentaje']) . '%</div>';
        } elseif ($product['es_novedad']) {
            $badges = '<div class="badge new">Nuevo</div>';
        }

        // Low Stock Logic (Inline)
        $lowStockHTML = '';
        if ($product['stock'] > 0 && $product['stock'] < 10) {
            $lowStockHTML = '<div class="badge low-stock-inline">¡Últimas Unidades!</div>';
        }

        // Image
        $img_url = !empty($product['imagen_url']) ? $product['imagen_url'] : 'https://via.placeholder.com/200x250/e0e0e0/333333?text=Sin+Imagen';

        // Render Card HTML
        echo '
        <article class="product-card" data-id="' . $product['id'] . '">
            ' . $badges . '
            <div class="product-image">
                <img src="' . $img_url . '" alt="' . htmlspecialchars($product['nombre']) . '">
            </div>
            <div class="product-info">
                <span class="brand-tag">' . htmlspecialchars($product['categoria']) . '</span>
                <h4>' . htmlspecialchars($product['nombre']) . '</h4>
                ' . $lowStockHTML;

        // Check Login for Price/Actions
        if (isset($_SESSION['user_id'])) {
            echo '
                <div class="price-container">
                    ' . $precio_display . '
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
                </div>';
        } else {
            // Guest View
            echo '
                <div class="login-price-container" style="text-align: center; margin-top: 15px; margin-bottom: 10px;">
                    <a href="login.php" class="btn-login-price" style="
                        background-color: #1565C0; /* Dark Blue */
                        color: white;
                        padding: 8px 20px;
                        border-radius: 50px;
                        text-decoration: none;
                        font-size: 14px;
                        font-weight: 700;
                        display: inline-block;
                        text-align: center;
                        box-shadow: 0 4px 10px rgba(21, 101, 192, 0.3);
                        transition: all 0.3s ease;
                        line-height: 1.2;
                    ">
                        INICIA SESIÓN<br>
                        <span style="font-size: 12px; font-weight: 500;">para ver precio</span>
                    </a>
                </div>';
        }

        echo '
            </div>
        </article>';
    }
} else {
    echo '<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #666;">
            <i class="fa-regular fa-face-frown-open" style="font-size: 40px; margin-bottom: 20px;"></i>
            <p>No se encontraron productos en esta categoría.</p>
          </div>';
}
?>