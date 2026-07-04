<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$orderNumber = isset($_SESSION['last_order_number']) ? $_SESSION['last_order_number'] : '';
if (empty($orderNumber)) {
    redirect('/Amimi/index.php');
}

// Fetch order details
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_number = ? AND user_id = ?");
$stmt->bind_param("si", $orderNumber, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    redirect('/Amimi/index.php');
}

// Clean up session var
unset($_SESSION['last_order_number']);

$pageTitle = 'Pesanan Berhasil';
include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 40px;">
    <div class="success-card">
        <div class="success-icon"></div>
        <h1 style="font-size: 32px; margin-bottom: 15px;">Terima Kasih!</h1>
        <p style="color: var(--text-white); font-size: 18px; margin-bottom: 5px;">Pesanan Anda berhasil dibuat.</p>
        <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 25px;">Nomor Pesanan: <strong style="color: var(--primary-gold);"><?= htmlspecialchars($orderNumber) ?></strong></p>
        
        <div style="background-color: var(--bg-dark); border: 1px solid var(--border-dark); padding: 20px; border-radius: 12px; margin-bottom: 30px; text-align: left;">
            <div class="summary-row" style="margin-bottom: 10px;">
                <span style="color: var(--text-muted);">Metode Pembayaran:</span>
                <strong style="color: var(--text-white);"><?= getPaymentMethodLabel($order['payment_method']) ?></strong>
            </div>
            <div class="summary-row" style="margin-bottom: 10px;">
                <span style="color: var(--text-muted);">Status Pembayaran:</span>
                <?php $payLabel = getPaymentStatusLabel($order['payment_status']); ?>
                <span class="badge <?= $payLabel['class'] ?>"><?= $payLabel['label'] ?></span>
            </div>
            <div class="summary-row" style="margin-bottom: 10px;">
                <span style="color: var(--text-muted);">Total Bayar:</span>
                <strong style="color: var(--primary-gold); font-size: 18px;"><?= formatRupiah($order['total_amount']) ?></strong>
            </div>
            <div style="border-top: 1px solid var(--border-dark); padding-top: 15px; margin-top: 15px; font-size: 13px; color: var(--text-muted);">
                <?php if ($order['payment_method'] === 'cod'): ?>
                     <strong>Info Pre-Order COD:</strong> Siapkan uang tunai pas saat kurir mengantarkan barang ke alamat Anda.
                <?php else: ?>
                     Pembayaran berhasil disimulasikan secara otomatis. Admin akan memproses pesanan Anda sesegera mungkin.
                <?php endif; ?>
            </div>
        </div>

        <div style="display: flex; gap: 15px; justify-content: center;">
            <a href="/Amimi/customer/orders.php" class="btn btn-gold"> Lacak Pesanan Saya</a>
            <a href="/Amimi/products.php" class="btn btn-outline"> Lanjut Belanja</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
