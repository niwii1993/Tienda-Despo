<?php
// setup_admin_secret.php
require_once 'config/db.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $nombre = "Super";
    $apellido = "Admin";
    $role = 'admin';

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check if duplicate
    $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
    if ($check->num_rows > 0) {
        // Update existing to admin
        $stmt = $conn->prepare("UPDATE users SET password = ?, role = 'admin' WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $email);
        $stmt->execute();
        $mensaje = "<div style='color: green; padding: 10px; background: #e8f5e9; border-radius: 5px;'>Usuario existente actualizado a ADMIN correctamente.</div>";
    } else {
        // Create new
        $stmt = $conn->prepare("INSERT INTO users (nombre, apellido, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nombre, $apellido, $email, $hashed_password, $role);

        if ($stmt->execute()) {
            $mensaje = "<div style='color: green; padding: 10px; background: #e8f5e9; border-radius: 5px;'>ADMIN creado correctamente. Ya puedes ir al Login.</div>";
        } else {
            $mensaje = "<div style='color: red;'>Error: " . $conn->error . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Configuración Secreta Admin</title>
    <style>
        body {
            font-family: sans-serif;
            background: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: white;
        }

        .card {
            background: #444;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 400px;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: none;
        }

        button {
            width: 100%;
            padding: 10px;
            background: #f4b324;
            border: none;
            font-weight: bold;
            cursor: pointer;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
            color: #ccc;
        }
    </style>
</head>

<body>
    <div class="card">
        <h2 style="text-align: center; color: #f4b324;">Crear Admin</h2>
        <?php echo $mensaje; ?>
        <form method="POST">
            <label>Email Admin</label>
            <input type="email" name="email" value="admin@grupodespo.com" required>

            <label>Contraseña</label>
            <input type="text" name="password" placeholder="Ingresa contraseña segura" required>

            <button type="submit">GENERAR ADMIN</button>
        </form>
        <div style="text-align: center; margin-top: 20px;">
            <a href="login.php" style="color: #ccc;">Ir al Login</a>
        </div>
    </div>
</body>

</html>