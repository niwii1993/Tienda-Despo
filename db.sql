CREATE DATABASE IF NOT EXISTS arcor_db;
USE arcor_db;

-- Users (Admin and Customers)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sigma_client_id VARCHAR(50), -- Mapped to Sigma 'clienteId'
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    telefono VARCHAR(20),
    direccion TEXT,
    ciudad VARCHAR(100),
    provincia VARCHAR(100),
    role ENUM('admin', 'cliente') DEFAULT 'cliente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sigma_id VARCHAR(50) UNIQUE, -- Mapped to Sigma API 'id'
    codigo_barras VARCHAR(50) UNIQUE,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    precio_costo DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    margen DECIMAL(5,2) DEFAULT 30.00, -- Profit margin percentage
    precio_venta DECIMAL(10,2) NOT NULL DEFAULT 0.00, -- Final price
    stock INT DEFAULT 0,
    unidades_por_bulto INT DEFAULT 1,
    categoria VARCHAR(100),
    imagen_url VARCHAR(255),
    es_novedad TINYINT(1) DEFAULT 0,
    es_oferta TINYINT(1) DEFAULT 0,
    descuento_porcentaje DECIMAL(5,2) DEFAULT 0.00, -- Discount % if offer
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Orders
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente', 'confirmado', 'enviado', 'cancelado') DEFAULT 'pendiente',
    fecha_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Order Items
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Default Admin User (Password: admin123)
-- Hash generated for 'admin123'
INSERT INTO users (nombre, apellido, email, password, role) 
VALUES ('Administrador', 'Sistema', 'admin@grupodespo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
ON DUPLICATE KEY UPDATE email=email;
