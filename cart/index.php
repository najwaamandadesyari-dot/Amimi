<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$userId = $_SESSION['user_id'];

// Get cart items
$stmt = $conn->prepare("SELECT c.*, p.name, p.price, p.stock, p.sizes as product_sizes, cat.name as category_name FROM cart c JOIN products p ON c.product_id = p.id JOIN categories cat ON p.category_id = cat.id WHERE c.user_id = ? ORDER BY c.id DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$cartItems = [];
$totalAmount = 0;

while ($row = $result->fetch_assoc()) {
    $cartItems[] = $row;
    $totalAmount += $row['price'] * $row['quantity'];
}

$pageTitle = 'Keranjang Belanja';
include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <h1 class="cart-title">Keranjang Belanja Anda</h1>

    <?php if (empty($cartItems)): ?>
        <div style="text-align: center; padding: 60px 20px; background-color: var(--card-dark); border: 1px solid var(--border-dark); border-radius: 12px; max-width: 600px; margin: 0 auto;">
            <span style="font-size: 64px;"></span>
            <h3 style="margin-top: 20px;">Keranjang Kuning Anda Kosong</h3>
            <p style="color: var(--text-muted); margin-top: 10px; margin-bottom: 30px;">Yuk, cari pakaian impian Anda atau peralatan rumah tangga sekarang!</p>
            <a href="/Amimi/products.php" class="btn btn-gold">Lihat Produk →</a>
        </div>
    <?php else: ?>
        <form action="/Amimi/checkout/index.php" method="POST" id="cartForm">
            <div class="cart-layout">
                <!-- Cart Items List -->
                <div class="cart-items">
                    <div style="margin-bottom: 15px; padding: 0 10px; display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" id="selectAll" checked style="width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary-gold);">
                        <label for="selectAll" style="font-weight: 600; cursor: pointer;">Pilih Semua</label>
                    </div>
                <?php foreach ($cartItems as $item): ?>
                    <div class="cart-item" id="cart-item-<?= $item['id'] ?>" style="display: flex; align-items: center;">
                        <!-- Checkbox -->
                        <div style="padding: 0 15px 0 10px;">
                            <input type="checkbox" name="selected_items[]" value="<?= $item['id'] ?>" class="item-checkbox" checked style="width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary-gold);" data-price="<?= $item['price'] ?>" data-qty="<?= $item['quantity'] ?>">
                        </div>
                        <!-- Product Icon / Thumbnail fallback -->
                        <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); display: flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--border-dark); flex-shrink: 0;">
                            <span style="font-size: 32px;"><?= $item['product_sizes'] ? '' : '' ?></span>
                        </div>

                        <div class="cart-item-details">
                            <h3 class="cart-item-name">
                                <a href="/Amimi/product_detail.php?id=<?= $item['product_id'] ?>"><?= htmlspecialchars($item['name']) ?></a>
                            </h3>
                            
                            <?php if ($item['size']): ?>
                                <span class="cart-item-size">Ukuran: <?= htmlspecialchars($item['size']) ?></span>
                            <?php endif; ?>

                            <div class="cart-item-price" id="price-<?= $item['id'] ?>" data-price="<?= $item['price'] ?>">
                                <?= formatRupiah($item['price']) ?>
                            </div>
                        </div>

                        <!-- Quantity Modifier -->
                        <div class="qty-selector" style="margin-right: 15px;">
                            <button type="button" class="qty-btn" style="width:28px; height:28px; font-size:14px;" onclick="updateCartQty(<?= $item['id'] ?>, 'minus')">-</button>
                            <input type="text" id="qty-<?= $item['id'] ?>" class="qty-input" style="width:30px; font-size:14px;" value="<?= $item['quantity'] ?>" readonly>
                            <button type="button" class="qty-btn" style="width:28px; height:28px; font-size:14px;" onclick="updateCartQty(<?= $item['id'] ?>, 'plus')">+</button>
                        </div>

                        <!-- Subtotal -->
                        <div style="font-weight: 700; width: 120px; text-align: right;" id="subtotal-<?= $item['id'] ?>">
                            <?= formatRupiah($item['price'] * $item['quantity']) ?>
                        </div>

                        <!-- Remove Button -->
                        <button class="cart-item-remove" onclick="deleteCartItem(<?= $item['id'] ?>)" title="Hapus Produk">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                <line x1="14" y1="11" x2="14" y2="17"></line>
                            </svg>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Cart Summary -->
            <aside class="cart-summary">
                <h3 class="summary-title">Ringkasan Belanja</h3>
                <div class="summary-row">
                    <span>Total Item</span>
                    <span><?= count($cartItems) ?> Produk</span>
                </div>
                <div class="summary-row summary-total">
                    <span>Total Harga</span>
                    <span id="summaryTotal"><?= formatRupiah($totalAmount) ?></span>
                </div>
                <button type="submit" class="btn btn-gold btn-block" style="margin-top: 10px;">Lanjut ke Checkout</button>
            </aside>
        </div>
        </form>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAllBtn = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.item-checkbox');
            const summaryTotalEl = document.getElementById('summaryTotal');
            const form = document.getElementById('cartForm');

            function calculateTotal() {
                let total = 0;
                let allChecked = true;
                checkboxes.forEach(cb => {
                    if (cb.checked) {
                        total += parseFloat(cb.dataset.price) * parseInt(cb.dataset.qty);
                    } else {
                        allChecked = false;
                    }
                });
                selectAllBtn.checked = allChecked && checkboxes.length > 0;
                summaryTotalEl.innerText = formatRupiah(total);
            }

            selectAllBtn.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = this.checked);
                calculateTotal();
            });

            checkboxes.forEach(cb => {
                cb.addEventListener('change', calculateTotal);
            });

            // Re-calculate when qty changes from AJAX
            const originalUpdateCartQty = window.updateCartQty;
            window.updateCartQty = function(cartId, action) {
                const input = document.getElementById(`qty-${cartId}`);
                if (!input) return;
                
                let qty = parseInt(input.value);
                if (action === 'plus') qty++;
                else if (action === 'minus') qty--;
                
                if (qty < 1) return;

                fetch('/Amimi/cart/update.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `cart_id=${cartId}&quantity=${qty}`
                }).then(res => res.json()).then(data => {
                    if (data.success) {
                        input.value = qty;
                        const cb = document.querySelector(`.item-checkbox[value="${cartId}"]`);
                        if (cb) cb.dataset.qty = qty;
                        
                        const price = parseFloat(document.getElementById(`price-${cartId}`).dataset.price);
                        const subtotalEl = document.getElementById(`subtotal-${cartId}`);
                        if (subtotalEl) subtotalEl.innerText = formatRupiah(price * qty);
                        
                        calculateTotal();
                        
                        const cartBadge = document.getElementById('cartBadge');
                        if (cartBadge) {
                            if (data.cart_count > 0) cartBadge.innerText = data.cart_count;
                            else cartBadge.remove();
                        }
                    }
                });
            };

            form.addEventListener('submit', function(e) {
                let anyChecked = false;
                checkboxes.forEach(cb => { if (cb.checked) anyChecked = true; });
                if (!anyChecked) {
                    e.preventDefault();
                    alert('Silakan pilih minimal 1 produk untuk dicheckout.');
                }
            });
            
            function formatRupiah(number) {
                return 'Rp ' + new Intl.NumberFormat('id-ID').format(number);
            }
        });
        </script>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
