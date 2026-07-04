<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    setFlash('error', 'Produk tidak ditemukan.');
    redirect('/Amimi/products.php');
}

// Fetch product details
$stmt = $conn->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ? AND p.is_active = 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    setFlash('error', 'Produk tidak ditemukan atau tidak aktif.');
    redirect('/Amimi/products.php');
}

$sizeStocks = [];
if ($product['sizes']) {
    $stmtSize = $conn->prepare("SELECT size, stock FROM product_sizes WHERE product_id = ?");
    $stmtSize->bind_param("i", $id);
    $stmtSize->execute();
    $resSize = $stmtSize->get_result();
    while ($rs = $resSize->fetch_assoc()) {
        $sizeStocks[$rs['size']] = $rs['stock'];
    }
}

$pageTitle = $product['name'];
include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div style="margin-bottom: 20px;">
        <a href="javascript:history.back()" style="color: var(--text-muted); font-size: 14px;">← Kembali</a>
    </div>

    <div class="product-detail-grid">
        <!-- Product Image -->
        <div class="detail-img-container">
            <?php 
            $imgExists = false;
            if (!empty($product['image'])) {
                $physicalPath = __DIR__ . '/uploads/products/' . $product['image'];
                if (file_exists($physicalPath)) {
                    $imgExists = true;
                }
            }
            ?>
            <?php if ($imgExists): ?>
                <img src="/Amimi/uploads/products/<?= htmlspecialchars($product['image']) ?>" 
                     alt="<?= htmlspecialchars($product['name']) ?>" 
                     class="detail-img">
            <?php else: ?>
                <div style="width: 100%; height: 350px; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); display: flex; flex-direction: column; align-items: center; justify-content: center; border-radius: 12px; border: 1px solid var(--border-dark);">
                    <span style="font-size: 80px;"><?= $product['category_id'] == 1 ? '' : ($product['category_id'] == 2 ? '' : ($product['category_id'] == 3 ? '' : ($product['category_id'] == 4 ? '' : ($product['category_id'] == 5 ? '' : '')))) ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Product Details -->
        <div class="detail-info">
            <div>
                <span class="detail-category"><?= htmlspecialchars($product['category_name']) ?></span>
                <h1 class="detail-title"><?= htmlspecialchars($product['name']) ?></h1>
            </div>

            <div class="detail-price"><?= formatRupiah($product['price']) ?></div>

            <div class="detail-meta-row">
                <span> Stok: <strong><?= $product['stock'] ?></strong></span>
                <span>Kondisi: <strong>Baru</strong></span>
            </div>

            <!-- Description -->
            <div>
                <h4 style="margin-bottom: 8px;">Deskripsi Produk</h4>
                <p style="color: var(--text-muted); font-size: 14px; line-height: 1.7; white-space: pre-line;">
                    <?= htmlspecialchars($product['description'] ?: 'Tidak ada deskripsi.') ?>
                </p>
            </div>

            <!-- Add to Cart Form -->
            <?php if ($product['stock'] > 0): ?>
                <form action="/Amimi/cart/add.php" method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    
                    <!-- Size Selector (if applicable) -->
                    <?php if ($product['sizes']): 
                        $sizes = explode(',', $product['sizes']);
                    ?>
                        <div style="margin-bottom: 20px;">
                            <h4 style="margin-bottom: 8px;">Pilih Ukuran</h4>
                            <input type="hidden" name="size" id="selectedSize" value="" required>
                            <div class="size-selector">
                                <?php foreach ($sizes as $sz): 
                                    $sz = trim($sz); 
                                    $szStock = isset($sizeStocks[$sz]) ? $sizeStocks[$sz] : 0;
                                    $disabledClass = $szStock <= 0 ? 'disabled' : '';
                                ?>
                                    <button type="button" class="size-btn <?= $disabledClass ?>" data-size="<?= htmlspecialchars($sz) ?>" data-stock="<?= $szStock ?>" <?= $szStock <= 0 ? 'disabled' : '' ?>>
                                        <?= htmlspecialchars($sz) ?>
                                        <?php if ($szStock <= 0): ?>
                                            <small style="display:block; font-size:10px; color:#ff5252;">Habis</small>
                                        <?php else: ?>
                                            <small style="display:block; font-size:10px; color:var(--primary-gold);">Sisa <?= $szStock ?></small>
                                        <?php endif; ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Quantity Selector & Action Button -->
                    <div style="margin-bottom: 20px;">
                        <h4 style="margin-bottom: 8px;">Jumlah</h4>
                        <div class="qty-selector">
                            <button type="button" class="qty-btn" onclick="adjustQty(-1)">-</button>
                            <input type="text" name="quantity" id="qtyInput" class="qty-input" value="1" readonly>
                            <button type="button" class="qty-btn" onclick="adjustQty(1)">+</button>
                        </div>
                    </div>

                    <div class="add-to-cart-section">
                        <button type="submit" class="btn btn-gold btn-block" style="flex-grow: 1;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 5px;">
                                <circle cx="9" cy="21" r="1"></circle>
                                <circle cx="20" cy="21" r="1"></circle>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                            Tambah ke Keranjang Kuning 
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div style="padding: 15px; background-color: rgba(255, 82, 82, 0.1); border: 1px solid var(--danger); border-radius: 8px; color: var(--danger); font-weight: 600; text-align: center;">
                    Habis Terjual 
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
let maxStock = <?= $product['sizes'] ? 0 : $product['stock'] ?>; // If sizes exist, maxStock is 0 until size selected

function adjustQty(amount) {
    const input = document.getElementById('qtyInput');
    const sizeInput = document.getElementById('selectedSize');
    
    if (<?= $product['sizes'] ? 'true' : 'false' ?> && sizeInput.value === '') {
        alert('Silakan pilih ukuran baju terlebih dahulu!');
        return;
    }
    
    let qty = parseInt(input.value) + amount;
    if (qty < 1) qty = 1;
    if (qty > maxStock) {
        qty = maxStock;
        alert('Maksimal stok yang tersedia adalah ' + maxStock);
    }
    input.value = qty;
}

// Handle size selection
document.querySelectorAll('.size-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        if(this.hasAttribute('disabled')) {
            e.stopPropagation();
            return;
        }
        
        document.querySelectorAll('.size-btn').forEach(b => b.classList.remove('selected'));
        this.classList.add('selected');
        document.getElementById('selectedSize').value = this.dataset.size;
        
        maxStock = parseInt(this.dataset.stock);
        
        const qtyInput = document.getElementById('qtyInput');
        if(parseInt(qtyInput.value) > maxStock) {
            qtyInput.value = maxStock;
        } else if (parseInt(qtyInput.value) < 1 && maxStock > 0) {
            qtyInput.value = 1;
        }
    });
});

// Intercept form submit to validate size
document.querySelector('form')?.addEventListener('submit', function(e) {
    const sizeInput = document.getElementById('selectedSize');
    if (sizeInput && sizeInput.value === '') {
        e.preventDefault();
        alert('Silakan pilih ukuran baju terlebih dahulu!');
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
