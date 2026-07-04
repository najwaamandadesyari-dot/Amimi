<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$userId = $_SESSION['user_id'];

if ($orderId <= 0) {
    setFlash('error', 'Pesanan tidak ditemukan.');
    redirect('/Amimi/customer/orders.php');
}

// Fetch order
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    setFlash('error', 'Pesanan tidak ditemukan atau Anda tidak berhak melihat pesanan ini.');
    redirect('/Amimi/customer/orders.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'cancel') {
        if ($order['order_status'] === 'pending') {
            $stmt = $conn->prepare("UPDATE orders SET order_status = 'cancelled' WHERE id = ?");
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            
            // Restore stock
            $itemStmt = $conn->prepare("SELECT quantity, product_id FROM order_items WHERE order_id = ?");
            $itemStmt->bind_param("i", $orderId);
            $itemStmt->execute();
            $itemsToRestore = $itemStmt->get_result();
            $updateStock = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            while ($item = $itemsToRestore->fetch_assoc()) {
                $updateStock->bind_param("ii", $item['quantity'], $item['product_id']);
                $updateStock->execute();
            }
            
            setFlash('success', 'Pesanan berhasil dibatalkan.');
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'complete') {
        if ($order['order_status'] === 'delivered') {
            $stmt = $conn->prepare("UPDATE orders SET order_status = 'completed', completed_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            setFlash('success', 'Pesanan telah diselesaikan. Terima kasih telah berbelanja di Amimi Shop!');
        }
    }
    redirect('/Amimi/customer/order_detail.php?id=' . $orderId);
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
$items = $itemStmt->get_result();

$pageTitle = 'Detail Pesanan ' . $order['order_number'];
include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 20px;">
    <div style="margin-bottom: 20px;">
        <a href="/Amimi/customer/orders.php" style="color: var(--text-muted); font-size: 14px;">← Kembali ke Riwayat Pesanan</a>
    </div>

    <div class="admin-section-header">
        <h1 style="font-size: 28px; margin-bottom: 0;"> Detail Pesanan</h1>
        <div>
            <span class="badge <?= getOrderStatusLabel($order['order_status'])['class'] ?>" style="font-size: 14px; padding: 6px 12px;">
                Status: <?= getOrderStatusLabel($order['order_status'])['label'] ?>
            </span>
        </div>
    </div>

    <div class="checkout-layout" style="margin-top: 30px;">
        <!-- Left: Order Items & Delivery Details -->
        <div>
            <!-- Order Items -->
            <div class="checkout-section">
                <h3 class="checkout-section-title"> Item Produk yang Dipesan</h3>
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <?php while ($item = $items->fetch_assoc()): ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-dark); padding-bottom: 15px;">
                            <div style="display: flex; gap: 15px; align-items: center;">
                                <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); display: flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--border-dark);">
                                    <span style="font-size: 24px;"><?= $item['product_sizes'] ? '' : '' ?></span>
                                </div>
                                <div>
                                    <h4 style="font-size: 15px;"><?= htmlspecialchars($item['name']) ?></h4>
                                    <?php if ($item['size']): ?>
                                        <span class="cart-item-size" style="margin-top: 4px;">Ukuran: <?= htmlspecialchars($item['size']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-weight: 700; color: var(--primary-gold);"><?= formatRupiah($item['price']) ?></div>
                                <small style="color: var(--text-muted);">Jumlah: <?= $item['quantity'] ?>x</small>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Delivery Details -->
            <div class="checkout-section">
                <h3 class="checkout-section-title"> Detail Alamat & Kontak</h3>
                <div style="font-size: 14px; color: var(--text-muted); display: flex; flex-direction: column; gap: 10px;">
                    <div>
                        <span style="color: var(--text-white); font-weight: 600;">Penerima Paket:</span>
                        <div style="margin-top: 4px;"><?= htmlspecialchars($_SESSION['user_name']) ?> (<?= htmlspecialchars($order['shipping_phone']) ?>)</div>
                    </div>
                    <div>
                        <span style="color: var(--text-white); font-weight: 600;">Alamat Lengkap Pengiriman:</span>
                        <div style="margin-top: 4px; line-height: 1.5;"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></div>
                    </div>
                    <?php if ($order['notes']): ?>
                        <div>
                            <span style="color: var(--text-white); font-weight: 600;">Catatan Tambahan:</span>
                            <div style="margin-top: 4px; color: var(--accent-yellow);">"<?= htmlspecialchars($order['notes']) ?>"</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right: Status Summary Card -->
        <aside>
            <div class="cart-summary">
                <h3 class="summary-title">Informasi Pesanan</h3>
                
                <div class="summary-row">
                    <span style="color: var(--text-muted);">Nomor Pesanan</span>
                    <strong style="color: var(--primary-gold);"><?= htmlspecialchars($order['order_number']) ?></strong>
                </div>

                <div class="summary-row">
                    <span style="color: var(--text-muted);">Waktu Pemesanan</span>
                    <span><?= date('d M Y H:i', strtotime($order['created_at'])) ?></span>
                </div>

                <div class="summary-row">
                    <span style="color: var(--text-muted);">Metode Bayar</span>
                    <span><?= getPaymentMethodLabel($order['payment_method']) ?></span>
                </div>

                <div class="summary-row">
                    <span style="color: var(--text-muted);">Status Bayar</span>
                    <span><?= getPaymentStatusLabel($order['payment_status'])['label'] ?></span>
                </div>

                <div class="summary-row">
                    <span style="color: var(--text-muted);">Ongkos Kirim</span>
                    <span><?= formatRupiah($order['shipping_cost']) ?></span>
                </div>
                
                <?php if ($order['discount_amount'] > 0): ?>
                <div class="summary-row" style="color: var(--danger);">
                    <span>Diskon</span>
                    <span>- <?= formatRupiah($order['discount_amount']) ?></span>
                </div>
                <?php endif; ?>

                <?php if ($order['unique_code'] > 0): ?>
                <div class="summary-row" style="color: var(--primary-gold);">
                    <span>Kode Unik</span>
                    <span>+ Rp <?= number_format($order['unique_code'], 0, ',', '.') ?></span>
                </div>
                <?php endif; ?>

                <div class="summary-row summary-total">
                    <span>Total Pembayaran</span>
                    <span><?= formatRupiah($order['total_amount']) ?></span>
                </div>
                
                <?php if ($order['shipping_method'] === 'pickup' && $order['pickup_number']): ?>
                <div style="background: white; padding: 15px; border-radius: 8px; margin-top: 15px; text-align: center;">
                    <div style="color: black; font-size: 13px; font-weight: 600; margin-bottom: 8px;">Nomor Pengambilan:</div>
                    <div style="color: black; font-size: 18px; font-weight: 800; letter-spacing: 1px; margin-bottom: 10px;"><?= $order['pickup_number'] ?></div>
                    <svg viewBox="0 0 200 50" style="width: 100%; height: 50px;">
                        <?php 
                        // Barcode dummy generator
                        $x = 0;
                        for($i=0; $i<35; $i++) {
                            $w = rand(1, 4);
                            if ($i % 2 == 0) {
                                echo "<rect x='$x' y='0' width='$w' height='50' fill='black' />";
                            }
                            $x += $w;
                        }
                        ?>
                    </svg>
                </div>
                <?php endif; ?>

                <?php if ($order['order_status'] === 'pending' && $order['payment_method'] !== 'cod'): ?>
                    <div style="background-color: rgba(0, 176, 255, 0.1); border: 1px solid var(--info); border-radius: 8px; padding: 12px; font-size: 12px; color: var(--info); line-height: 1.5; text-align: center; margin-top: 10px;">
                         Pembayaran Anda telah disimulasikan sukses otomatis. Admin akan memproses pengiriman produk Anda.
                    </div>
                <?php elseif ($order['order_status'] === 'pending' && $order['payment_method'] === 'cod'): ?>
                    <div style="background-color: rgba(255, 171, 0, 0.1); border: 1px solid var(--warning); border-radius: 8px; padding: 12px; font-size: 12px; color: var(--warning); line-height: 1.5; text-align: center; margin-top: 10px;">
                         Pre-Order COD: Menunggu konfirmasi pesanan dari admin toko sebelum diproses ke pengiriman.
                    </div>
                <?php endif; ?>

                <?php if ($order['order_status'] === 'pending'): ?>
                    <form action="" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pesanan ini?');">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="cancel">
                        <button type="submit" class="btn btn-outline btn-block" style="margin-top: 15px; color: var(--danger); border-color: var(--danger);">Batalkan Pesanan</button>
                    </form>
                <?php elseif ($order['order_status'] === 'delivered'): ?>
                    <form action="" method="POST" onsubmit="return confirm('Apakah pesanan sudah Anda terima dengan baik?');">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="complete">
                        <button type="submit" class="btn btn-gold btn-block" style="margin-top: 15px;"> Selesai Pesanan</button>
                    </form>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
