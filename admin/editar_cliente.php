<?php
// admin/editar_cliente.php
require_once '../config/db.php';
require_once 'includes/auth_check.php';

$active = 'clientes';
$page_title = 'Editar Cliente';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: clientes.php");
    exit();
}

$mensaje = '';

// Handle Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $sigma_id = $_POST['sigma_id'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $direccion = $_POST['direccion'];
    $ciudad = $_POST['ciudad'];
    $provincia = $_POST['provincia'];
    $seller_id = $_POST['seller_id'] ?? null; // New Field

    // Update Query
    $sql = "UPDATE users SET sigma_id=?, nombre=?, apellido=?, email=?, telefono=?, direccion=?, ciudad=?, provincia=?, seller_id=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssi", $sigma_id, $nombre, $apellido, $email, $telefono, $direccion, $ciudad, $provincia, $seller_id, $id);

    if ($stmt->execute()) {
        $mensaje = '<div class="alert alert-success">Cliente actualizado correctamente.</div>';
    } else {
        $mensaje = '<div class="alert alert-danger">Error al actualizar: ' . $conn->error . '</div>';
    }
}

// Fetch Client
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$c = $stmt->get_result()->fetch_assoc();

if (!$c) {
    echo "Cliente no encontrado";
    exit();
}

// Fetch Sellers for Dropdown
$sellers = $conn->query("SELECT * FROM sellers WHERE activo = 1 ORDER BY nombre ASC");


include 'includes/header.php';
?>

<div class="admin-header">
    <h2>Editar Cliente</h2>
    <a href="clientes.php" class="btn-secondary" style="font-size: 14px; padding: 8px 15px;">
        <i class="fa-solid fa-arrow-left"></i> Volver
    </a>
</div>

<div class="admin-content">
    <?php echo $mensaje; ?>

    <div class="form-card" style="max-width: 800px; margin: 0 auto;">
        <h3
            style="margin-bottom: 20px; color: var(--primary-blue); border-bottom: 2px solid #eee; padding-bottom: 10px;">
            <i class="fa-solid fa-user-pen"></i>
            <?php echo htmlspecialchars($c['nombre'] . ' ' . $c['apellido']); ?>
        </h3>

        <form method="POST" action="">
            <input type="hidden" name="action" value="edit">

            <!-- Identifiers -->
            <div class="form-row">
                <div class="form-col" style="flex: 1;">
                    <label class="form-label">ID Sistema</label>
                    <input type="text" class="form-control" value="<?php echo $c['id']; ?>" readonly disabled
                        style="background: #f5f5f5;">
                </div>
                <div class="form-col" style="flex: 1;">
                    <label class="form-label">ID Sigma</label>
                    <input type="text" name="sigma_id" class="form-control"
                        value="<?php echo htmlspecialchars($c['sigma_id']); ?>"
                        style="background-color: #f0f0f0; font-family:monospace;">
                </div>
                <div class="form-col" style="flex: 2;">
                    <label class="form-label">Vendedor Asignado</label>
                    <select name="seller_id" class="form-control">
                        <option value="">-- Sin Vendedor --</option>
                        <?php while($s = $sellers->fetch_assoc()): ?>
                            <option value="<?php echo $s['sigma_id']; ?>" <?php echo ($c['seller_id'] == $s['sigma_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['nombre']); ?> (<?php echo $s['sigma_id']; ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <!-- Contact Info -->
            <h5 style="color: #666; margin: 20px 0 15px; font-weight: 600;">Datos Personales</h5>
            <div class="form-row">
                <div class="form-col">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="nombre" class="form-control"
                        value="<?php echo htmlspecialchars($c['nombre']); ?>" required>
                </div>
                <div class="form-col">
                    <label class="form-label">Apellido</label>
                    <input type="text" name="apellido" class="form-control"
                        value="<?php echo htmlspecialchars($c['apellido']); ?>">
                </div>
            </div>

            <div class="form-row" style="margin-top: 15px;">
                <div class="form-col">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control"
                        value="<?php echo htmlspecialchars($c['email']); ?>" required>
                </div>
                <div class="form-col">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-control"
                        value="<?php echo htmlspecialchars($c['telefono']); ?>">
                </div>
            </div>

            <!-- Address -->
            <h5 style="color: #666; margin: 20px 0 15px; font-weight: 600;">Ubicación / Envío</h5>
            <div class="form-row">
                <div class="form-col" style="flex: 2;">
                    <label class="form-label">Dirección</label>
                    <input type="text" name="direccion" class="form-control"
                        value="<?php echo htmlspecialchars($c['direccion']); ?>">
                </div>
            </div>
            <div class="form-row" style="margin-top: 15px;">
                <div class="form-col">
                    <label class="form-label">Ciudad</label>
                    <input type="text" name="ciudad" class="form-control"
                        value="<?php echo htmlspecialchars($c['ciudad']); ?>">
                </div>
                <div class="form-col">
                    <label class="form-label">Provincia</label>
                    <input type="text" name="provincia" class="form-control"
                        value="<?php echo htmlspecialchars($c['provincia']); ?>">
                </div>
            </div>

            <div style="margin-top: 30px; text-align: right; border-top: 1px solid #eee; padding-top: 20px;">
                <a href="clientes.php" class="btn-secondary"
                    style="margin-right: 10px; text-decoration: none; padding: 10px 20px; font-size: 14px; background: #eee; color: #333; border: none;">Cancelar</a>
                <button type="submit" class="btn-primary" style="padding: 10px 30px; font-size: 16px;">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>