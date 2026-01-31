<?php
// admin/clientes.php
require_once '../config/db.php';
require_once 'includes/auth_check.php';

$active = 'clientes';
$page_title = 'Gestión de Clientes';

// Helper for filtering
// Helper for filtering
$where_clauses = ["u.role = 'cliente'"];
$params = [];
$types = "";

if (isset($_GET['q']) && !empty($_GET['q'])) {
    $search_term = "%" . $_GET['q'] . "%";
    $where_clauses[] = "(u.nombre LIKE ? OR u.apellido LIKE ? OR u.email LIKE ? OR u.sigma_id LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ssss";
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// 1. Fetch Users
$sql = "SELECT u.*, s.nombre as vendedor_nombre 
        FROM users u 
        LEFT JOIN sellers s ON u.seller_id = s.sigma_id 
        $where_sql 
        ORDER BY u.nombre ASC LIMIT ?, ?";

$stmt = $conn->prepare($sql);

// Add limit/offset to params
$query_params = $params;
$query_params[] = $limit;
$query_params[] = $offset;
$query_types = $types . "ii";

$stmt->bind_param($query_types, ...$query_params);
$stmt->execute();
$result = $stmt->get_result();

// 2. Count Total
$total_sql = "SELECT COUNT(*) as total FROM users u $where_sql";
$stmt_count = $conn->prepare($total_sql);

if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}

$stmt_count->execute();
$total_rows = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

include 'includes/header.php';
?>

<div class="form-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h4 style="margin: 0;">Listado de Clientes</h4>
        <div style="display:flex; gap:10px;">
            <a href="sync_clients.php" class="btn-secondary"
                style="font-size: 13px; padding: 8px 15px; border: 1px solid var(--primary-blue); color: var(--primary-blue); text-decoration:none; border-radius:4px;">
                <i class="fa-solid fa-rotate"></i> Sincronizar Sigma
            </a>
            <form method="GET" action="" style="display: flex; gap: 10px;">
                <input type="text" name="q" placeholder="Buscar cliente..." class="form-control"
                    style="width: 200px; padding: 8px;"
                    value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                <button type="submit" class="btn-primary" style="padding: 8px 15px;">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
                <?php if (isset($_GET['q']) && !empty($_GET['q'])): ?>
                    <a href="clientes.php" class="btn-secondary" title="Limpiar"
                        style="padding: 8px 15px; border: 1px solid #ddd; border-radius: 4px; color: #666; text-decoration: none;">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th style="width: 70px;">Sigma</th>
                    <th>Nombre / Razón Social</th>
                    <th>Email</th>
                    <th>Ubicación</th>
                    <th>Vendedor</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td>
                                <?php if (!empty($row['sigma_id'])): ?>
                                    <span
                                        style="font-family:monospace; background:#f0f0f0; padding:2px 5px; border-radius:4px; font-size:12px;">
                                        <?php echo $row['sigma_id']; ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color:#ccc; font-size:12px;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight: 500; color: #333;">
                                    <?php echo htmlspecialchars($row['nombre'] . ' ' . $row['apellido']); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td>
                                <?php
                                $loc = [];
                                if (!empty($row['ciudad']))
                                    $loc[] = $row['ciudad'];
                                if (!empty($row['provincia']))
                                    $loc[] = $row['provincia'];
                                echo implode(', ', $loc);
                                ?>
                            </td>
                            <td>
                                <?php if (!empty($row['vendedor_nombre'])): ?>
                                    <span style="color: #2980b9; font-weight:500; font-size:13px;">
                                        <i class="fa-solid fa-user-tie"></i>
                                        <?php echo htmlspecialchars($row['vendedor_nombre']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color:#999; font-size:12px; font-style:italic;">Sin Asignar</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="editar_cliente.php?id=<?php echo $row['id']; ?>" class="action-btn"
                                    style="background-color: #607D8B; text-decoration: none; padding: 5px 10px; border-radius: 4px; color: white;">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 20px; color: #666;">
                            No se encontraron clientes.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div style="margin-top: 20px; display: flex; justify-content: center; gap: 5px;">
            <?php
            $range = 2;
            $start = max(1, $page - $range);
            $end = min($total_pages, $page + $range);

            if ($start > 1) {
                echo '<a href="?page=1' . (isset($_GET['q']) ? '&q=' . urlencode($_GET['q']) : '') . '" class="btn-secondary" style="padding: 5px 10px; border: 1px solid #ddd;">1</a>';
                if ($start > 2) {
                    echo '<span style="padding: 5px;">...</span>';
                }
            }

            for ($i = $start; $i <= $end; $i++): ?>
                <a href="?page=<?php echo $i; ?><?php echo isset($_GET['q']) ? '&q=' . urlencode($_GET['q']) : ''; ?>"
                    class="btn-secondary"
                    style="padding: 5px 10px; text-decoration: none; <?php echo ($i == $page) ? 'background: var(--primary-blue); color: white; border-color: var(--primary-blue);' : 'background: white; border: 1px solid #ddd;'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor;

            if ($end < $total_pages) {
                if ($end < $total_pages - 1) {
                    echo '<span style="padding: 5px;">...</span>';
                }
                echo '<a href="?page=' . $total_pages . (isset($_GET['q']) ? '&q=' . urlencode($_GET['q']) : '') . '" class="btn-secondary" style="padding: 5px 10px; border: 1px solid #ddd;">' . $total_pages . '</a>';
            }
            ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>