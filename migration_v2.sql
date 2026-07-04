-- ============================================
-- AMIMI SHOP - Migration V2
-- ============================================

USE amimi_shop;
SET FOREIGN_KEY_CHECKS=0;

-- 1. Table product_sizes
CREATE TABLE IF NOT EXISTS product_sizes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    size VARCHAR(5) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_size (product_id, size)
) ENGINE=InnoDB;

-- Migrate existing sizes if any, logic handled in PHP if needed, but for simplicity we will just create the structure.
-- Let's populate product_sizes based on existing products and their stock
INSERT INTO product_sizes (product_id, size, stock)
SELECT id, 'S', CEIL(stock/4) FROM products WHERE sizes IS NOT NULL AND sizes != '';
INSERT INTO product_sizes (product_id, size, stock)
SELECT id, 'M', CEIL(stock/4) FROM products WHERE sizes IS NOT NULL AND sizes != '';
INSERT INTO product_sizes (product_id, size, stock)
SELECT id, 'L', CEIL(stock/4) FROM products WHERE sizes IS NOT NULL AND sizes != '';
INSERT INTO product_sizes (product_id, size, stock)
SELECT id, 'XL', stock - 3*CEIL(stock/4) FROM products WHERE sizes IS NOT NULL AND sizes != '';

-- We can drop sizes from products later, but let's keep it for now for compatibility and we'll just stop using it or sync it.
-- Actually we can alter table but better leave it in case of rollback.

-- 2. New columns in orders
-- We need to add them safely. In MySQL we can just run ALTER TABLE. It might fail if they exist, but that's fine.
ALTER TABLE orders 
    ADD COLUMN pickup_number VARCHAR(20) NULL AFTER order_number,
    ADD COLUMN shipping_method ENUM('pickup', 'jnt_reg', 'jnt_express') DEFAULT 'pickup' AFTER shipping_address,
    ADD COLUMN shipping_cost DECIMAL(12,2) DEFAULT 0 AFTER shipping_method,
    ADD COLUMN discount_amount DECIMAL(12,2) DEFAULT 0 AFTER total_amount,
    ADD COLUMN unique_code INT DEFAULT 0 AFTER discount_amount,
    ADD COLUMN completed_at TIMESTAMP NULL AFTER updated_at;

-- Update order_status ENUM to include 'completed'
ALTER TABLE orders MODIFY COLUMN order_status ENUM('pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled') DEFAULT 'pending';

-- 3. New columns in users
ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) NULL AFTER avatar;

-- 4. New columns in cart
ALTER TABLE cart ADD COLUMN is_selected TINYINT(1) DEFAULT 1 AFTER quantity;

SET FOREIGN_KEY_CHECKS=1;
