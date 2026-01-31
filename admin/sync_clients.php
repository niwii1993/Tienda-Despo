<?php
// admin/sync_clients.php
require_once '../config/db.php';
require_once '../config/apisigma.php';
require_once 'includes/auth_check.php';

// INCREASE LIMITS
set_time_limit(0);
ini_set('memory_limit', '1024M');

$pageTitle = "Sincronizar Clientes";
include 'includes/header.php';

// --- Helper Functions ---
function fetchApiData($endpoint, $params = [])
{
    $result = callSigmaApi($endpoint, $params);
    if ($result['http_code'] == 429) {
        $waitMs = $result['headers']['X-Retry-After-ms'] ?? 5000;
        sleep(ceil($waitMs / 1000) + 1);
        $result = callSigmaApi($endpoint, $params);
    }
    return ($result['http_code'] == 200) ? $result['response'] : null;
}

$message = "";
$messageType = "";
$results = null;

if (isset($_POST['sync'])) {
    // 1. Fetch Clients
    $rawClients = fetchApiData('ExportClientes');

    if ($rawClients) {
        $count = 0;
        $updated = 0;
        $created = 0;

        $checkSql = "SELECT id FROM users WHERE sigma_id = ?";
        $stmtExists = $conn->prepare($checkSql);

        $updateSql = "UPDATE users SET nombre = ?, apellido = ?, email = ?, telefono = ?, direccion = ?, ciudad = ?, provincia = ? WHERE sigma_id = ?";
        $stmtUpdate = $conn->prepare($updateSql);

        // For INSERT, we use a placeholder password since they are imported
        $default_pass = password_hash('cliente123', PASSWORD_DEFAULT);
        $insertSql = "INSERT INTO users (sigma_id, nombre, apellido, email, password, telefono, direccion, ciudad, provincia, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'cliente')";
        $stmtInsert = $conn->prepare($insertSql);

        foreach ($rawClients as $c) {
            // Sigma ID
            $sid = (string) ($c['id'] ?? ($c['codigo'] ?? ''));
            if (empty($sid))
                continue;

            // Map Fields
            $name_full = $c['razonSocial'] ?? ($c['nombre'] ?? 'Cliente Sigma');
            // Split name simplistic (First word = Name, rest = Surname) or just put all in Name
            $parts = explode(' ', $name_full, 2);
            $nombre = $parts[0];
            $apellido = $parts[1] ?? '';

            // Email (Unique constraint in DB might fail if empty or duplicate)
            $email = $c['email'] ?? '';
            if (empty($email)) {
                // Generate a fake email if missing to avoid constraints or skip?
                // Let's use ID@sigma.temp for now if unique constraint exists
                $email = "cliente_$sid@sin-email.com";
            }

            $telefono = $c['telefono'] ?? ($c['celular'] ?? '');
            $direccion = $c['domicilio'] ?? '';
            $ciudad = $c['localidad'] ?? '';
            $provincia = $c['provincia'] ?? '';

            // Check Exists
            $stmtExists->bind_param("s", $sid);
            $stmtExists->execute();
            $existsRes = $stmtExists->get_result();

            if ($existsRes->num_rows > 0) {
                // Update
                $stmtUpdate->bind_param("ssssssss", $nombre, $apellido, $email, $telefono, $direccion, $ciudad, $provincia, $sid);
                $stmtUpdate->execute();
                $updated++;
            } else {
                // Insert - Check existing email first to avoid crash?
                // For now, try insert, ignore if fail? 
                // Better: Check email collision manually if not using sigma_id

                $stmtInsert->bind_param("sssssssss", $sid, $nombre, $apellido, $email, $default_pass, $telefono, $direccion, $ciudad, $provincia);
                if ($stmtInsert->execute()) {
                    $created++;
                }
            }
            $count++;
        }

        $messageType = "success";
        $message = "Sincronización de Clientes completada.<br>Procesados: $count <br>Nuevos: $created <br>Actualizados: $updated";
        $results = ['processed' => $count, 'created' => $created, 'updated' => $updated];

    } else {
        $messageType = "danger";
        $message = "Error al descargar Clientes de Sigma (ExportClientes).";
    }
}
?>

<div class="form-card" style="max-width: 800px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 30px;">
        <i class="fa-solid fa-users-gear" style="font-size: 48px; color: var(--primary-blue); margin-bottom: 15px;"></i>
        <h2 style="color: var(--text-dark);">Sincronizar Clientes</h2>
        <p style="color: #666;">Importa clientes desde Sigma ERP a la base local.</p>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 25px; border-radius: 8px;">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <form method="POST" id="syncForm">
        <div style="display: flex; gap: 15px; justify-content: center;">
            <a href="clientes.php" class="btn btn-secondary"
                style="background-color: #6c757d; color: white; padding: 12px 25px; border-radius: 8px; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </a>
            <button type="submit" name="sync" class="btn btn-primary"
                style="background-color: var(--primary-blue); color: white; padding: 12px 30px; border-radius: 8px; border: none; cursor: pointer; font-size: 16px; font-weight: 500;"
                onclick="showLoading()">
                <i class="fa-solid fa-cloud-arrow-down"></i> Iniciar Sincronización
            </button>
        </div>
    </form>
</div>

<div id="loadingOverlay"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.8); z-index: 9999; justify-content: center; align-items: center; flex-direction: column;">
    <div class="spinner-border text-primary" role="status"
        style="width: 3rem; height: 3rem; border: 5px solid #f3f3f3; border-top: 5px solid var(--primary-blue); border-radius: 50%; animation: spin 1s linear infinite;">
    </div>
    <h4 style="margin-top: 20px;">Sincronizando Clientes...</h4>
</div>

<style>
    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }
</style>
<script>
    function showLoading() { document.getElementById('loadingOverlay').style.display = 'flex'; }
</script>