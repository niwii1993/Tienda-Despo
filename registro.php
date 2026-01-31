<?php
// register.php
require_once 'config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $telefono = $_POST['telefono'];
    $direccion = $_POST['direccion'];
    $ciudad = $_POST['ciudad'];
    $provincia = $_POST['provincia'];

    // Check if email exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $error = "El correo electrónico ya está registrado.";
    } else {
        $sigma_id = !empty($_POST['sigma_id']) ? $_POST['sigma_id'] : null;
        $claimed = false;

        // Account Claiming Logic
        if ($sigma_id) {
            $checkSigma = $conn->prepare("SELECT id FROM users WHERE sigma_id = ?");
            $checkSigma->bind_param("s", $sigma_id);
            $checkSigma->execute();
            $resSigma = $checkSigma->get_result();

            if ($resSigma->num_rows > 0) {
                // User exists (imported), update credentials
                $user = $resSigma->fetch_assoc();
                $updateUser = $conn->prepare("UPDATE users SET nombre=?, apellido=?, email=?, password=?, telefono=?, direccion=?, ciudad=?, provincia=?, role='cliente' WHERE id=?");
                $updateUser->bind_param("ssssssssi", $nombre, $apellido, $email, $password, $telefono, $direccion, $ciudad, $provincia, $user['id']);

                if ($updateUser->execute()) {
                    $success = "¡Cuenta vinculada con éxito! Tu historial ha sido recuperado. Ahora puedes <a href='login.php'>iniciar sesión</a>.";
                    $claimed = true;
                } else {
                    $error = "Error al vincular cuenta: " . $conn->error;
                }
            }
        }

        // Standard Registration (if not claimed)
        if (!$claimed && empty($error)) {
            $stmt = $conn->prepare("INSERT INTO users (nombre, apellido, email, password, telefono, direccion, ciudad, provincia, role, sigma_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'cliente', ?)");
            $stmt->bind_param("sssssssss", $nombre, $apellido, $email, $password, $telefono, $direccion, $ciudad, $provincia, $sigma_id);

            if ($stmt->execute()) {
                $success = "Registro exitoso. Ahora puedes <a href='login.php'>iniciar sesión</a>.";
            } else {
                $error = "Error al registrarse: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Grupo Despo</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .auth-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: inherit;
        }

        .btn-submit {
            width: 100%;
            background: var(--primary-blue);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }

        .alert {
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="auth-container">
            <h2 style="text-align: center; margin-bottom: 20px; color: var(--primary-blue);">Crear Cuenta</h2>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex:1;">
                        <label>Nombre</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Apellido</label>
                        <input type="text" name="apellido" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" class="form-control" required>
                </div>

                <!-- Optional Sigma ID -->
                <div class="form-group"
                    style="background: #f8f9fa; padding: 10px; border-radius: 8px; border: 1px dashed #ced4da;">
                    <label style="color: #666; font-size: 14px;"><strong>Nº de Cliente (Opcional)</strong></label>
                    <div style="font-size: 12px; color: #888; margin-bottom: 5px;">
                        Si ya eres cliente, ingresa tu número para vincular tu cuenta histórica.
                    </div>
                    <input type="text" name="sigma_id" class="form-control" placeholder="Ej: 00123">
                </div>

                <h4 style="margin: 15px 0 10px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Dirección de Envío
                </h4>

                <div class="form-group">
                    <label>Dirección (Calle y Altura)</label>
                    <input type="text" name="direccion" class="form-control" required>
                </div>

                <div style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex:1;">
                        <label>Ciudad</label>
                        <input type="text" name="ciudad" class="form-control" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Provincia</label>
                        <input type="text" name="provincia" class="form-control" required>
                    </div>
                </div>

                <button type="submit" class="btn-submit">Registrarse</button>
                <div style="text-align: center; margin-top: 15px; font-size: 14px;">
                    ¿Ya tienes cuenta? <a href="login.php" style="color: var(--primary-blue); font-weight: 500;">Iniciar
                        Sesión</a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>