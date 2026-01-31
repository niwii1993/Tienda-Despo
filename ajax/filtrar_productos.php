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

// Prepared Statement Variables
$params = [];
$types = "";

// Base SQL
$sql = "SELECT * FROM products WHERE stock > 0 AND activo = 1";

// Filter Logic: Category
if ($categoria !== 'todas') {
    $sql .= " AND categoria = ?";
    $params[] = $categoria;
    $types .= "s";
}

// Filter Logic: Search
if (!empty($q)) {
    $search_term = "%" . $q . "%";
    $sql .= " AND (nombre LIKE ? OR codigo_barras LIKE ? OR categoria LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

// Order Logic
$sql .= " ORDER BY es_oferta DESC, es_novedad DESC, id DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $limit;
$types .= "ii";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    require_once '../includes/components/product_card.php';
    while ($product = $result->fetch_assoc()) {
        renderProductCard($product);
    }
} else {
    echo '<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #666;">
            <i class="fa-regular fa-face-frown-open" style="font-size: 40px; margin-bottom: 20px;"></i>
            <p>No se encontraron productos en esta categoría.</p>
          </div>';
}
?>