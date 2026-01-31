<!-- admin/includes/header.php -->
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Grupo Despo</title>
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CSS -->
    <link rel="stylesheet" href="../css/style.css"> <!-- Base styles -->
    <link rel="stylesheet" href="../css/admin.css?v=<?php echo time(); ?>"> <!-- Admin specific -->
</head>

<body class="admin-body">

    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header" style="text-align: center; padding: 20px 0;">
            <img src="../img/logo/logo.png" alt="Grupo Despo" style="max-width: 40%; height: auto;">
        </div>
        <nav class="sidebar-menu">
            <a href="index.php" class="menu-item <?php echo ($active == 'dashboard') ? 'active' : ''; ?>">
                <i class="fa-solid fa-gauge"></i> Dashboard
            </a>
            <a href="productos.php" class="menu-item <?php echo ($active == 'productos') ? 'active' : ''; ?>">
                <i class="fa-solid fa-box"></i> Productos
            </a>
            <a href="sync_products.php" class="menu-item <?php echo ($active == 'sync') ? 'active' : ''; ?>">
                <i class="fa-solid fa-rotate"></i> Sincronizar Sigma
            </a>
            <a href="sliders.php" class="menu-item <?php echo ($active == 'sliders') ? 'active' : ''; ?>">
                <i class="fa-solid fa-images"></i> Sliders
            </a>
            <a href="pedidos.php" class="menu-item <?php echo ($active == 'pedidos') ? 'active' : ''; ?>">
                <i class="fa-solid fa-cart-shopping"></i> Pedidos
            </a>
            <a href="clientes.php" class="menu-item <?php echo ($active == 'clientes') ? 'active' : ''; ?>">
                <i class="fa-solid fa-users"></i> Clientes
            </a>
            <a href="usuarios_admin.php" class="menu-item <?php echo ($active == 'usuarios_admin') ? 'active' : ''; ?>">
                <i class="fa-solid fa-user-shield"></i> Administradores
            </a>
            <a href="configuracion.php" class="menu-item <?php echo ($active == 'configuracion') ? 'active' : ''; ?>">
                <i class="fa-solid fa-gears"></i> Configuración
            </a>
            <a href="../index.php" class="menu-item" target="_blank">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Ver Tienda
            </a>
            <a href="logout.php" class="menu-item">
                <i class="fa-solid fa-right-from-bracket"></i> Salir
            </a>
        </nav>
    </aside>

    <!-- Main Content Wrapper -->
    <main class="admin-main">
        <header class="admin-header">
            <h4>
                <?php echo $page_title ?? 'Dashboard'; ?>
            </h4>
            <div class="user-info">
                <span><i class="fa-solid fa-circle-user"></i> Admin</span>
            </div>
        </header>

        <div class="admin-content">