<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$userId = $_SESSION['user_id'];

// Get user info
$user = getUserById($conn, $userId);

// Get total orders
$orderCountStmt = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ?");
$orderCountStmt->bind_param("i", $userId);
$orderCountStmt->execute();
$orderCount = $orderCountStmt->get_result()->fetch_assoc()['total'];

// Get total spent
$spentStmt = $conn->prepare("SELECT SUM(total_amount) as total FROM orders WHERE user_id = ? AND payment_status = 'confirmed'");
$spentStmt->bind_param("i", $userId);
$spentStmt->execute();
$totalSpent = $spentStmt->get_result()->fetch_assoc()['total'] ?? 0;

// Get pending orders
$pendingStmt = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ? AND order_status IN ('pending', 'processing', 'shipped')");
$pendingStmt->bind_param("i", $userId);
$pendingStmt->execute();
$pendingOrders = $pendingStmt->get_result()->fetch_assoc()['total'];

// Get cart count
$cartStmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
$cartStmt->bind_param("i", $userId);
$cartStmt->execute();
$cartCount = $cartStmt->get_result()->fetch_assoc()['total'] ?? 0;

// Get recent orders
$recentStmt = $conn->prepare("
    SELECT o.*, COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 5
");
$recentStmt->bind_param("i", $userId);
$recentStmt->execute();
$recentOrders = $recentStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Dashboard Pembeli';
include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 20px; padding-bottom: 40px;">
    <!-- Header -->
    <div style="margin-bottom: 40px;">
        <h1 style="font-size: 32px; font-weight: 700; margin-bottom: 8px;">
             Selamat datang, <?= htmlspecialchars($user['name']) ?>!
        </h1>
        <p style="color: var(--text-muted); font-size: 16px;">
            Customer ID: <strong><?= htmlspecialchars($user['customer_id']) ?></strong> • Member sejak <?= date('d M Y', strtotime($user['created_at'])) ?>
        </p>
    </div>

    <!-- Quick Stats -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 40px;">
        <!-- Total Orders -->
        <div style="background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%); border-radius: 12px; padding: 24px; color: white; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);">
            <div style="font-size: 28px; margin-bottom: 8px; font-weight: 700;"><?= $orderCount ?></div>
            <div style="font-size: 14px; opacity: 0.9;">Total Pesanan</div>
        </div>

        <!-- Total Spent -->
        <div style="background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%); border-radius: 12px; padding: 24px; color: white; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);">
            <div style="font-size: 28px; margin-bottom: 8px; font-weight: 700;"><?= formatRupiah($totalSpent) ?></div>
            <div style="font-size: 14px; opacity: 0.9;">Total Belanja</div>
        </div>

        <!-- Pending Orders -->
        <div style="background: linear-gradient(135deg, #ec4899 0%, #f43f5e 100%); border-radius: 12px; padding: 24px; color: white; box-shadow: 0 4px 12px rgba(236, 72, 153, 0.3);">
            <div style="font-size: 28px; margin-bottom: 8px; font-weight: 700;"><?= $pendingOrders ?></div>
            <div style="font-size: 14px; opacity: 0.9;">Pesanan Aktif</div>
        </div>

        <!-- Cart Items -->
        <div style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); border-radius: 12px; padding: 24px; color: white; box-shadow: 0 4px 12px rgba(6, 182, 212, 0.3);">
            <div style="font-size: 28px; margin-bottom: 8px; font-weight: 700;"><?= $cartCount ?></div>
            <div style="font-size: 14px; opacity: 0.9;">Item di Keranjang</div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div style="display: grid; grid-template-columns: 1fr 300px; gap: 30px;">
        <!-- Recent Orders Section -->
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="font-size: 22px; font-weight: 700; margin: 0;">Pesanan Terbaru</h2>
                <a href="/Amimi/customer/orders.php" style="color: var(--primary-gold); text-decoration: none; font-weight: 600;">Lihat Semua →</a>
            </div>

            <?php if (empty($recentOrders)): ?>
                <div style="background-color: var(--card-dark); border: 1px solid var(--border-dark); border-radius: 12px; padding: 40px 20px; text-align: center;">
                    <span style="font-size: 48px; display: block; margin-bottom: 15px;"></span>
                    <p style="color: var(--text-muted); margin-bottom: 20px;">Anda belum memiliki pesanan.</p>
                    <a href="/Amimi/products.php" class="btn btn-gold">Mulai Belanja</a>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach ($recentOrders as $order):
                        $statusLabel = getOrderStatusLabel($order['order_status']);
                        $payLabel = getPaymentStatusLabel($order['payment_status']);
                    ?>
                        <a href="/Amimi/customer/order_detail.php?id=<?= $order['id'] ?>" style="text-decoration: none; color: inherit; transition: all 0.3s ease;">
                            <div style="background-color: var(--card-dark); border: 1px solid var(--border-dark); border-radius: 10px; padding: 16px; hover-scale: 1.02; cursor: pointer; transition: all 0.3s ease;"
                                 onmouseover="this.style.borderColor='var(--primary-gold)'; this.style.boxShadow='0 4px 12px rgba(212, 175, 55, 0.2)';"
                                 onmouseout="this.style.borderColor='var(--border-dark)'; this.style.boxShadow='none';">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                                    <div>
                                        <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 4px;"><?= $order['order_number'] ?></div>
                                        <div style="font-size: 13px; color: var(--text-muted);"><?= date('d M Y H:i', strtotime($order['created_at'])) ?></div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-weight: 700; color: var(--primary-gold);"><?= formatRupiah($order['total_amount']) ?></div>
                                        <div style="font-size: 12px; margin-top: 4px;">
                                            <span style="display: inline-block; background-color: rgba(212, 175, 55, 0.1); color: var(--primary-gold); padding: 2px 8px; border-radius: 4px;"><?= $order['item_count'] ?> item</span>
                                        </div>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 8px; margin-top: 10px;">
                                    <span class="<?= $statusLabel['class'] ?>" style="font-size: 12px; padding: 4px 10px; border-radius: 4px; background-color: rgba(212, 175, 55, 0.1); color: var(--primary-gold);">
                                        <?= $statusLabel['label'] ?>
                                    </span>
                                    <span class="<?= $payLabel['class'] ?>" style="font-size: 12px; padding: 4px 10px; border-radius: 4px; background-color: rgba(212, 175, 55, 0.1); color: var(--primary-gold);">
                                        <?= $payLabel['label'] ?>
                                    </span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <!-- Profile Card -->
            <div style="background-color: var(--card-dark); border: 1px solid var(--border-dark); border-radius: 12px; padding: 20px; text-align: center;">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="<?= $user['avatar'] ?>" alt="Avatar" style="width: 80px; height: 80px; border-radius: 50%; border: 2px solid var(--primary-gold); margin: 0 auto 15px; object-fit: cover;">
                <?php else: ?>
                    <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #ff5e3a 0%, #e62e00 100%); display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 700; color: white; border: 2px solid var(--primary-gold); margin: 0 auto 15px;">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
                <div style="font-weight: 600; margin-bottom: 4px;"><?= htmlspecialchars($user['name']) ?></div>
                <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 15px;"><?= htmlspecialchars($user['email']) ?></div>
                <a href="/Amimi/customer/profile.php" class="btn btn-gold" style="width: 100%; display: block; padding: 8px; font-size: 13px;">Edit Profil</a>
            </div>

            <!-- Quick Links -->
            <div style="background-color: var(--card-dark); border: 1px solid var(--border-dark); border-radius: 12px; padding: 16px;">
                <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 12px; margin-top: 0;">Aksi Cepat</h3>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <a href="/Amimi/cart/index.php" style="display: flex; align-items: center; gap: 10px; padding: 10px; background-color: rgba(212, 175, 55, 0.1); border-radius: 8px; text-decoration: none; color: var(--text-primary); transition: all 0.3s ease;"
                       onmouseover="this.style.backgroundColor='rgba(212, 175, 55, 0.2)'; this.style.transform='translateX(4px)';"
                       onmouseout="this.style.backgroundColor='rgba(212, 175, 55, 0.1)'; this.style.transform='translateX(0)';">
                        <span style="font-size: 16px;"></span>
                        <span style="font-size: 13px; font-weight: 500;">Keranjang (<?= $cartCount ?>)</span>
                    </a>
                    <a href="/Amimi/products.php" style="display: flex; align-items: center; gap: 10px; padding: 10px; background-color: rgba(212, 175, 55, 0.1); border-radius: 8px; text-decoration: none; color: var(--text-primary); transition: all 0.3s ease;"
                       onmouseover="this.style.backgroundColor='rgba(212, 175, 55, 0.2)'; this.style.transform='translateX(4px)';"
                       onmouseout="this.style.backgroundColor='rgba(212, 175, 55, 0.1)'; this.style.transform='translateX(0)';">
                        <span style="font-size: 16px;"></span>
                        <span style="font-size: 13px; font-weight: 500;">Belanja Produk</span>
                    </a>
                    <a href="/Amimi/customer/orders.php" style="display: flex; align-items: center; gap: 10px; padding: 10px; background-color: rgba(212, 175, 55, 0.1); border-radius: 8px; text-decoration: none; color: var(--text-primary); transition: all 0.3s ease;"
                       onmouseover="this.style.backgroundColor='rgba(212, 175, 55, 0.2)'; this.style.transform='translateX(4px)';"
                       onmouseout="this.style.backgroundColor='rgba(212, 175, 55, 0.1)'; this.style.transform='translateX(0)';">
                        <span style="font-size: 16px;"></span>
                        <span style="font-size: 13px; font-weight: 500;">Semua Pesanan</span>
                    </a>
                </div>
            </div>

            <!-- Info Card -->
            <div style="background-color: rgba(212, 175, 55, 0.1); border: 1px solid rgba(212, 175, 55, 0.3); border-radius: 12px; padding: 16px;">
                <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 12px; margin-top: 0; color: var(--primary-gold);">ℹ Pusat Informasi</h3>
                <div style="font-size: 12px; color: var(--text-muted); line-height: 1.6;">
                    <strong style="color: var(--text-white);"> Jam Operasional:</strong><br>
                    Senin - Jumat: 09:00 - 18:00<br>
                    Sabtu: 10:00 - 17:00<br><br>
                    <strong style="color: var(--text-white);"> Pengiriman:</strong><br>
                    Pesanan sebelum jam 15:00 akan dikirim pada hari yang sama.<br><br>
                    <strong style="color: var(--text-white);"> Bantuan:</strong><br>
                    Gunakan menu <a href="/Amimi/customer/chat.php" style="color: var(--primary-gold);">Chat</a> untuk menghubungi Admin jika ada kendala pesanan.
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.btn-gold {
    background: linear-gradient(135deg, #ff5e3a 0%, #e62e00 100%);
    color: #1a1a1a;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
}

.btn-gold:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(212, 175, 55, 0.3);
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
