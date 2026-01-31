<?php
require_once 'config/db.php';

// Create table if not exists (Essential step missed before)
$conn->query("CREATE TABLE IF NOT EXISTS sliders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255),
    descripcion TEXT,
    texto_boton VARCHAR(50),
    enlace_boton VARCHAR(255),
    imagen_url VARCHAR(255),
    orden INT DEFAULT 0
)");

// Clear existing sliders
$conn->query("TRUNCATE TABLE sliders");

// Example 1: Ofertas (Shopping)
$title1 = "Ofertas Imperdibles de la Semana";
$desc1 = "Aprovecha hasta un 30% de descuento en chocolates y galletas seleccionadas.";
$btn1 = "Ver Descuentos >";
$link1 = "#destacados";
$img1 = "https://images.unsplash.com/photo-1604719312566-b7cb0274bc71?q=80&w=1920&auto=format&fit=crop";

// Example 2: Envíos (Logistics/Box)
$title2 = "Envíos Gratis a Todo el País";
$desc2 = "En compras superiores a $50.000. Recibí tu pedido en 24/48hs.";
$btn2 = "Cómo Comprar";
$link2 = "#";
$img2 = "https://plus.unsplash.com/premium_photo-1664303356064-46c59501e521?q=80&w=1920&auto=format&fit=crop";

// Example 3: Branding (Arcor/Gradient)
$title3 = "Distribuidor Oficial Arcor";
$desc3 = "La mayor variedad de productos para tu kiosco o negocio.";
$btn3 = "Ver Catálogo";
$link3 = "#";
$img3 = "";

$stmt = $conn->prepare("INSERT INTO sliders (titulo, descripcion, texto_boton, enlace_boton, imagen_url, orden) VALUES (?, ?, ?, ?, ?, ?)");

if ($stmt) {
    // Insert 1
    $ord = 1;
    $stmt->bind_param("sssssi", $title1, $desc1, $btn1, $link1, $img1, $ord);
    $stmt->execute();

    // Insert 2
    $ord = 2;
    $stmt->bind_param("sssssi", $title2, $desc2, $btn2, $link2, $img2, $ord);
    $stmt->execute();

    // Insert 3
    $ord = 3;
    $stmt->bind_param("sssssi", $title3, $desc3, $btn3, $link3, $img3, $ord);
    $stmt->execute();

    echo "Sliders cargados correctamente. Tabla verificada.";
} else {
    echo "Error preparando consulta: " . $conn->error;
}
?>