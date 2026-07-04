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
    $userId = $_SESSION['user_id'];

    if ($cartId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Parameter tidak valid.']);
        exit;
    }

    // Check item belongs to user
    $stmt = $conn->prepare("SELECT id FROM cart WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $cartId, $userId);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();

    if (!$item) {
        echo json_encode(['success' => false, 'message' => 'Produk di keranjang tidak ditemukan.']);
        exit;
    }

    // Delete item
    $delete = $conn->prepare("DELETE FROM cart WHERE id = ?");
    $delete->bind_param("i", $cartId);

    if ($delete->execute()) {
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
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus produk dari database.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method tidak didukung.']);
}
