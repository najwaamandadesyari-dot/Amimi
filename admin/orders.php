<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

// Build Query
$query = "
    SELECT o.*, u.name as customer_name, u.customer_id 
    FROM orders o 
    JOIN users u ON o.user_id = u.id
";

$params = [];
$types = "";

if ($statusFilter !== '') {
    $query .= " WHERE o.order_status = ?";
    $params[] = &$statusFilter;
    $types .= "s";
}

$query .= " ORDER BY o.id DESC";

$stmt = $conn->prepare($query);
if ($statusFilter !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

$pageTitle = 'Kelola Pesanan';
include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 20px;">
    <h1 style="font-size: 28px; margin-bottom: 24px;"> Sistem Penerimaan & Kelola Pesanan</h1>

    <!-- Tabs/Filter bar -->
    <div style="display: flex; gap: 10px; margin-bottom: 30px; flex-wrap: wrap;">
        <a href="/Amimi/admin/orders.php" class="btn btn-sm <?= $statusFilter === '' ? 'btn-gold' : 'btn-outline' ?>">Semua</a>
        <a href="/Amimi/admin/orders.php?status=pending" class="btn btn-sm <?= $statusFilter === 'pending' ? 'btn-gold' : 'btn-outline' ?>">Menunggu Konfirmasi</a>
        <a href="/Amimi/admin/orders.php?status=processing" class="btn btn-sm <?= $statusFilter === 'processing' ? 'btn-gold' : 'btn-outline' ?>">Diproses</a>
        <a href="/Amimi/admin/orders.php?status=shipped" class="btn btn-sm <?= $statusFilter === 'shipped' ? 'btn-gold' : 'btn-outline' ?>">Dikirim</a>
        <a href="/Amimi/admin/orders.php?status=delivered" class="btn btn-sm <?= $statusFilter === 'delivered' ? 'btn-gold' : 'btn-outline' ?>">Selesai</a>
        <a href="/Amimi/admin/orders.php?status=cancelled" class="btn btn-sm <?= $statusFilter === 'cancelled' ? 'btn-gold' : 'btn-outline' ?>">Dibatalkan</a>
    </div>

    <!-- Orders Table Section -->
    <div class="admin-section" style="padding: 0; overflow: hidden;">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>No. Pesanan</th>
                        <th>Pelanggan</th>
                        <th>Metode Pembayaran</th>
                        <th>Total Pembayaran</th>
                        <th>Status Bayar</th>
                        <th>Status Pesanan</th>
                        <th>Tanggal Pesan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $order): 
                            $status = getOrderStatusLabel($order['order_status']);
                            $payment = getPaymentStatusLabel($order['payment_status']);
                        ?>
                            <tr>
                                <td>
                                    <strong style="color: var(--primary-gold);"><?= htmlspecialchars($order['order_number']) ?></strong>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($order['customer_name']) ?></strong>
                                    <br>
                                    <small style="color: var(--text-muted); font-size: 11px;">ID: <?= htmlspecialchars($order['customer_id']) ?></small>
                                </td>
                                <td>
                                    <?= getPaymentMethodLabel($order['payment_method']) ?>
                                    <?php if ($order['payment_method'] === 'cod'): ?>
                                        <br><span style="color: var(--accent-yellow); font-size: 10px; font-weight: 700; text-transform: uppercase;">[Pre-Order]</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= formatRupiah($order['total_amount']) ?></strong></td>
                                <td><span class="badge <?= $payment['class'] ?>"><?= $payment['label'] ?></span></td>
                                <td><span class="badge <?= $status['class'] ?>"><?= $status['label'] ?></span></td>
                                <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                <td>
                                    <a href="/Amimi/admin/order_detail.php?id=<?= $order['id'] ?>" class="btn btn-outline btn-sm">Kelola & Detail </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">Tidak ada pesanan untuk filter status ini.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
