<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$userId = $_SESSION['user_id'];

$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

$whereClause = "WHERE o.user_id = ?";
if ($statusFilter !== 'all') {
    $whereClause .= " AND o.order_status = '" . $conn->real_escape_string($statusFilter) . "'";
}

// Get all orders for this user
$stmt = $conn->prepare("
    SELECT o.*, 
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
    FROM orders o 
    $whereClause 
    ORDER BY o.id DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

$pageTitle = 'Pesanan Saya';
include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 20px;">
    <h1 style="font-size: 28px; margin-bottom: 24px;">Riwayat Pesanan Saya</h1>

    <div style="display: flex; gap: 10px; margin-bottom: 20px; overflow-x: auto; padding-bottom: 5px; white-space: nowrap;">
        <a href="?status=all" class="btn <?= $statusFilter == 'all' ? 'btn-gold' : 'btn-outline' ?> btn-sm">Semua</a>
        <a href="?status=pending" class="btn <?= $statusFilter == 'pending' ? 'btn-gold' : 'btn-outline' ?> btn-sm">Menunggu</a>
        <a href="?status=processing" class="btn <?= $statusFilter == 'processing' ? 'btn-gold' : 'btn-outline' ?> btn-sm">Diproses</a>
        <a href="?status=shipped" class="btn <?= $statusFilter == 'shipped' ? 'btn-gold' : 'btn-outline' ?> btn-sm">Dikirim</a>
        <a href="?status=delivered" class="btn <?= $statusFilter == 'delivered' ? 'btn-gold' : 'btn-outline' ?> btn-sm">Sampai</a>
        <a href="?status=completed" class="btn <?= $statusFilter == 'completed' ? 'btn-gold' : 'btn-outline' ?> btn-sm">Selesai</a>
        <a href="?status=cancelled" class="btn <?= $statusFilter == 'cancelled' ? 'btn-gold' : 'btn-outline' ?> btn-sm">Dibatalkan</a>
    </div>

    <?php if (empty($orders)): ?>
        <div style="text-align: center; padding: 60px 20px; background-color: var(--card-dark); border: 1px solid var(--border-dark); border-radius: 12px; max-width: 600px; margin: 0 auto;">
            <span style="font-size: 64px;"></span>
            <h3 style="margin-top: 20px;">Belum Ada Pesanan</h3>
            <p style="color: var(--text-muted); margin-top: 10px; margin-bottom: 30px;">Anda belum melakukan transaksi belanja apapun.</p>
            <a href="/Amimi/products.php" class="btn btn-gold">Mulai Belanja</a>
        </div>
    <?php else: ?>
        <div class="admin-section" style="padding: 0; overflow: hidden;">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>No. Pesanan</th>
                            <th>Tanggal</th>
                            <th>Metode Bayar</th>
                            <th>Status Bayar</th>
                            <th>Status Pesanan</th>
                            <th>Total Bayar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): 
                            $statusLabel = getOrderStatusLabel($order['order_status']);
                            $payLabel = getPaymentStatusLabel($order['payment_status']);
                        ?>
                            <tr>
                                <td>
                                    <strong style="color: var(--primary-gold);"><?= htmlspecialchars($order['order_number']) ?></strong>
                                    <br>
                                    <small style="color: var(--text-muted);"><?= $order['item_count'] ?> item produk</small>
                                </td>
                                <td><?= date('d M Y H:i', strtotime($order['created_at'])) ?></td>
                                <td><?= getPaymentMethodLabel($order['payment_method']) ?></td>
                                <td>
                                    <span class="badge <?= $payLabel['class'] ?>"><?= $payLabel['label'] ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= $statusLabel['class'] ?>"><?= $statusLabel['label'] ?></span>
                                </td>
                                <td><strong><?= formatRupiah($order['total_amount']) ?></strong></td>
                                <td>
                                    <!-- Detailed Modal or view button trigger -->
                                    <a href="/Amimi/customer/order_detail.php?id=<?= $order['id'] ?>" class="btn btn-outline btn-sm">Lihat Detail </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
