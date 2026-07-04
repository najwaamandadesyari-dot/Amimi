-- ============================================
-- AMIMI SHOP - Database Setup
-- Run this SQL in phpMyAdmin or MySQL CLI
-- ============================================

CREATE DATABASE IF NOT EXISTS amimi_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE amimi_shop;
SET FOREIGN_KEY_CHECKS=0;

-- ============================================
-- USERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id VARCHAR(10) UNIQUE,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255),
    phone VARCHAR(20),
    address TEXT,
    role ENUM('admin', 'customer') DEFAULT 'customer',
    google_id VARCHAR(100),
    avatar VARCHAR(255),
    profile_image VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- CATEGORIES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- PRODUCTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    cost_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    stock INT NOT NULL DEFAULT 0,
    sizes VARCHAR(50) DEFAULT NULL,
    image VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- PRODUCT SIZES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS product_sizes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    size VARCHAR(5) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_size (product_id, size)
) ENGINE=InnoDB;

-- ============================================
-- CART TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    size VARCHAR(5) DEFAULT NULL,
    quantity INT NOT NULL DEFAULT 1,
    is_selected TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- ORDERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(20) NOT NULL UNIQUE,
    pickup_number VARCHAR(20) NULL,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount_amount DECIMAL(12,2) DEFAULT 0,
    unique_code INT DEFAULT 0,
    payment_method ENUM('cod', 'mbanking', 'ewallet') NOT NULL,
    payment_status ENUM('pending', 'confirmed', 'failed') DEFAULT 'pending',
    order_status ENUM('pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled') DEFAULT 'pending',
    shipping_address TEXT NOT NULL,
    shipping_method ENUM('pickup', 'jnt_reg', 'jnt_express') DEFAULT 'pickup',
    shipping_cost DECIMAL(12,2) DEFAULT 0,
    shipping_phone VARCHAR(20),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- ORDER ITEMS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    size VARCHAR(5) DEFAULT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    cost_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- INSERT DEFAULT CATEGORIES
-- ============================================
INSERT INTO categories (name, slug, icon) VALUES
('Baju Wanita', 'baju-wanita', ''),
('Baju Pria', 'baju-pria', ''),
('Baju Anak-Anak', 'baju-anak', ''),
('Aksesoris', 'aksesoris', ''),
('Peralatan Rumah Tangga', 'peralatan-rumah-tangga', ''),
('Lainnya', 'lainnya', '');

-- ============================================
-- INSERT DEFAULT ADMIN USER
-- Password: admin123 (hashed with password_hash)
-- ============================================
INSERT INTO users (customer_id, name, email, password, role) VALUES
('AMM-00001', 'Admin Amimi', 'admin@amimi.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- ============================================
-- INSERT SAMPLE PRODUCTS
-- ============================================
INSERT INTO products (category_id, name, description, price, cost_price, stock, sizes, image, is_active) VALUES
-- Baju Wanita
(1, 'Blouse Elegant Satin', 'Blouse satin premium dengan detail ruffle yang elegan, cocok untuk acara formal maupun kasual.', 189000, 95000, 50, 'S,M,L,XL', 'blouse_satin.jpg', 1),
(1, 'Dress Floral Summer', 'Dress motif bunga dengan bahan adem, perfect untuk musim panas dan jalan-jalan.', 259000, 130000, 35, 'S,M,L,XL', 'dress_floral.jpg', 1),
(1, 'Cardigan Rajut Oversize', 'Cardigan rajut oversize yang nyaman dan stylish untuk layering sehari-hari.', 175000, 88000, 40, 'S,M,L,XL', 'cardigan_rajut.jpg', 1),

-- Baju Pria
(2, 'Kemeja Linen Casual', 'Kemeja linen premium dengan potongan slim fit, nyaman dipakai seharian.', 225000, 110000, 45, 'S,M,L,XL', 'kemeja_linen.jpg', 1),
(2, 'Kaos Polo Premium', 'Kaos polo dengan bahan cotton combed 30s, tersedia berbagai warna.', 149000, 70000, 60, 'S,M,L,XL', 'kaos_polo.jpg', 1),
(2, 'Jaket Bomber Streetwear', 'Jaket bomber dengan desain modern streetwear, anti air dan ringan.', 350000, 175000, 30, 'S,M,L,XL', 'jaket_bomber.jpg', 1),

-- Baju Anak
(3, 'Set Baju Anak Lucu', 'Set baju anak dengan motif karakter lucu, bahan katun lembut dan aman.', 129000, 60000, 40, 'S,M,L,XL', 'baju_anak_set.jpg', 1),
(3, 'Dress Anak Princess', 'Dress anak perempuan dengan desain princess yang cantik dan nyaman.', 159000, 75000, 35, 'S,M,L,XL', 'dress_anak.jpg', 1),

-- Aksesoris
(4, 'Tas Selempang Mini', 'Tas selempang mini dari kulit sintetis premium, multifungsi dan stylish.', 145000, 65000, 50, NULL, 'tas_selempang.jpg', 1),
(4, 'Kacamata Fashion UV400', 'Kacamata fashion dengan perlindungan UV400, desain trendy dan ringan.', 89000, 35000, 70, NULL, 'kacamata.jpg', 1),
(4, 'Topi Bucket Hat', 'Topi bucket hat unisex dari bahan kanvas premium, cocok untuk outdoor.', 79000, 30000, 55, NULL, 'topi_bucket.jpg', 1),

-- Peralatan Rumah Tangga
(5, 'Set Peralatan Masak Anti Lengket', 'Set peralatan masak 5 pcs dengan lapisan anti lengket berkualitas tinggi.', 450000, 220000, 20, NULL, 'peralatan_masak.jpg', 1),
(5, 'Organizer Serbaguna', 'Organizer multi-compartment untuk merapikan rumah, bahan kayu premium.', 185000, 85000, 30, NULL, 'organizer.jpg', 1),
(5, 'Set Handuk Premium', 'Set handuk 3 pcs dari bahan microfiber super lembut dan cepat kering.', 125000, 55000, 45, NULL, 'handuk_set.jpg', 1),

-- Lainnya
(6, 'Tumbler Stainless 500ml', 'Tumbler stainless steel vacuum insulated, tahan panas 12 jam.', 135000, 55000, 40, NULL, 'tumbler.jpg', 1),
(6, 'Power Bank 10000mAh', 'Power bank slim dengan kapasitas 10000mAh, fast charging support.', 199000, 95000, 25, NULL, 'powerbank.jpg', 1);

SET FOREIGN_KEY_CHECKS=1;
