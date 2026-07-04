<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$userId = $_SESSION['user_id'];

// Get user profile details for autofill
$user = getUserById($conn, $userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_items'])) {
    $_SESSION['checkout_items'] = $_POST['selected_items'];
}

$selectedItems = $_SESSION['checkout_items'] ?? [];
if (empty($selectedItems)) {
    setFlash('error', 'Silakan pilih produk untuk dicheckout.');
    redirect('/Amimi/cart/index.php');
}

// Fetch cart items
$placeholders = implode(',', array_fill(0, count($selectedItems), '?'));
$types = "i" . str_repeat('i', count($selectedItems));
$params = array_merge([$userId], $selectedItems);

$stmt = $conn->prepare("SELECT c.*, p.name, p.price, p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ? AND c.id IN ($placeholders)");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$cartItems = [];
$subtotal = 0;

while ($row = $result->fetch_assoc()) {
    $cartItems[] = $row;
    $subtotal += $row['price'] * $row['quantity'];
}

// Hitung diskon: Belanja > 500rb diskon 50rb atau 10% (mana yang lebih besar)
$discount = 0;
if ($subtotal > 500000) {
    $discount10 = $subtotal * 0.10;
    $discount = max(50000, $discount10);
}

// Generate kode unik
if (!isset($_SESSION['checkout_unique_code']) || $_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['checkout_unique_code'] = rand(100, 999);
}
$uniqueCode = $_SESSION['checkout_unique_code'];

// Redirect if cart empty
if (empty($cartItems)) {
    setFlash('error', 'Keranjang belanja Anda kosong.');
    redirect('/Amimi/cart/index.php');
}

$pageTitle = 'Checkout';
include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding: 20px; max-width: 1200px;">
    <div style="margin-bottom: 30px;">
        <h1 style="font-size: 32px; font-weight: 700; margin-bottom: 8px;">
            Checkout Pesanan
        </h1>
        <p style="color: var(--text-muted);">Selesaikan pembelian Anda dalam beberapa langkah mudah</p>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 350px; gap: 25px;">
        <!-- Left Column: Form -->
        <div>
            <form action="/Amimi/checkout/process.php" method="POST" id="checkoutForm">
                <?= csrfField() ?>
                
                <!-- Shipping Info -->
                <div class="checkout-section">
                    <h3 class="checkout-section-title"> Informasi Pengiriman</h3>
                    
                    <div class="form-group">
                        <label class="form-label" for="shipping_name">Nama Penerima</label>
                        <input type="text" name="shipping_name" id="shipping_name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="shipping_phone">No. Telepon</label>
                        <input type="text" name="shipping_phone" id="shipping_phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="shipping_address">Alamat Lengkap Pengiriman</label>
                        <textarea name="shipping_address" id="shipping_address" class="form-control" rows="3" required><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="shipping_method">Pilih Pengiriman</label>
                        <select name="shipping_method" id="shippingMethod" class="form-control" onchange="calculateShipping()" required>
                            <option value="pickup" data-cost="0">Ambil Sendiri di Toko (Gratis)</option>
                            <option value="jnt_reg" data-cost="10000">J&T Reguler - Dalam Kota (Rp 10.000)</option>
                            <option value="jnt_express" data-cost="20000">J&T Express - Luar Kota (Rp 20.000)</option>
                            <option value="jnt_reg_luar_pulau" data-cost="35000">J&T Reguler - Luar Pulau (Rp 35.000)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="notes">Catatan Pesanan (Opsional)</label>
                        <input type="text" name="notes" id="notes" class="form-control" placeholder="Contoh: Titip tetangga jika rumah kosong">
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="checkout-section">
                    <h3 class="checkout-section-title"> Metode Pembayaran</h3>
                    <input type="hidden" name="payment_method" id="selectedPaymentMethod" value="" required>

                    <div class="payment-methods">
                        <!-- COD -->
                        <div class="payment-method-card" data-method="cod" onclick="selectPayment('cod')">
                            <input type="radio" name="pay_radio" id="pay_cod" style="margin-top: 4px; pointer-events: none;">
                            <div style="flex-grow: 1;">
                                <div class="payment-method-title"> Bayar di Tempat (COD) [Pre-Order]</div>
                                <div class="payment-method-desc">Sistem Pre-Order. Pembayaran tunai dilakukan secara langsung kepada kurir saat barang sampai di alamat Anda.</div>
                            </div>
                        </div>

                        <!-- M-Banking -->
                        <div class="payment-method-card" data-method="mbanking" onclick="selectPayment('mbanking')">
                            <input type="radio" name="pay_radio" id="pay_mbanking" style="margin-top: 4px; pointer-events: none;">
                            <div style="flex-grow: 1;">
                                <div class="payment-method-title"> M-Banking / Transfer Bank</div>
                                <div class="payment-method-desc">Transfer bank menggunakan Virtual Account. Konfirmasi pembayaran otomatis atau via dashboard.</div>
                            </div>
                        </div>

                        <!-- E-Wallet -->
                        <div class="payment-method-card" data-method="ewallet" onclick="selectPayment('ewallet')">
                            <input type="radio" name="pay_radio" id="pay_ewallet" style="margin-top: 4px; pointer-events: none;">
                            <div style="flex-grow: 1;">
                                <div class="payment-method-title"> E-Wallet (OVO / GoPay / ShopeePay / QRIS)</div>
                                <div class="payment-method-desc">Bayar instan menggunakan saldo dompet digital Anda atau scan QRIS yang disediakan.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Simulation Box (Shown dynamically) -->
                    <div id="paymentSimulation" class="payment-sim-box" style="display: none;">
                        <h4 style="margin-bottom: 10px; color: var(--primary-gold);" id="simTitle">Instruksi Pembayaran</h4>
                        <div id="simInstruction" style="font-size: 14px; color: var(--text-muted); line-height: 1.6;"></div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Order Summary -->
            <aside>
                <div class="cart-summary">
                    <h3 class="summary-title">Ringkasan Pesanan</h3>
                    
                    <div style="max-height: 200px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; margin-bottom: 15px; border-bottom: 1px solid var(--border-dark); padding-bottom: 15px;">
                        <?php foreach ($cartItems as $item): ?>
                            <div style="display: flex; justify-content: space-between; font-size: 13px;">
                                <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 180px;">
                                    <?= htmlspecialchars($item['name']) ?> 
                                    <?= $item['size'] ? '<small style="color:var(--accent-yellow)">(' . $item['size'] . ')</small>' : '' ?>
                                </span>
                                <span style="color: var(--text-muted);"><?= $item['quantity'] ?>x</span>
                                <span style="font-weight: 600;"><?= formatRupiah($item['price'] * $item['quantity']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-row">
                        <span>Subtotal Produk</span>
                        <span><?= formatRupiah($subtotal) ?></span>
                    </div>
                    
                    <?php if ($discount > 0): ?>
                    <div class="summary-row" style="color: var(--danger);">
                        <span>Diskon (Amimi Promo)</span>
                        <span>- <?= formatRupiah($discount) ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="summary-row">
                        <span>Ongkos Kirim</span>
                        <span id="shippingCostEl" style="color: var(--text-white); font-weight: 600;">Rp 0</span>
                    </div>

                    <div class="summary-row" style="color: var(--primary-gold);">
                        <span>Kode Unik</span>
                        <span>+ Rp <?= number_format($uniqueCode, 0, ',', '.') ?></span>
                    </div>

                    <div class="summary-row summary-total">
                        <span>Total Bayar</span>
                        <span id="finalTotalEl"><?= formatRupiah($subtotal - $discount + $uniqueCode) ?></span>
                    </div>

                    <button type="submit" class="btn btn-gold btn-block" style="margin-top: 15px;">Buat Pesanan Sekarang </button>
                </div>
            </aside>
        </div>
    </form>
</div>


<style>
    .checkout-section {
        background-color: var(--card-dark);
        border: 1px solid var(--border-dark);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .checkout-section-title {
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 16px;
        color: var(--text-primary);
    }

    .payment-method-card {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px;
        border: 2px solid var(--border-dark);
        border-radius: 8px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .payment-method-card:hover {
        border-color: var(--primary-gold);
        background-color: rgba(212, 175, 55, 0.05);
    }

    .payment-method-card.active {
        border-color: var(--primary-gold);
        background-color: rgba(212, 175, 55, 0.1);
    }

    .payment-method-title {
        font-weight: 600;
        font-size: 13px;
        margin-bottom: 4px;
    }

    .payment-method-desc {
        font-size: 12px;
        color: var(--text-muted);
        line-height: 1.5;
    }

    .payment-sim-box {
        background-color: rgba(212, 175, 55, 0.05);
        border: 1px solid rgba(212, 175, 55, 0.3);
        border-radius: 8px;
        padding: 15px;
        margin-top: 15px;
        font-size: 13px;
    }

    .cart-summary {
        background-color: var(--card-dark);
        border: 1px solid var(--border-dark);
        border-radius: 12px;
        padding: 20px;
    }

    .summary-title {
        font-size: 14px;
        font-weight: 700;
        margin-bottom: 15px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--primary-gold);
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 12px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--border-dark);
        font-size: 13px;
    }

    .summary-row:last-of-type {
        border-bottom: none;
    }

    .summary-total {
        font-weight: 700;
        font-size: 16px;
        border-top: 1px solid var(--border-dark);
        border-bottom: none;
        padding-top: 12px;
        padding-bottom: 15px;
        margin-bottom: 0;
    }
</style>

<script>
function selectPayment(method) {
    // Set hidden field value
    document.getElementById('selectedPaymentMethod').value = method;
    
    // Select radio button
    document.getElementById('pay_' + method).checked = true;
    
    // Update active class on cards
    document.querySelectorAll('.payment-method-card').forEach(card => {
        card.classList.remove('active');
    });
    document.querySelector(`[data-method="${method}"]`).classList.add('active');

    // Show simulation instructions
    const simBox = document.getElementById('paymentSimulation');
    const simTitle = document.getElementById('simTitle');
    const simInstruction = document.getElementById('simInstruction');
    
    simBox.style.display = 'block';

    if (method === 'cod') {
        simTitle.innerHTML = ' Info Bayar di Tempat (COD)';
        simInstruction.innerHTML = 'Pesanan Anda bertipe <strong>Pre-Order</strong>. Admin akan memproses pesanan dan mengirimkannya ke alamat Anda. Siapkan uang tunai sesuai <strong>Total Bayar</strong> untuk diserahkan ke kurir saat paket tiba.';
    } else if (method === 'mbanking') {
        simTitle.innerHTML = ' Simulasi Transfer Virtual Account';
        simInstruction.innerHTML = 'Silakan transfer tepat sejumlah <strong>Total Bayar</strong> (termasuk kode unik) ke rekening berikut:<br>' +
                                    '<strong>Bank BCA Virtual Account:</strong> 8892 0812 3456 7890<br>' +
                                    '<strong>Atas Nama:</strong> AMIMI SHOP INDONESIA<br><br>' +
                                    '<span style="font-size:12px; color:var(--primary-gold);">*Catatan: Ini adalah simulasi transaksi. Status pembayaran akan otomatis disetujui setelah Anda membuat pesanan.</span>';
    } else if (method === 'ewallet') {
        simTitle.innerHTML = ' Simulasi Scan QRIS E-Wallet';
        simInstruction.innerHTML = 'Scan QRIS Amimi Shop di bawah ini menggunakan aplikasi OVO, GoPay, Dana, LinkAja, atau ShopeePay Anda:<br><br>' +
                                    '<div style="text-align:center; padding:15px; background:white; width:180px; margin:0 auto; border-radius:8px;">' +
                                    '<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" style="width:100%; height:auto;">' +
                                    '<rect width="100" height="100" fill="#fff"/>' +
                                    '<path d="M10,10 h20 v20 h-20 z M15,15 h10 v10 h-10 z" fill="#000"/>' +
                                    '<path d="M70,10 h20 v20 h-20 z M75,15 h10 v10 h-10 z" fill="#000"/>' +
                                    '<path d="M10,70 h20 v20 h-20 z M15,75 h10 v10 h-10 z" fill="#000"/>' +
                                    '<rect x="40" y="10" width="10" height="10" fill="#000"/>' +
                                    '<rect x="55" y="20" width="10" height="10" fill="#000"/>' +
                                    '<rect x="45" y="30" width="20" height="10" fill="#000"/>' +
                                    '<rect x="10" y="40" width="10" height="20" fill="#000"/>' +
                                    '<rect x="30" y="45" width="20" height="10" fill="#000"/>' +
                                    '<rect x="60" y="40" width="10" height="10" fill="#000"/>' +
                                    '<rect x="80" y="40" width="10" height="20" fill="#000"/>' +
                                    '<rect x="40" y="60" width="10" height="10" fill="#000"/>' +
                                    '<rect x="60" y="60" width="10" height="10" fill="#000"/>' +
                                    '<rect x="70" y="70" width="20" height="10" fill="#000"/>' +
                                    '<rect x="40" y="80" width="20" height="10" fill="#000"/>' +
                                    '<rect x="75" y="85" width="15" height="5" fill="#000"/>' +
                                    '</svg>' +
                                    '<div style="margin-top:8px; font-weight:bold; color:black; font-size:14px;">QRIS Amimi</div>' +
                                    '</div><br>' +
                                    '<span style="font-size:12px; color:var(--primary-gold);">*Catatan: Pastikan nominal transfer sesuai dengan Total Bayar (termasuk kode unik).</span>';
    }
}

// Calculate total dynamically
const subtotal = <?= $subtotal ?>;
const discount = <?= $discount ?>;
const uniqueCode = <?= $uniqueCode ?>;

function calculateShipping() {
    const select = document.getElementById('shippingMethod');
    const cost = parseInt(select.options[select.selectedIndex].dataset.cost);
    
    document.getElementById('shippingCostEl').innerText = formatRupiah(cost);
    
    const finalTotal = subtotal - discount + cost + uniqueCode;
    document.getElementById('finalTotalEl').innerText = formatRupiah(finalTotal);
}

function formatRupiah(number) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(number);
}

// Init shipping calc on load
document.addEventListener('DOMContentLoaded', function() {
    calculateShipping();
});

// Form validation
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    const method = document.getElementById('selectedPaymentMethod').value;
    if (!method) {
        e.preventDefault();
        alert('Silakan pilih metode pembayaran terlebih dahulu!');
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
