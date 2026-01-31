<?php
// login.php
require_once 'config/db.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, nombre, apellido, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nombre'];
            $_SESSION['user_role'] = $user['role'];

            if ($user['role'] == 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $error = "Contraseña incorrecta.";
        }
    } else {
        $error = "Usuario no encontrado.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Grupo Despo</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .auth-container {
            max-width: 400px;
            margin: 80px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .btn-submit {
            width: 100%;
            background: var(--primary-blue);
            color: white;
            padding: 12px;
            border: none;
            border: 8px;
            font-weight: bold;
            cursor: pointer;
        }

        .error-msg {
            color: #d32f2f;
            margin-bottom: 15px;
            font-size: 14px;
        }
    </style>
</head>

<body style="background-color: #F5F6FA;">
    <div class="auth-container">
        <div style="font-size: 50px; color: var(--primary-blue); margin-bottom: 20px;">
            <i class="fa-solid fa-circle-user"></i>
        </div>
        <h2 style="color: var(--primary-blue); margin-bottom: 5px;">GRUPODESPO</h2>
        <p style="color: #666; margin-bottom: 30px;">Bienvenido de nuevo</p>

        <?php if ($error): ?>
            <div class="error-msg">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="email" name="email" class="form-control" placeholder="Correo electrónico" required>
            <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
            <button type="submit" class="btn-submit">Ingresar</button>
        </form>

        <div style="margin-top: 20px; font-size: 14px;">
            ¿No tienes cuenta? <a href="registro.php" style="color: var(--primary-blue); font-weight: 500;">Regístrate
                aquí</a>
        </div>
        <div style="margin-top: 10px; font-size: 14px;">
            <a href="index.php" style="color: #666;">Volver a la tienda</a>
        </div>
    </div>
</body>

</html>