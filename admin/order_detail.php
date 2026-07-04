<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($orderId <= 0) {
    setFlash('error', 'Pesanan tidak ditemukan.');
    redirect('/Amimi/admin/orders.php');
}

// Fetch order details
$stmt = $conn->prepare("
    SELECT o.*, u.name as customer_name, u.email as customer_email, u.customer_id 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?
");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    setFlash('error', 'Pesanan tidak ditemukan.');
    redirect('/Amimi/admin/orders.php');
}

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    $csrfToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validateCsrfToken($csrfToken)) {
        setFlash('error', 'Validasi keamanan gagal.');
        redirect('/Amimi/admin/order_detail.php?id=' . $orderId);
    }

    if (isset($_POST['update_order_status'])) {
        $newOrderStatus = sanitize($_POST['order_status']);
        $newPaymentStatus = sanitize($_POST['payment_status']);
        
        // If status changes to 'cancelled', restore product stock
        if ($newOrderStatus === 'cancelled' && $order['order_status'] !== 'cancelled') {
            $conn->begin_transaction();
            try {
                $itemsQuery = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                $itemsQuery->bind_param("i", $orderId);
                $itemsQuery->execute();
                $itemsResult = $itemsQuery->get_result();
                
                $restoreStock = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                while ($item = $itemsResult->fetch_assoc()) {
                    $restoreStock->bind_param("ii", $item['quantity'], $item['product_id']);
                    $restoreStock->execute();
                }
                
                $update = $conn->prepare("UPDATE orders SET order_status = ?, payment_status = ? WHERE id = ?");
                $update->bind_param("ssi", $newOrderStatus, $newPaymentStatus, $orderId);
                $update->execute();
                
                $conn->commit();
                setFlash('success', 'Status pesanan diperbarui menjadi Dibatalkan. Stok produk telah dikembalikan.');
            } catch (Exception $e) {
                $conn->rollback();
                setFlash('error', 'Gagal membatalkan pesanan: ' . $e->getMessage());
            }
        } else {
            // Normal status update
            // If completed (delivered) and it's COD, auto-confirm payment
            if ($newOrderStatus === 'delivered' && $order['payment_method'] === 'cod') {
                $newPaymentStatus = 'confirmed';
            }

            $update = $conn->prepare("UPDATE orders SET order_status = ?, payment_status = ? WHERE id = ?");
            $update->bind_param("ssi", $newOrderStatus, $newPaymentStatus, $orderId);
            if ($update->execute()) {
                setFlash('success', 'Status pesanan berhasil diperbarui.');
            } else {
                setFlash('error', 'Gagal memperbarui status pesanan.');
            }
        }
        
        redirect('/Amimi/admin/order_detail.php?id=' . $orderId);
    }
}

// Fetch order items
$itemStmt = $conn->prepare("
    SELECT oi.*, p.name, p.sizes as product_sizes 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$itemStmt->bind_param("i", $orderId);
$itemStmt->execute();
$itemsResult = $itemStmt->get_result();

$items = [];
$totalCost = 0;
while ($row = $itemsResult->fetch_assoc()) {
    $items[] = $row;
    $totalCost += $row['cost_price'] * $row['quantity'];
}

$profit = $order['total_amount'] - $totalCost;

$pageTitle = 'Kelola Pesanan ' . $order['order_number'];
include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 20px;">
    <div style="margin-bottom: 20px;">
        <a href="/Amimi/admin/orders.php" style="color: var(--text-muted); font-size: 14px;">← Kembali ke Kelola Pesanan</a>
    </div>

    <div class="admin-section-header">
        <h1 style="font-size: 28px; margin-bottom: 0;"> Kelola Transaksi</h1>
        <div>
            <span class="badge <?= getOrderStatusLabel($order['order_status'])['class'] ?>" style="font-size: 14px; padding: 6px 12px;">
                Status: <?= getOrderStatusLabel($order['order_status'])['label'] ?>
            </span>
        </div>
    </div>

    <div class="checkout-layout" style="margin-top: 30px;">
        <!-- Left: Details and Items -->
        <div>
            <!-- Order Items Table -->
            <div class="checkout-section">
                <h3 class="checkout-section-title">Produk Yang Dipesan</h3>
                <div class="table-responsive">
                    <table style="font-size: 13px;">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Ukuran</th>
                                <th style="text-align: right;">Harga Beli (HPP)</th>
                                <th style="text-align: right;">Harga Jual</th>
                                <th style="text-align: center;">Jumlah</th>
                                <th style="text-align: right;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($item['name']) ?></strong></td>
                                    <td><?= $item['size'] ? '<span class="badge badge-info">' . $item['size'] . '</span>' : 'N/A' ?></td>
                                    <td style="text-align: right; color: var(--text-muted);"><?= formatRupiah($item['cost_price']) ?></td>
                                    <td style="text-align: right;"><?= formatRupiah($item['price']) ?></td>
                                    <td style="text-align: center;"><?= $item['quantity'] ?>x</td>
                                    <td style="text-align: right; font-weight: 700; color: var(--primary-gold);"><?= formatRupiah($item['price'] * $item['quantity']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Delivery & Customer Info -->
            <div class="checkout-section">
                <h3 class="checkout-section-title"> Informasi Pelanggan & Pengiriman</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; font-size: 14px; color: var(--text-muted);">
                    <div>
                        <h4 style="color: var(--text-white); margin-bottom: 8px;">Profil Pelanggan</h4>
                        <p style="margin-bottom: 6px;">Nama: <strong><?= htmlspecialchars($order['customer_name']) ?></strong></p>
                        <p style="margin-bottom: 6px;">ID Pelanggan: <strong style="color: var(--primary-gold);"><?= htmlspecialchars($order['customer_id']) ?></strong></p>
                        <p style="margin-bottom: 6px;">Email: <?= htmlspecialchars($order['customer_email']) ?></p>
                        <p>No. Telepon: <?= htmlspecialchars($order['shipping_phone']) ?></p>
                    </div>
                    <div>
                        <h4 style="color: var(--text-white); margin-bottom: 8px;">Alamat Pengiriman</h4>
                        <p style="line-height: 1.5;"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
                        <?php if ($order['notes']): ?>
                            <p style="margin-top: 10px; color: var(--accent-yellow);">Catatan: "<?= htmlspecialchars($order['notes']) ?>"</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Status Update Form & Profit calculation -->
        <aside style="position: sticky; top: 20px; align-self: start;">
            <!-- Update Status Box -->
            <div class="cart-summary" style="margin-bottom: 20px;">
                <h3 class="summary-title">Update Status Pesanan</h3>
                <form action="/Amimi/admin/order_detail.php?id=<?= $orderId ?>" method="POST">
                    <?= csrfField() ?>
                    
                    <div class="form-group">
                        <label class="form-label" for="order_status">Status Pesanan</label>
                        <select name="order_status" id="order_status" class="form-control">
                            <option value="pending" <?= $order['order_status'] === 'pending' ? 'selected' : '' ?>>Menunggu Konfirmasi</option>
                            <option value="processing" <?= $order['order_status'] === 'processing' ? 'selected' : '' ?>>Sedang Diproses</option>
                            <option value="shipped" <?= $order['order_status'] === 'shipped' ? 'selected' : '' ?>>Sedang Dikirim</option>
                            <option value="delivered" <?= $order['order_status'] === 'delivered' ? 'selected' : '' ?>>Selesai (Sampai)</option>
                            <option value="cancelled" <?= $order['order_status'] === 'cancelled' ? 'selected' : '' ?>>Dibatalkan</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="payment_status">Status Pembayaran</label>
                        <select name="payment_status" id="payment_status" class="form-control">
                            <option value="pending" <?= $order['payment_status'] === 'pending' ? 'selected' : '' ?>>Belum Bayar (Pending)</option>
                            <option value="confirmed" <?= $order['payment_status'] === 'confirmed' ? 'selected' : '' ?>>Lunas (Confirmed)</option>
                            <option value="failed" <?= $order['payment_status'] === 'failed' ? 'selected' : '' ?>>Gagal (Failed)</option>
                        </select>
                    </div>

                    <button type="submit" name="update_order_status" class="btn btn-gold btn-block" style="margin-top: 10px;">Perbarui Status </button>
                </form>
            </div>

            <!-- Financial Analysis Box -->
            <div class="cart-summary">
                <h3 class="summary-title">Analisis Keuntungan Transaksi</h3>
                <div class="summary-row">
                    <span>Omset Penjualan</span>
                    <strong><?= formatRupiah($order['total_amount']) ?></strong>
                </div>
                <div class="summary-row">
                    <span>Total HPP (Modal)</span>
                    <span style="color: var(--text-muted);"><?= formatRupiah($totalCost) ?></span>
                </div>
                <div class="summary-row summary-total" style="border-color: var(--border-dark);">
                    <span>Laba Kotor</span>
                    <span class="<?= $profit >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= formatRupiah($profit) ?>
                    </span>
                </div>
                <div style="font-size: 11px; color: var(--text-muted); line-height: 1.4; margin-top: 10px;">
                    *Laba bersih tercatat otomatis pada kalkulator P&L dashboard utama admin jika status pesanan diset **Selesai (Sampai)** dan status pembayaran **Lunas**.
                </div>
            </div>
        </aside>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
