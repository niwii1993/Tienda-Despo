<?php
// admin/sliders.php
require_once '../config/db.php';
require_once 'includes/auth_check.php';

$active = 'sliders';
$page_title = 'Gestión de Sliders';

// Initialization
$edit_mode = false;
$id_edit = 0;
// Default values
$titulo = '';
$descripcion = '';
$texto_boton = 'Ver Ofertas >';
$enlace_boton = '#destacados';
$orden = 0;
$imagen_actual = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Optional: Delete image file from server if needed
    $conn->query("DELETE FROM sliders WHERE id = $id");
    header("Location: sliders.php?msg=deleted");
    exit();
}

// Handle Edit Request (Load Data)
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id_edit = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM sliders WHERE id = $id_edit");
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $titulo = $row['titulo'];
        $descripcion = $row['descripcion'];
        $texto_boton = $row['texto_boton'];
        $enlace_boton = $row['enlace_boton'];
        $orden = $row['orden'];
        $imagen_actual = $row['imagen_url'];
    }
}

// Handle Form Submit (Add or Update)
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo_post = $_POST['titulo'];
    $descripcion_post = $_POST['descripcion'];
    $texto_boton_post = $_POST['texto_boton'];
    $enlace_boton_post = $_POST['enlace_boton'];
    $orden_post = intval($_POST['orden']);
    $id_post = isset($_POST['id']) ? intval($_POST['id']) : 0;

    // Image Upload
    $imagen_url = isset($_POST['imagen_actual']) ? $_POST['imagen_actual'] : '';

    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $target_dir = "../img/sliders/";
        if (!file_exists($target_dir))
            mkdir($target_dir, 0777, true);

        $file_extension = strtolower(pathinfo($_FILES["imagen"]["name"], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($file_extension, $allowed)) {
            $new_filename = 'slide_' . time() . '.' . $file_extension;
            if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $target_dir . $new_filename)) {
                $imagen_url = "img/sliders/" . $new_filename;
            }
        }
    }

    if ($id_post > 0) {
        // UPDATE
        $stmt = $conn->prepare("UPDATE sliders SET titulo=?, descripcion=?, texto_boton=?, enlace_boton=?, imagen_url=?, orden=? WHERE id=?");
        $stmt->bind_param("sssssii", $titulo_post, $descripcion_post, $texto_boton_post, $enlace_boton_post, $imagen_url, $orden_post, $id_post);
        if ($stmt->execute()) {
            $mensaje = '<div class="status-badge status-active" style="display:inline-block; margin-bottom:10px;">Slide actualizado correctamente</div>';
            // Refresh variables
            $titulo = $titulo_post;
            $descripcion = $descripcion_post;
            $texto_boton = $texto_boton_post;
            $enlace_boton = $enlace_boton_post;
            $orden = $orden_post;
            $imagen_actual = $imagen_url;
        } else {
            $mensaje = '<div class="status-badge status-inactive">Error actualizando: ' . $conn->error . '</div>';
        }
    } else {
        // INSERT
        $stmt = $conn->prepare("INSERT INTO sliders (titulo, descripcion, texto_boton, enlace_boton, imagen_url, orden) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $titulo_post, $descripcion_post, $texto_boton_post, $enlace_boton_post, $imagen_url, $orden_post);
        if ($stmt->execute()) {
            $mensaje = '<div class="status-badge status-active" style="display:inline-block; margin-bottom:10px;">Slide creado correctamente</div>';
            // Reset form
            $titulo = '';
            $descripcion = '';
            $texto_boton = 'Ver Ofertas >';
            $enlace_boton = '#destacados';
            $orden = 0;
            $imagen_actual = '';
        } else {
            $mensaje = '<div class="status-badge status-inactive">Error creando: ' . $conn->error . '</div>';
        }
    }
}

// Fetch Slides for List
$sliders = $conn->query("SELECT * FROM sliders ORDER BY orden ASC, id DESC");

include 'includes/header.php';
?>

<div class="form-card" style="margin-bottom: 30px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h4><?php echo $edit_mode ? 'Editar Slide' : 'Agregar Nuevo Slide'; ?></h4>
        <?php if ($edit_mode): ?>
            <a href="sliders.php" class="btn-secondary"
                style="padding: 5px 10px; border: 1px solid #ccc; border-radius: 4px; text-decoration: none; font-size: 12px;">Cancelar
                Edición</a>
        <?php endif; ?>
    </div>

    <?php echo $mensaje; ?>

    <form method="POST" action="sliders.php<?php echo $edit_mode ? '?edit=' . $id_edit : ''; ?>"
        enctype="multipart/form-data">
        <?php if ($edit_mode): ?>
            <input type="hidden" name="id" value="<?php echo $id_edit; ?>">
            <input type="hidden" name="imagen_actual" value="<?php echo htmlspecialchars($imagen_actual); ?>">
        <?php endif; ?>

        <div class="form-row">
            <div class="form-col">
                <label class="form-label">Título Principal</label>
                <input type="text" name="titulo" class="form-control" placeholder="Ej: Tu compra del mes..."
                    value="<?php echo htmlspecialchars($titulo); ?>" required>
            </div>
            <div class="form-col">
                <label class="form-label">Descripción / Subtítulo</label>
                <input type="text" name="descripcion" class="form-control" placeholder="Ej: Envío gratis a partir de..."
                    value="<?php echo htmlspecialchars($descripcion); ?>">
            </div>
        </div>

        <div class="form-row" style="margin-top: 15px;">
            <div class="form-col">
                <label class="form-label">Texto Botón</label>
                <input type="text" name="texto_boton" class="form-control"
                    value="<?php echo htmlspecialchars($texto_boton); ?>">
            </div>
            <div class="form-col">
                <label class="form-label">Enlace Botón (URL)</label>
                <input type="text" name="enlace_boton" class="form-control"
                    value="<?php echo htmlspecialchars($enlace_boton); ?>">
            </div>
        </div>

        <div class="form-row" style="margin-top: 15px;">
            <!-- Orden -->
            <div class="form-col">
                <label class="form-label">Orden (1, 2, 3...)</label>
                <input type="number" name="orden" class="form-control" value="<?php echo $orden; ?>">
            </div>
            <!-- Image Input -->
            <div class="form-col">
                <label class="form-label">Imagen de Fondo</label>
                <?php if ($edit_mode && !empty($imagen_actual)): ?>
                    <div style="margin-bottom: 5px;">
                        <img src="../<?php echo $imagen_actual; ?>" style="height: 40px; border-radius: 4px;">
                        <small style="color: #666; vertical-align: top;">(Imagen actual)</small>
                    </div>
                <?php endif; ?>
                <input type="file" name="imagen" class="form-control" accept="image/*">
                <small style="color:#666;">Dejar vacío para mantener la actual. Se recomienda imagen ancha de alta
                    resolución.</small>
            </div>
        </div>

        <div style="margin-top: 20px; text-align: right;">
            <button type="submit"
                class="btn-primary"><?php echo $edit_mode ? 'Actualizar Slide' : 'Guardar Slide'; ?></button>
        </div>
    </form>
</div>

<!-- Slides List -->
<div class="form-card">
    <h4>Slides Activos</h4>
    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Orden</th>
                    <th>Img</th>
                    <th>Título</th>
                    <th>Botón</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($s = $sliders->fetch_assoc()): ?>
                    <tr <?php echo ($edit_mode && $s['id'] == $id_edit) ? 'style="background-color: #e3f2fd;"' : ''; ?>>
                        <td><?php echo $s['orden']; ?></td>
                        <td>
                            <?php if (!empty($s['imagen_url'])): ?>
                                <img src="../<?php echo $s['imagen_url']; ?>"
                                    style="width: 80px; height: 40px; object-fit: cover; border-radius: 4px;">
                            <?php else: ?>
                                <div
                                    style="width: 80px; height: 40px; background: linear-gradient(to right, #1E4D8C, #4A90E2); border-radius: 4px;">
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($s['titulo']); ?></strong><br>
                            <small><?php echo htmlspecialchars($s['descripcion']); ?></small>
                        </td>
                        <td><a href="<?php echo htmlspecialchars($s['enlace_boton']); ?>"
                                target="_blank"><?php echo htmlspecialchars($s['texto_boton']); ?></a></td>
                        <td>
                            <a href="sliders.php?edit=<?php echo $s['id']; ?>" class="action-btn btn-edit"><i
                                    class="fa-solid fa-pen"></i></a>
                            <a href="sliders.php?delete=<?php echo $s['id']; ?>" class="action-btn btn-delete"
                                onclick="return confirm('Eliminar?');"><i class="fa-solid fa-trash"></i></a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>