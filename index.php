<?php
require_once 'config/db.php';

// Fetch Featured Products (Offers and News first, then others)
// Fetch Featured Products (Offers and News first, then others)
// Fetch Featured Products (Offers and News first, then others)
$sql = "SELECT * FROM products WHERE stock > 0 AND activo = 1 ORDER BY es_oferta DESC, es_novedad DESC, id DESC LIMIT 20";
$result = $conn->query($sql);

// Fetch Sliders - Check if table exists first to avoid fatal error on clean install
$sliders = [];
$check_table = $conn->query("SHOW TABLES LIKE 'sliders'");
if ($check_table && $check_table->num_rows > 0) {
    $sliders_res = $conn->query("SELECT * FROM sliders ORDER BY orden ASC, id DESC");
    if ($sliders_res) {
        while ($row = $sliders_res->fetch_assoc())
            $sliders[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grupo Despo - Especialistas en Distribución</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <style>
        /* Slider Dynamic Styles */
        .hero-section {
            position: relative;
            min-height: 350px;
            /* Force minimum height reduced */
            height: 350px;
            /* Set fixed height reduced */
            padding: 0 !important;
            /* Remove any padding that conflicts */
            display: flex;
            align-items: center;
        }

        .slider-wrapper {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
            /* Behind content content but above background */
        }

        .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
            background-size: cover;
            background-position: center;
        }

        .slide.active {
            opacity: 1;
            z-index: 2;
        }

        /* Gradient Overlay */
        .slide::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, rgba(30, 77, 140, 0.85) 0%, rgba(30, 77, 140, 0.4) 100%);
            z-index: 1;
        }

        /* Ensure content is above overlay */
        .container.hero-content {
            position: relative;
            z-index: 10;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }

        /* Ensure dots are visible */
        .slider-dots {
            z-index: 20;
        }
    </style>
</head>

<body>

    <!-- Dynamic Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section" id="mainHero">
        <div class="slider-wrapper">
            <?php if (!empty($sliders)): ?>
                <?php foreach ($sliders as $index => $s):
                    $activeClass = ($index === 0) ? 'active' : '';
                    $bgStyle = !empty($s['imagen_url']) ? "background-image: url('" . $s['imagen_url'] . "'); background-size: contain; background-repeat: no-repeat; background-position: center;" : "";
                    ?>
                    <div class="slide <?php echo $activeClass; ?>" style="<?php echo $bgStyle; ?>">
                        <!-- Gradient overlay if image exists to make text readable? optional. For now simplified structure -->
                        <div class="container hero-content">
                            <div class="hero-text">
                                <h2><?php echo nl2br(htmlspecialchars($s['titulo'])); ?></h2>
                                <div class="divider"></div>
                                <p><?php echo htmlspecialchars($s['descripcion']); ?></p>
                                <a href="<?php echo htmlspecialchars($s['enlace_boton']); ?>"
                                    class="btn-primary"><?php echo htmlspecialchars($s['texto_boton']); ?></a>
                            </div>
                            <!-- Image container only shows when there is NO background image -->
                            <div class="hero-image-container">
                                <?php if (empty($s['imagen_url'])): ?>
                                    <div class="bag-illustration">
                                        <i class="fa-solid fa-bag-shopping"></i>
                                        <div class="heart-icon"><i class="fa-solid fa-heart"></i></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Default Slide Fallback -->
                <div class="slide active">
                    <div class="container hero-content">
                        <div class="hero-text">
                            <h2>Tu compra del mes,<br>más inteligente.</h2>
                            <div class="divider"></div>
                            <p>Envío gratis a partir de compras superiores a $50.000.</p>
                            <a href="#destacados" class="btn-primary">Ver Ofertas ></a>
                        </div>
                        <div class="hero-image-container">
                            <div class="bag-illustration">
                                <i class="fa-solid fa-bag-shopping"></i>
                                <div class="heart-icon"><i class="fa-solid fa-heart"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="slider-dots">
            <?php
            $count = !empty($sliders) ? count($sliders) : 1;
            for ($i = 0; $i < $count; $i++):
                $active = ($i === 0) ? 'active' : '';
                ?>
                <span class="dot <?php echo $active; ?>" onclick="currentSlide(<?php echo $i; ?>)"></span>
            <?php endfor; ?>
        </div>
    </section>

    <!-- Navigation Categories -->
    <nav class="category-nav">
        <div class="container">
            <ul class="nav-pills">
                <?php
                // Fetch Dynamic Categories
                $cats = $conn->query("SELECT DISTINCT categoria FROM products WHERE stock > 0 AND activo = 1 AND categoria IS NOT NULL AND categoria != '' ORDER BY categoria ASC");
                ?>
                <li class="active"><a href="#" data-category="todas">Todo</a></li>
                <?php while ($c = $cats->fetch_assoc()): ?>
                    <li><a href="#"
                            data-category="<?php echo htmlspecialchars($c['categoria']); ?>"><?php echo htmlspecialchars($c['categoria']); ?></a>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </nav>

    <!-- Featured Products -->
    <section class="featured-products" id="destacados">
        <div class="container">
            <div class="section-title">
                <h3>Nuestros Productos</h3>
            </div>

            <!-- Product Grid -->
            <div class="product-grid">
                <?php
                // INITIAL LOAD: 20 Products
                $sqlInitial = "SELECT * FROM products WHERE stock > 0 AND activo = 1 ORDER BY es_oferta DESC, es_novedad DESC LIMIT 20";
                $resultInitial = $conn->query($sqlInitial);

                if ($resultInitial && $resultInitial->num_rows > 0):
                    while ($product = $resultInitial->fetch_assoc()):
                        // Reuse Card Logic / Include or Inline
                        $img_url = !empty($product['imagen_url']) ? $product['imagen_url'] : 'https://via.placeholder.com/200x250/e0e0e0/333333?text=Sin+Imagen';
                        ?>
                        <article class="product-card" data-id="<?php echo $product['id']; ?>">
                            <?php if ($product['es_oferta']): ?>
                                <div class="badge discount">-<?php echo intval($product['descuento_porcentaje']); ?>%</div>
                            <?php elseif ($product['es_novedad']): ?>
                                <div class="badge new">Nuevo</div>
                            <?php endif; ?>

                            <div class="product-image">
                                <img src="<?php echo $img_url; ?>" alt="<?php echo htmlspecialchars($product['nombre']); ?>">
                            </div>

                            <div class="product-info">
                                <span class="brand-tag"><?php echo htmlspecialchars($product['categoria']); ?></span>
                                <h4><?php echo htmlspecialchars($product['nombre']); ?></h4>

                                <?php if ($product['stock'] > 0 && $product['stock'] < 10): ?>
                                    <div class="badge low-stock-inline">¡Últimas Unidades!</div>
                                <?php endif; ?>

                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <div class="price-container">
                                        <?php if ($product['es_oferta'] == 1 && $product['descuento_porcentaje'] > 0):
                                            $precio_final = $product['precio_venta'] * (1 - ($product['descuento_porcentaje'] / 100));
                                            ?>
                                            <div class="price old"><?php echo formatPrecio($product['precio_venta']); ?></div>
                                            <div class="price offer"><?php echo formatPrecio($precio_final); ?></div>
                                        <?php else: ?>
                                            <div class="price"><?php echo formatPrecio($product['precio_venta']); ?></div>
                                        <?php endif; ?>
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
                                    <!-- Guest View: Login to see price -->
                                    <div class="login-price-container"
                                        style="text-align: center; margin-top: 15px; margin-bottom: 10px;">
                                        <a href="login.php" class="btn-login-price" style="
                                            background-color: #1565C0; /* Dark Blue */
                                            color: white;
                                            padding: 8px 20px;
                                            border-radius: 50px; /* Pill shape */
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
                                    </div>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align:center; grid-column:1/-1;">No hay productos disponibles.</p>
                <?php endif; ?>
            </div>

            <!-- Load More Button -->
            <div style="text-align: center; margin-top: 30px;">
                <button id="loadMoreBtn" class="btn-secondary" style="padding: 10px 30px;">Ver más productos</button>
            </div>

            <div class="legal-text">
                <p>Venta de productos Arcor sólo utilizando plataforma online.</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="main-footer">
        <p>&copy; 2026 GRUPODESPO | ENBSystem Nicolas Benitez</p>
    </footer>

    <script src="js/main.js?v=<?php echo time(); ?>"></script>
    <script>
        // Simple Slider Logic Embedded to ensure it works with dynamic PHP content
        let slideIndex = 0;
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.dot');
        let slideInterval;

        function showSlide(n) {
            if (!slides.length) return;

            // Wrap around
            if (n >= slides.length) slideIndex = 0;
            if (n < 0) slideIndex = slides.length - 1;

            // Hide all
            slides.forEach(s => s.classList.remove('active'));
            dots.forEach(d => d.classList.remove('active'));

            // Show active
            slides[slideIndex].classList.add('active');
            if (dots[slideIndex]) dots[slideIndex].classList.add('active');
        }

        function nextSlide() {
            slideIndex++;
            showSlide(slideIndex);
        }

        function currentSlide(n) {
            slideIndex = n;
            showSlide(slideIndex);
            resetTimer();
        }

        function resetTimer() {
            clearInterval(slideInterval);
            slideInterval = setInterval(nextSlide, 5000);
        }

        // Init
        if (slides.length > 0) {
            showSlide(slideIndex);
            resetTimer();
        }
    </script>
</body>

</html>