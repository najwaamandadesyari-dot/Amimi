<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Get products for homepage
$productsQuery = "SELECT p.*, c.name as category_name FROM products p 
                  JOIN categories c ON p.category_id = c.id 
                  WHERE p.is_active = 1 AND p.stock > 0
                  ORDER BY p.created_at DESC
                  LIMIT 12";
$productsResult = $conn->query($productsQuery);
$products = [];
while ($row = $productsResult->fetch_assoc()) {
    $products[] = $row;
}

// Check user role
$isAdmin = isAdmin();
$isCustomer = isLoggedIn() && !$isAdmin;
$isVisitor = !isLoggedIn();

$pageTitle = 'Beranda - Amimi Shop';

include __DIR__ . '/includes/header.php';
?>

<!-- ============================================
     HOMEPAGE
     ============================================ -->
<style>
    .homepage-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .hero-banner {
        background: #fff7f2;
        border: 1px solid #ffd4bf;
        border-radius: 16px;
        padding: 40px 24px;
        margin: 20px 0 28px;
        text-align: center;
        color: #222;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
    }

    .hero-banner h1 {
        font-size: 34px;
        font-weight: 800;
        margin-bottom: 12px;
    }

    .hero-banner p {
        font-size: 16px;
        color: #666;
        margin-bottom: 20px;
    }

    .hero-actions {
        display: flex;
        justify-content: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin-bottom: 32px;
    }

    .info-card {
        background: #fff;
        border: 1px solid #ececec;
        border-radius: 12px;
        padding: 18px;
    }

    .info-card h3 {
        font-size: 15px;
        margin-bottom: 6px;
        color: #e62e00;
    }

    .info-card p {
        font-size: 13px;
        color: #666;
    }

    .promo-section {
        margin-bottom: 32px;
    }

    .promo-section-title {
        font-size: 22px;
        font-weight: 700;
        margin-bottom: 16px;
    }
</style>

<div class="homepage-container">
    <div class="hero-banner">
        <h1>Selamat Datang di Amimi Shop</h1>
        <p>Belanja kebutuhan sehari-hari dengan harga bersahabat, proses mudah, dan produk yang selalu tersedia.</p>
        <div class="hero-actions">
            <?php if ($isVisitor): ?>
                <a href="/Amimi/auth/login.php" class="btn btn-gold">Masuk</a>
                <a href="/Amimi/auth/register.php" class="btn btn-outline">Daftar</a>
            <?php elseif ($isAdmin): ?>
                <a href="/Amimi/admin/product_form.php" class="btn btn-outline">Tambah Produk</a>
                <a href="/Amimi/admin/orders.php" class="btn btn-gold">Lihat Pesanan</a>
            <?php else: ?>
                <a href="/Amimi/shop.php" class="btn btn-gold">Mulai Belanja</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-card">
            <h3>Pengiriman Cepat</h3>
            <p>Pesanan kami kirim dengan cepat ke seluruh Indonesia.</p>
        </div>
        <div class="info-card">
            <h3>Pembayaran Aman</h3>
            <p>Transaksi aman dan mudah dengan berbagai metode pembayaran.</p>
        </div>
        <div class="info-card">
            <h3>Produk Terpercaya</h3>
            <p>Kami sediakan produk berkualitas dengan harga yang bersaing.</p>
        </div>
    </div>

    <div class="promo-section">
        <div class="promo-section-title">Produk Terbaru</div>

        <?php if (!empty($products)): ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
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
                                     style="width:100%; height:100%; object-fit:cover; position:absolute; top:0; left:0;">
                            <?php else: ?>
                                <span><?= htmlspecialchars($product['category_name']) ?></span>
                            <?php endif; ?>
                            <?php if ($product['stock'] <= 5 && $product['stock'] > 0): ?>
                                <div class="product-sale-badge">Stok Terbatas</div>
                            <?php elseif ($product['stock'] == 0): ?>
                                <div class="product-sale-badge" style="background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);">Habis</div>
                            <?php endif; ?>
                            <div class="product-stock-badge"><?= $product['stock'] ?> tersedia</div>
                        </div>

                        <div class="product-info">
                            <div class="product-category"><?= htmlspecialchars($product['category_name']) ?></div>
                            <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                            <div class="product-price"><?= formatRupiah($product['price']) ?></div>

                            <div class="product-actions" style="margin-top: 10px;">
                                <a href="/Amimi/product_detail.php?id=<?= $product['id'] ?>" class="btn-quick-view">Lihat</a>
                                <?php if ($isVisitor): ?>
                                    <a href="/Amimi/auth/login.php" class="btn-add-cart" style="display: flex; align-items: center; justify-content: center; text-decoration: none;">Beli</a>
                                <?php elseif ($isAdmin): ?>
                                    <a href="/Amimi/admin/product_form.php?id=<?= $product['id'] ?>" class="btn-add-cart" style="display: flex; align-items: center; justify-content: center; text-decoration: none; background: rgba(255,255,255,0.1); color: var(--text-primary); border: 1px solid var(--border-dark);">Edit</a>
                                <?php else: ?>
                                    <button class="btn-add-cart" onclick="addToCartQuick(<?= $product['id'] ?>, '<?= htmlspecialchars($product['name']) ?>')" <?= $product['stock'] == 0 ? 'disabled style="opacity:0.5;"' : '' ?>>
                                        <?= $product['stock'] == 0 ? 'Habis' : 'Beli' ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-title">Belum Ada Produk</div>
                <p class="empty-state-desc">Produk akan segera tersedia. Silakan kembali lagi nanti.</p>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($isVisitor): ?>
        <div class="hero-banner" style="margin-top: 0; background: #fff4ec; border-color: #f2c5a8;">
            <h2 style="font-size: 24px; margin-bottom: 8px;">Siap berbelanja?</h2>
            <p>Daftar sekarang untuk menikmati pengalaman belanja yang lebih mudah.</p>
            <a href="/Amimi/auth/register.php" class="btn btn-gold">Daftar Gratis</a>
        </div>
    <?php endif; ?>
</div>

<script>
function addToCartQuick(productId, productName) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/Amimi/cart/add.php';
    
    form.innerHTML = `
        <input type="hidden" name="product_id" value="${productId}">
        <input type="hidden" name="quantity" value="1">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    `;
    
    document.body.appendChild(form);
    form.submit();
}
</script>



<?php include __DIR__ . '/includes/footer.php'; ?>
