<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cartId = isset($_POST['cart_id']) ? intval($_POST['cart_id']) : 0;
    $qty = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    $userId = $_SESSION['user_id'];

    if ($cartId <= 0 || $qty <= 0) {
        echo json_encode(['success' => false, 'message' => 'Parameter tidak valid.']);
        exit;
    }

    // Check if cart item belongs to user and check stock
    $stmt = $conn->prepare("SELECT c.*, p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ? AND c.user_id = ?");
    $stmt->bind_param("ii", $cartId, $userId);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();

    if (!$item) {
        echo json_encode(['success' => false, 'message' => 'Produk di keranjang tidak ditemukan.']);
        exit;
    }

    if ($item['stock'] < $qty) {
        echo json_encode(['success' => false, 'message' => 'Stok produk tidak mencukupi. Maksimal: ' . $item['stock']]);
        exit;
    }

    // Update quantity
    $update = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    $update->bind_param("ii", $qty, $cartId);
    
    if ($update->execute()) {
        // Calculate new total amount for the user
        $totalQuery = $conn->prepare("SELECT SUM(c.quantity * p.price) as total_amount FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
        $totalQuery->bind_param("i", $userId);
        $totalQuery->execute();
        $totalRes = $totalQuery->get_result()->fetch_assoc();
        $totalAmount = $totalRes['total_amount'] ?? 0;

        // Get total cart items count
        $countQuery = $conn->prepare("SELECT SUM(quantity) as total_count FROM cart WHERE user_id = ?");
        $countQuery->bind_param("i", $userId);
        $countQuery->execute();
        $countRes = $countQuery->get_result()->fetch_assoc();
        $cartCount = $countRes['total_count'] ?? 0;

        echo json_encode([
            'success' => true,
            'total_amount' => $totalAmount,
            'cart_count' => $cartCount
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengubah jumlah di database.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method tidak didukung.']);
}
