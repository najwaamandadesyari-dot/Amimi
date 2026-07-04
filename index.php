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
    .hero-banner {
        background: linear-gradient(135deg, #ff5e3a 0%, #e62e00 100%);
        border-radius: 16px;
        padding: 60px 40px;
        margin: 30px 0;
        text-align: center;
        color: #1a1a1a;
        box-shadow: 0 8px 32px rgba(212, 175, 55, 0.3);
    }

    .hero-banner h1 {
        font-size: 42px;
        font-weight: 800;
        margin-bottom: 12px;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .hero-banner p {
        font-size: 18px;
        margin-bottom: 25px;
        opacity: 0.95;
        font-weight: 500;
    }

    .promo-section {
        margin-bottom: 50px;
    }

    .promo-section-title {
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .promo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 50px;
    }

    .promo-card {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        border: 2px solid var(--border-dark);
        border-radius: 12px;
        padding: 30px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .promo-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(212, 175, 55, 0.2) 0%, transparent 100%);
        transition: left 0.3s ease;
    }

    .promo-card:hover {
        border-color: var(--primary-gold);
        box-shadow: 0 8px 24px rgba(212, 175, 55, 0.2);
        transform: translateY(-4px);
    }

    .promo-card:hover::before {
        left: 100%;
    }

    .promo-card-icon {
        font-size: 48px;
        margin-bottom: 12px;
    }

    .promo-card-title {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 8px;
        color: var(--primary-gold);
    }

    .promo-card-desc {
        font-size: 13px;
        color: var(--text-muted);
        line-height: 1.6;
    }

    .homepage-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .features-section {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 40px;
    }

    .feature-item {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        padding: 16px;
        background-color: rgba(212, 175, 55, 0.05);
        border-radius: 8px;
    }

    .feature-icon {
        font-size: 28px;
        flex-shrink: 0;
    }

    .feature-text h3 {
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 4px;
        color: var(--primary-gold);
    }

    .feature-text p {
        font-size: 12px;
        color: var(--text-muted);
    }
</style>

<div class="homepage-container">
    <!-- Hero Banner / Promosi Utama -->
    <?php 
    $flyerPath = __DIR__ . '/uploads/promotions/flyer.jpg';
    if (file_exists($flyerPath)): 
    ?>
    <div style="margin: 30px 0; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 32px rgba(212, 175, 55, 0.3); position: relative;">
        <img src="/Amimi/uploads/promotions/flyer.jpg?t=<?= time() ?>" alt="Promosi Amimi Shop" style="width: 100%; display: block;">
        <?php if ($isAdmin): ?>
            <div style="position: absolute; bottom: 20px; right: 20px;">
                <a href="/Amimi/admin/promo_form.php" class="btn btn-gold" style="box-shadow: 0 4px 12px rgba(0,0,0,0.5);"> Ganti Flayer</a>
            </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="hero-banner">
        <h1> SELAMAT DATANG DI AMIMI SHOP</h1>
        <p>Temukan ribuan produk pilihan dengan harga terbaik dan kualitas terjamin</p>
    </div>
    <?php endif; ?>

    <!-- Action Buttons -->
    <div style="display: flex; gap: 12px; justify-content: center; margin-bottom: 40px;">
        <?php if ($isVisitor): ?>
            <a href="/Amimi/auth/login.php" class="btn btn-gold">Masuk Sekarang</a>
            <a href="/Amimi/auth/register.php" class="btn btn-outline">Daftar Gratis</a>
        <?php elseif ($isAdmin): ?>
            <a href="/Amimi/admin/product_form.php" class="btn btn-outline"> Tambah Produk</a>
            <a href="/Amimi/admin/promo_form.php" class="btn btn-gold"> Kelola Flayer</a>
        <?php else: ?>
            <a href="/Amimi/shop.php" class="btn btn-gold">Mulai Belanja</a>
        <?php endif; ?>
    </div>

    <!-- Features Section -->
    <div class="features-section">
        <div class="feature-item">
            <div class="feature-icon"></div>
            <div class="feature-text">
                <h3>Pengiriman Gratis</h3>
                <p>Ke seluruh Indonesia tanpa biaya tambahan</p>
            </div>
        </div>
        <div class="feature-item">
            <div class="feature-icon"></div>
            <div class="feature-text">
                <h3>100% Aman</h3>
                <p>Transaksi terjamin dan data pribadi terlindungi</p>
            </div>
        </div>
        <div class="feature-item">
            <div class="feature-icon"></div>
            <div class="feature-text">
                <h3>Produk Berkualitas</h3>
                <p>Pilihan terbaik dengan rating tinggi</p>
            </div>
        </div>
        <div class="feature-item">
            <div class="feature-icon"></div>
            <div class="feature-text">
                <h3>Customer Service</h3>
                <p>Siap membantu Anda 24/7</p>
            </div>
        </div>
    </div>

    <!-- Promosi Spesial Cards -->
    <div class="promo-section">
        <div class="promo-section-title"> Promosi Spesial Hari Ini</div>
        <div class="promo-grid">
            <div class="promo-card">
                <div class="promo-card-icon"></div>
                <div class="promo-card-title">Diskon Hingga 50%</div>
                <div class="promo-card-desc">Dapatkan diskon spesial untuk produk pilihan setiap harinya</div>
            </div>
            <div class="promo-card">
                <div class="promo-card-icon"></div>
                <div class="promo-card-title">Flash Sale Pukul 12 Siang</div>
                <div class="promo-card-desc">Jangan lewatkan flash sale setiap hari dengan penawaran terbatas</div>
            </div>
            <div class="promo-card">
                <div class="promo-card-icon"></div>
                <div class="promo-card-title">Gratis Ongkir Sepuasnya</div>
                <div class="promo-card-desc">Belanja berapa pun, pengiriman gratis ke seluruh nusantara</div>
            </div>
            <div class="promo-card">
                <div class="promo-card-icon"></div>
                <div class="promo-card-title">Poin Rewards</div>
                <div class="promo-card-desc">Setiap pembelian dapat poin yang bisa ditukar dengan produk</div>
            </div>
        </div>
    </div>

    <!-- Produk Terbaru / Featured Products -->
    <div class="promo-section">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div class="promo-section-title" style="margin-bottom: 0;">Produk Terbaru & Tersedia</div>
            <?php if (!$isVisitor): ?>
                <a href="/Amimi/shop.php" style="color: var(--primary-gold); text-decoration: none; font-weight: 600;">Lihat Semua →</a>
            <?php endif; ?>
        </div>

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
                                <span><?= $product['category_id'] == 1 ? '' : ($product['category_id'] == 2 ? '' : ($product['category_id'] == 3 ? '' : ($product['category_id'] == 4 ? '' : ($product['category_id'] == 5 ? '' : '')))) ?></span>
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

                            <div class="product-footer">
                                <div class="product-rating">
                                    <span> 4.8</span>
                                    <span style="color: var(--text-muted);">(24)</span>
                                </div>
                            </div>

                            <div class="product-actions" style="margin-top: 10px;">
                                <a href="/Amimi/product_detail.php?id=<?= $product['id'] ?>" class="btn-quick-view">Lihat</a>
                                <?php if ($isVisitor): ?>
                                    <a href="/Amimi/auth/login.php" class="btn-add-cart" style="display: flex; align-items: center; justify-content: center; text-decoration: none;">Beli</a>
                                <?php elseif ($isAdmin): ?>
                                    <a href="/Amimi/admin/product_form.php?id=<?= $product['id'] ?>" class="btn-add-cart" style="display: flex; align-items: center; justify-content: center; text-decoration: none; background: rgba(255,255,255,0.1); color: var(--text-primary); border: 1px solid var(--border-dark);"> Edit</a>
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
                <div class="empty-state-icon"></div>
                <div class="empty-state-title">Belum Ada Produk</div>
                <p class="empty-state-desc">Produk akan segera tersedia. Silakan kembali lagi nanti.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- CTA Section untuk Visitor -->
    <?php if ($isVisitor): ?>
        <div style="background: linear-gradient(135deg, #ff5e3a 0%, #e62e00 100%); border-radius: 16px; padding: 40px; text-align: center; margin-bottom: 30px; color: #1a1a1a;">
            <h2 style="font-size: 28px; font-weight: 700; margin-bottom: 12px;">Siap Berbelanja?</h2>
            <p style="font-size: 16px; margin-bottom: 20px; opacity: 0.95;">Daftar sekarang dan nikmati kemudahan berbelanja dengan berbagai pilihan produk berkualitas</p>
            <a href="/Amimi/auth/register.php" class="btn btn-outline" style="color: #1a1a1a; border: 2px solid #1a1a1a; background-color: rgba(255,255,255,0.1); font-weight: 700; display: inline-block;">Daftar Gratis Sekarang</a>
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
