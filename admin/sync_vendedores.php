<?php
// admin/sync_vendedores.php
require_once '../config/db.php';
require_once '../config/apisigma.php';
require_once 'includes/auth_check.php';

$active = 'configuracion'; // Or 'vendedores'
$page_title = 'Sincronizar Vendedores';

// Logic to Sync
$message = '';
if (isset($_POST['sync'])) {

    // 1. Fetch from Sigma
    $endpoint = 'ExportVendedores';
    $result = callSigmaApi($endpoint);

    if ($result['http_code'] == 200 && is_array($result['response'])) {
        $vendedores = $result['response'];
        $count_new = 0;
        $count_updated = 0;

        $stmtCheck = $conn->prepare("SELECT id FROM sellers WHERE sigma_id = ?");
        $stmtInsert = $conn->prepare("INSERT INTO sellers (sigma_id, nombre, sucursal, email, activo) VALUES (?, ?, ?, ?, 1)");
        $stmtUpdate = $conn->prepare("UPDATE sellers SET nombre=?, sucursal=?, email=? WHERE sigma_id=?");

        foreach ($vendedores as $v) {
            $sid = $v['id'];
            $nombre = $v['nombre'];
            $sucursal = $v['sucursal'] ?? '';
            $email = $v['email'] ?? '';

            // Check if exists
            $stmtCheck->bind_param("s", $sid);
            $stmtCheck->execute();
            $stmtCheck->store_result();

            if ($stmtCheck->num_rows > 0) {
                // Update
                $stmtUpdate->bind_param("ssss", $nombre, $sucursal, $email, $sid);
                $stmtUpdate->execute();
                $count_updated++;
            } else {
                // Insert
                $stmtInsert->bind_param("ssss", $sid, $nombre, $sucursal, $email);
                $stmtInsert->execute();
                $count_new++;
            }
        }
        $message = "<div class='alert alert-success'>Sincronización completada: $count_new nuevos, $count_updated actualizados.</div>";
    } else {
        $message = "<div class='alert alert-danger'>Error al conectar con Sigma API. Code: " . $result['http_code'] . "</div>";
    }
}

// Fetch Local Sellers
$sellers = $conn->query("SELECT * FROM sellers ORDER BY nombre ASC");

include 'includes/header.php';
?>

<div class="admin-header">
    <h2>Vendedores</h2>
    <form method="POST" style="display:inline;">
        <button type="submit" name="sync" class="btn-primary">
            <i class="fa-solid fa-sync"></i> Sincronizar desde Sigma
        </button>
    </form>
</div>

<div class="admin-content">
    <?php echo $message; ?>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID Sigma</th>
                    <th>Nombre</th>
                    <th>Sucursal</th>
                    <th>Email</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($sellers->num_rows > 0): ?>
                    <?php while ($row = $sellers->fetch_assoc()): ?>
                        <tr>
                            <td><span style="font-family:monospace; background:#eee; padding:2px 5px; border-radius:3px;">
                                    <?php echo $row['sigma_id']; ?>
                                </span></td>
                            <td><strong>
                                    <?php echo $row['nombre']; ?>
                                </strong></td>
                            <td>
                                <?php echo $row['sucursal']; ?>
                            </td>
                            <td>
                                <?php echo $row['email']; ?>
                            </td>
                            <td>
                                <?php if ($row['activo']): ?>
                                    <span style="color:green; font-size:12px;">● Activo</span>
                                <?php else: ?>
                                    <span style="color:red; font-size:12px;">● Inactivo</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 20px; color: #7f8c8d;">
                            No hay vendedores sincronizados.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>