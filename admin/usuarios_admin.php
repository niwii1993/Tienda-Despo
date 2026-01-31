<?php
// admin/usuarios_admin.php
require_once '../config/db.php';
require_once 'includes/auth_check.php';

$active = 'usuarios_admin';
$page_title = 'Gestión de Administradores';

$mensaje = '';

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action == 'add') {
            $nombre = $_POST['nombre'];
            $apellido = $_POST['apellido'];
            $email = $_POST['email'];
            $password = $_POST['password'];

            // Check if email exists
            $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check->bind_param("s", $email);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $mensaje = '<div class="alert alert-danger">El email ya está registrado.</div>';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $role = 'admin';
                $stmt = $conn->prepare("INSERT INTO users (nombre, apellido, email, password, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $nombre, $apellido, $email, $hash, $role);
                if ($stmt->execute()) {
                    $mensaje = '<div class="alert alert-success">Administrador creado correctamente.</div>';
                } else {
                    $mensaje = '<div class="alert alert-danger">Error: ' . $conn->error . '</div>';
                }
            }
        } elseif ($action == 'delete') {
            $id = intval($_POST['id']);
            // Prevent deleting self
            if ($id == $_SESSION['user_id']) {
                $mensaje = '<div class="alert alert-danger">No puedes eliminar tu propia cuenta.</div>';
            } else {
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'admin'");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $mensaje = '<div class="alert alert-success">Administrador eliminado correctamente.</div>';
                } else {
                    $mensaje = '<div class="alert alert-danger">Error al eliminar.</div>';
                }
            }
        } elseif ($action == 'edit') {
            $id = intval($_POST['id']);
            $nombre = $_POST['nombre'];
            $apellido = $_POST['apellido'];
            $email = $_POST['email'];
            $password = $_POST['password'];

            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET nombre=?, apellido=?, email=?, password=? WHERE id=? AND role='admin'");
                $stmt->bind_param("ssssi", $nombre, $apellido, $email, $hash, $id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET nombre=?, apellido=?, email=? WHERE id=? AND role='admin'");
                $stmt->bind_param("sssi", $nombre, $apellido, $email, $id);
            }

            if ($stmt->execute()) {
                $mensaje = '<div class="alert alert-success">Administrador actualizado correctamente.</div>';
            } else {
                $mensaje = '<div class="alert alert-danger">Error al actualizar.</div>';
            }
        }
    }
}

// Fetch Admins
$admins = $conn->query("SELECT * FROM users WHERE role = 'admin' ORDER BY nombre ASC");

include 'includes/header.php';
?>

<div class="admin-content">
    <?php echo $mensaje; ?>

    <div class="form-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h4 style="margin: 0;">Listado de Administradores</h4>
            <button onclick="openModal('addModal')" class="btn-primary">
                <i class="fa-solid fa-user-plus"></i> Nuevo Admin
            </button>
        </div>

        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Email</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $admins->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php echo $row['id']; ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($row['nombre']); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($row['apellido']); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($row['email']); ?>
                            </td>
                            <td>
                                <div class="actions-cell">
                                    <button class="action-btn btn-edit" title="Editar"
                                        onclick='openEditModal(<?php echo json_encode($row); ?>)'>
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" onsubmit="return confirm('¿Eliminar este administrador?');"
                                            style="display:inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" class="action-btn btn-delete" title="Eliminar">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Add -->
<div id="addModal" class="modal"
    style="display:none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div class="form-card" style="width: 400px; margin: 0;">
        <h4 style="margin-bottom: 20px;">Nuevo Administrador</h4>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-col">
                <label>Nombre</label>
                <input type="text" name="nombre" class="form-control" required>
            </div>
            <div class="form-col">
                <label>Apellido</label>
                <input type="text" name="apellido" class="form-control" required>
            </div>
            <div class="form-col">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-col">
                <label>Contraseña</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div style="text-align: right; margin-top: 15px;">
                <button type="button" onclick="closeModal('addModal')" class="btn-secondary">Cancelar</button>
                <button type="submit" class="btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit -->
<div id="editModal" class="modal"
    style="display:none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div class="form-card" style="width: 400px; margin: 0;">
        <h4 style="margin-bottom: 20px;">Editar Administrador</h4>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-col">
                <label>Nombre</label>
                <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
            </div>
            <div class="form-col">
                <label>Apellido</label>
                <input type="text" name="apellido" id="edit_apellido" class="form-control" required>
            </div>
            <div class="form-col">
                <label>Email</label>
                <input type="email" name="email" id="edit_email" class="form-control" required>
            </div>
            <div class="form-col">
                <label>Nueva Contraseña (Dejar en blanco para mantener)</label>
                <input type="password" name="password" class="form-control">
            </div>
            <div style="text-align: right; margin-top: 15px;">
                <button type="button" onclick="closeModal('editModal')" class="btn-secondary">Cancelar</button>
                <button type="submit" class="btn-primary">Actualizar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(id) {
        document.getElementById(id).style.display = 'flex';
    }
    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }
    function openEditModal(user) {
        document.getElementById('edit_id').value = user.id;
        document.getElementById('edit_nombre').value = user.nombre;
        document.getElementById('edit_apellido').value = user.apellido;
        document.getElementById('edit_email').value = user.email;
        openModal('editModal');
    }
    // Close on click outside
    window.addEventListener('click', function (event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    });
</script>

<?php include 'includes/footer.php'; ?>