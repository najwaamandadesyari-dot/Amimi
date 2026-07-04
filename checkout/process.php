<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    $csrfToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validateCsrfToken($csrfToken)) {
        setFlash('error', 'Validasi keamanan gagal. Silakan coba lagi.');
        redirect('/Amimi/cart/index.php');
    }

    $userId = $_SESSION['user_id'];
    $shippingName = sanitize($_POST['shipping_name']);
    $shippingPhone = sanitize($_POST['shipping_phone']);
    $shippingAddress = sanitize($_POST['shipping_address']);
    $notes = sanitize($_POST['notes']);
    $shippingMethod = isset($_POST['shipping_method']) ? sanitize($_POST['shipping_method']) : 'pickup';
    $paymentMethod = isset($_POST['payment_method']) ? sanitize($_POST['payment_method']) : '';

    if (empty($shippingName) || empty($shippingPhone) || empty($shippingAddress) || empty($paymentMethod)) {
        setFlash('error', 'Semua kolom bertanda bintang wajib diisi.');
        redirect('/Amimi/checkout/index.php');
    }

    $selectedItems = $_SESSION['checkout_items'] ?? [];
    if (empty($selectedItems)) {
        setFlash('error', 'Keranjang belanja Anda kosong atau sesi checkout kedaluwarsa.');
        redirect('/Amimi/cart/index.php');
    }

    // Get cart items
    $placeholders = implode(',', array_fill(0, count($selectedItems), '?'));
    $types = "i" . str_repeat('i', count($selectedItems));
    $params = array_merge([$userId], $selectedItems);

    $stmt = $conn->prepare("SELECT c.*, p.price, p.cost_price, p.stock as main_stock, p.name FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ? AND c.id IN ($placeholders)");
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $cartItems = $stmt->get_result();

    if ($cartItems->num_rows === 0) {
        setFlash('error', 'Keranjang belanja Anda kosong.');
        redirect('/Amimi/cart/index.php');
    }

    // Validate stock and calculate total
    $itemsArray = [];
    $totalAmount = 0;
    $hasStockError = false;

    while ($item = $cartItems->fetch_assoc()) {
        $checkStock = $item['main_stock'];
        if (!empty($item['size'])) {
            $szStmt = $conn->prepare("SELECT stock FROM product_sizes WHERE product_id = ? AND size = ?");
            $szStmt->bind_param("is", $item['product_id'], $item['size']);
            $szStmt->execute();
            $szRes = $szStmt->get_result()->fetch_assoc();
            if ($szRes) {
                $checkStock = $szRes['stock'];
            } else {
                $checkStock = 0;
            }
        }

        if ($checkStock < $item['quantity']) {
            $msg = 'Stok produk "' . $item['name'] . '"';
            if (!empty($item['size'])) $msg .= ' (Ukuran ' . $item['size'] . ')';
            $msg .= ' tidak mencukupi. Tersedia: ' . $checkStock;
            
            setFlash('error', $msg);
            $hasStockError = true;
            break;
        }
        $itemsArray[] = $item;
        $totalAmount += $item['price'] * $item['quantity'];
    }
    // Compute costs
    $shippingCost = 0;
    if ($shippingMethod === 'jnt_reg') $shippingCost = 10000;
    elseif ($shippingMethod === 'jnt_express') $shippingCost = 20000;
    elseif ($shippingMethod === 'jnt_reg_luar_pulau') $shippingCost = 35000;

    $discount = 0;
    if ($totalAmount > 500000) {
        $discount = max(50000, $totalAmount * 0.10);
    }

    $uniqueCode = $_SESSION['checkout_unique_code'] ?? rand(100, 999);
    $finalTotal = $totalAmount - $discount + $shippingCost + $uniqueCode;
    if ($hasStockError) {
        redirect('/Amimi/cart/index.php');
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Generate Order Number & Pickup Number
        $orderNumber = generateOrderNumber($conn);
        $pickupNumber = ($shippingMethod === 'pickup') ? 'PU-' . strtoupper(substr(uniqid(), -5)) : null;
        
        // Define payment status
        // For COD it's 'pending' until they receive it.
        // For simulated M-Banking and E-Wallet, we automatically set it to 'confirmed' for simplicity/simulation success.
        $paymentStatus = ($paymentMethod === 'cod') ? 'pending' : 'confirmed';
        
        // Insert order
        $insertOrder = $conn->prepare("INSERT INTO orders (user_id, order_number, pickup_number, total_amount, discount_amount, unique_code, payment_method, payment_status, order_status, shipping_address, shipping_method, shipping_cost, shipping_phone, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?)");
        $insertOrder->bind_param("issddissssddss", $userId, $orderNumber, $pickupNumber, $finalTotal, $discount, $uniqueCode, $paymentMethod, $paymentStatus, $shippingAddress, $shippingMethod, $shippingCost, $shippingPhone, $notes);
        $insertOrder->execute();
        $orderId = $insertOrder->insert_id;

        // Insert order items & update product stock
        $insertItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, size, quantity, price, cost_price) VALUES (?, ?, ?, ?, ?, ?)");
        $updateStock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $updateSizeStock = $conn->prepare("UPDATE product_sizes SET stock = stock - ? WHERE product_id = ? AND size = ?");

        foreach ($itemsArray as $item) {
            $insertItem->bind_param("iisidd", $orderId, $item['product_id'], $item['size'], $item['quantity'], $item['price'], $item['cost_price']);
            $insertItem->execute();

            $updateStock->bind_param("ii", $item['quantity'], $item['product_id']);
            $updateStock->execute();
            
            if (!empty($item['size'])) {
                $updateSizeStock->bind_param("iis", $item['quantity'], $item['product_id'], $item['size']);
                $updateSizeStock->execute();
            }
        }

        // Clear SELECTED items from cart
        $deletePlaceholders = implode(',', array_fill(0, count($selectedItems), '?'));
        $clearCart = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND id IN ($deletePlaceholders)");
        $clearCart->bind_param($types, ...$params);
        $clearCart->execute();
        
        // Clean session
        unset($_SESSION['checkout_items']);
        unset($_SESSION['checkout_unique_code']);

        // Commit transaction
        $conn->commit();

        // Set success message
        $successMsg = 'Pesanan berhasil dibuat dengan nomor pesanan: ' . $orderNumber;
        if ($paymentMethod === 'cod') {
            $successMsg .= '. Silakan siapkan pembayaran COD saat kurir tiba.';
        } else {
            $successMsg .= '. Terima kasih telah melakukan pembayaran simulasi.';
        }
        
        setFlash('success', $successMsg);
        
        // Show success screen
        $_SESSION['last_order_number'] = $orderNumber;
        redirect('/Amimi/checkout/success.php');

    } catch (Exception $e) {
        $conn->rollback();
        setFlash('error', 'Terjadi kesalahan sistem saat memproses checkout: ' . $e->getMessage());
        redirect('/Amimi/checkout/index.php');
    }
} else {
    redirect('/Amimi/products.php');
}
