<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Require login
if (!isLoggedIn()) {
    setFlash('error', 'Silakan login terlebih dahulu untuk menambah produk ke keranjang.');
    redirect('/Amimi/auth/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    $csrfToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validateCsrfToken($csrfToken)) {
        setFlash('error', 'Validasi keamanan gagal. Silakan coba lagi.');
        redirect('/Amimi/products.php');
    }

    $productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $size = isset($_POST['size']) ? sanitize($_POST['size']) : null;
    $qty = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    $userId = $_SESSION['user_id'];

    if ($productId <= 0 || $qty <= 0) {
        setFlash('error', 'Produk tidak valid.');
        redirect('/Amimi/products.php');
    }

    // Check product exists and has stock
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if (!$product) {
        setFlash('error', 'Produk tidak ditemukan atau tidak aktif.');
        redirect('/Amimi/products.php');
    }

    if ($product['stock'] < $qty) {
        setFlash('error', 'Stok produk tidak mencukupi.');
        redirect('/Amimi/product_detail.php?id=' . $productId);
    }

    // Check if garment category has size selected
    if ($product['sizes'] && empty($size)) {
        setFlash('error', 'Silakan pilih ukuran baju Terlebih Dahulu.');
        redirect('/Amimi/product_detail.php?id=' . $productId);
    }

    // Check if product with same size already in cart
    if ($size) {
        $check = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND size = ?");
        $check->bind_param("iis", $userId, $productId, $size);
    } else {
        $check = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND size IS NULL");
        $check->bind_param("ii", $userId, $productId);
    }
    
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();

    if ($existing) {
        // Update quantity
        $newQty = $existing['quantity'] + $qty;
        if ($product['stock'] < $newQty) {
            $newQty = $product['stock']; // Cap at max stock
        }
        
        $update = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $update->bind_param("ii", $newQty, $existing['id']);
        $update->execute();
    } else {
        // Insert new item
        $insert = $conn->prepare("INSERT INTO cart (user_id, product_id, size, quantity) VALUES (?, ?, ?, ?)");
        $insert->bind_param("iisi", $userId, $productId, $size, $qty);
        $insert->execute();
    }

    setFlash('success', 'Produk berhasil ditambahkan ke keranjang kuning ');
    redirect('/Amimi/cart/index.php');
} else {
    redirect('/Amimi/products.php');
}
