<?php
// includes/header.php
// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Get cart count
$cart_count = 0;
if (isset($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $cart_count += $item['cantidad'];
    }
}
?>
<!-- Header -->
<header class="main-header">
    <div class="container header-content">
        <div class="logo-container">
            <a href="index.php" style="text-decoration: none;">
                <img src="img/logo/logo.png" alt="Grupo Despo" class="main-logo" style="max-height: 50px; width: auto;">
            </a>
        </div>

        <div class="header-actions">
            <!-- Search Bar -->
            <div class="search-container"
                style="display:flex; align-items:center; background:#f5f5f5; padding:5px 10px; border-radius:20px; margin-right:15px;">
                <input type="text" id="mainSearchInput" placeholder="Buscar producto..."
                    style="border:none; background:transparent; outline:none; font-size:14px; width:150px;">
                <button class="icon-btn search-btn" id="mainSearchBtn" style="background:transparent; padding:5px;"><i
                        class="fa-solid fa-magnifying-glass"></i></button>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-dropdown" style="position: relative; display: inline-block;">
                    <button class="icon-btn user-btn"
                        onclick="document.getElementById('userMenu').classList.toggle('show')">
                        <i class="fa-solid fa-user-check"></i>
                        <span style="font-size: 14px; margin-left: 5px; font-weight: 500;">
                            <?php echo explode(' ', $_SESSION['user_name'])[0]; ?>
                        </span>
                    </button>
                    <!-- Dropdown Content -->
                    <div id="userMenu" class="dropdown-content">
                        <?php if ($_SESSION['user_role'] == 'admin'): ?>
                            <a href="admin/index.php"><i class="fa-solid fa-gauge"></i> Panel Admin</a>
                        <?php endif; ?>
                        <a href="mis_pedidos.php"><i class="fa-solid fa-list"></i> Mis Pedidos</a>
                        <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="icon-btn user-btn" title="Iniciar Sesión"><i class="fa-regular fa-user"></i></a>
            <?php endif; ?>

            <button class="icon-btn cart-btn" onclick="window.location.href='carrito.php'">
                <i class="fa-solid fa-cart-shopping"></i>
                <span class="cart-badge">
                    <?php echo $cart_count; ?>
                </span>
            </button>
        </div>
    </div>
</header>

<style>
    /* Simple Dropdown CSS */
    .dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        top: 100%;
        background-color: white;
        min-width: 160px;
        box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
        z-index: 100;
        border-radius: 8px;
        overflow: hidden;
    }

    .dropdown-content a {
        color: black;
        padding: 12px 16px;
        text-decoration: none;
        display: block;
        font-size: 14px;
    }

    .dropdown-content a:hover {
        background-color: #f1f1f1;
    }

    .show {
        display: block;
    }
</style>

<script>
    // Close dropdown if clicked outside
    window.onclick = function (event) {
        if (!event.target.matches('.user-btn') && !event.target.closest('.user-btn')) {
            var dropdowns = document.getElementsByClassName("dropdown-content");
            for (var i = 0; i < dropdowns.length; i++) {
                var openDropdown = dropdowns[i];
                if (openDropdown.classList.contains('show')) {
                    openDropdown.classList.remove('show');
                }
            }
        }
    }
</script>