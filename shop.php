<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Get categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");

// Get filter parameters
$categoryFilter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$searchQuery = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$sortBy = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'newest';
$priceFilter = isset($_GET['price']) ? sanitize($_GET['price']) : '';
$stockFilter = isset($_GET['stock']) ? sanitize($_GET['stock']) : 'available';

// Build query (stock > 0 is default unless stock=all is explicitly requested)
$query = "SELECT p.*, c.name as category_name FROM products p 
          JOIN categories c ON p.category_id = c.id 
          WHERE p.is_active = 1";

if ($stockFilter !== 'all') {
    $query .= " AND p.stock > 0";
}

if ($categoryFilter > 0) {
    $query .= " AND p.category_id = $categoryFilter";
}

if (!empty($searchQuery)) {
    $query .= " AND (p.name LIKE '%$searchQuery%' OR p.description LIKE '%$searchQuery%')";
}

if ($priceFilter === 'low') {
    $query .= " AND p.price < 100000";
} elseif ($priceFilter === 'mid') {
    $query .= " AND p.price >= 100000 AND p.price <= 500000";
} elseif ($priceFilter === 'high') {
    $query .= " AND p.price > 500000";
}

// Sort options
if ($sortBy === 'price_low') {
    $query .= " ORDER BY p.price ASC";
} elseif ($sortBy === 'price_high') {
    $query .= " ORDER BY p.price DESC";
} else {
    $query .= " ORDER BY p.created_at DESC";
}

$result = $conn->query($query);
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

$pageTitle = 'Belanja Produk';
include __DIR__ . '/includes/header.php';
?>

<style>
    .shop-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .shop-header {
        margin-bottom: 30px;
        text-align: center;
    }

    .shop-header h1 {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 8px;
        background: linear-gradient(135deg, #ff5e3a 0%, #e62e00 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .shop-layout {
        display: grid;
        grid-template-columns: 250px 1fr;
        gap: 25px;
    }

    /* Sidebar */
    .shop-sidebar {
        background-color: var(--card-dark);
        border: 1px solid var(--border-dark);
        border-radius: 12px;
        padding: 20px;
        height: fit-content;
        position: sticky;
        top: 20px;
    }

    .filter-section {
        margin-bottom: 25px;
    }

    .filter-section:last-child {
        margin-bottom: 0;
    }

    .filter-section h3 {
        font-size: 14px;
        font-weight: 700;
        margin-bottom: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--primary-gold);
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .filter-item {
        display: flex;
        align-items: center;
    }

    .filter-item input[type="checkbox"],
    .filter-item input[type="radio"] {
        width: 16px;
        height: 16px;
        cursor: pointer;
        accent-color: var(--primary-gold);
    }

    .filter-item label {
        margin-left: 8px;
        cursor: pointer;
        font-size: 13px;
        user-select: none;
    }

    .filter-item label:hover {
        color: var(--primary-gold);
    }

    /* Main Content */
    .shop-content {
        display: flex;
        flex-direction: column;
    }

    .shop-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 12px;
        background-color: var(--card-dark);
        padding: 15px;
        border-radius: 8px;
        border: 1px solid var(--border-dark);
    }

    .search-bar {
        flex: 1;
        min-width: 200px;
    }

    .search-bar input {
        width: 100%;
        padding: 10px 40px 10px 15px;
        border: 1px solid var(--border-dark);
        border-radius: 8px;
        background-color: rgba(212, 175, 55, 0.05);
        color: var(--text-primary);
        font-size: 13px;
    }

    .search-bar input:focus {
        outline: none;
        border-color: var(--primary-gold);
        box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
    }

    .sort-dropdown {
        padding: 8px 12px;
        border: 1px solid var(--border-dark);
        border-radius: 6px;
        background-color: rgba(212, 175, 55, 0.05);
        color: var(--text-primary);
        font-size: 13px;
        cursor: pointer;
    }

    .sort-dropdown:hover {
        border-color: var(--primary-gold);
    }

    /* Products Grid */
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 30px;
    }

    .product-card {
        background-color: var(--card-dark);
        border: 1px solid var(--border-dark);
        border-radius: 10px;
        overflow: hidden;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        height: 100%;
        cursor: pointer;
    }

    .product-card:hover {
        border-color: var(--primary-gold);
        box-shadow: 0 8px 20px rgba(212, 175, 55, 0.2);
        transform: translateY(-4px);
    }

    .product-image {
        width: 100%;
        aspect-ratio: 1;
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
        overflow: hidden;
        position: relative;
    }

    .product-image.has-sale {
        position: relative;
    }

    .product-sale-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 700;
        z-index: 10;
    }

    .product-stock-badge {
        position: absolute;
        bottom: 10px;
        left: 10px;
        background-color: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
    }

    .product-info {
        padding: 12px;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }

    .product-category {
        font-size: 11px;
        color: var(--primary-gold);
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .product-name {
        font-weight: 600;
        font-size: 13px;
        margin-bottom: 8px;
        line-height: 1.4;
        color: var(--text-primary);
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .product-price {
        font-size: 16px;
        font-weight: 700;
        color: var(--primary-gold);
        margin-bottom: 8px;
    }

    .product-footer {
        display: flex;
        gap: 8px;
        justify-content: space-between;
        align-items: center;
        margin-top: auto;
    }

    .product-rating {
        font-size: 12px;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .product-actions {
        display: flex;
        gap: 8px;
        width: 100%;
    }

    .btn-quick-view {
        flex: 1;
        padding: 8px 10px;
        background-color: rgba(212, 175, 55, 0.1);
        color: var(--primary-gold);
        border: 1px solid var(--primary-gold);
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-quick-view:hover {
        background-color: var(--primary-gold);
        color: #1a1a1a;
    }

    .btn-add-cart {
        flex: 1;
        padding: 8px 10px;
        background: linear-gradient(135deg, #ff5e3a 0%, #e62e00 100%);
        color: #1a1a1a;
        border: none;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-add-cart:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
    }

    .btn-add-cart:active {
        transform: translateY(0);
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background-color: var(--card-dark);
        border: 1px solid var(--border-dark);
        border-radius: 12px;
    }

    .empty-state-icon {
        font-size: 64px;
        margin-bottom: 20px;
    }

    .empty-state-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 10px;
    }

    .empty-state-desc {
        color: var(--text-muted);
        margin-bottom: 20px;
    }

    @media (max-width: 768px) {
        .shop-layout {
            grid-template-columns: 1fr;
        }

        .shop-sidebar {
            position: relative;
            top: auto;
        }

        .products-grid {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        }

        .shop-toolbar {
            flex-direction: column;
        }

        .search-bar {
            min-width: 100%;
        }
    }
</style>

<div class="shop-container">
    <!-- Header -->
    <div class="shop-header">
        <h1>Toko Amimi</h1>
        <p style="color: var(--text-muted);">Temukan produk pilihan dengan kualitas terbaik</p>
    </div>

    <div class="shop-layout">
        <!-- Sidebar Filter -->
        <aside class="shop-sidebar">
            <div class="filter-section">
                <h3>Kategori</h3>
                <div class="filter-group">
                    <div class="filter-item">
                        <input type="radio" id="cat_all" name="category" value="" <?= $categoryFilter === 0 ? 'checked' : '' ?> onchange="filterProducts()">
                        <label for="cat_all">Semua Kategori</label>
                    </div>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                        <div class="filter-item">
                            <input type="radio" id="cat_<?= $cat['id'] ?>" name="category" value="<?= $cat['id'] ?>" <?= $categoryFilter === $cat['id'] ? 'checked' : '' ?> onchange="filterProducts()">
                            <label for="cat_<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></label>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <hr style="border: none; border-top: 1px solid var(--border-dark); margin: 20px 0;">

            <div class="filter-section">
                <h3>Harga</h3>
                <div class="filter-group">
                    <div class="filter-item">
                        <input type="radio" id="price_all" name="price" value="" <?= empty($priceFilter) ? 'checked' : '' ?> onchange="filterProducts()">
                        <label for="price_all">Semua Harga</label>
                    </div>
                    <div class="filter-item">
                        <input type="radio" id="price_low" name="price" value="low" <?= $priceFilter === 'low' ? 'checked' : '' ?> onchange="filterProducts()">
                        <label for="price_low">Murah (< Rp 100k)</label>
                    </div>
                    <div class="filter-item">
                        <input type="radio" id="price_mid" name="price" value="mid" <?= $priceFilter === 'mid' ? 'checked' : '' ?> onchange="filterProducts()">
                        <label for="price_mid">Menengah (Rp 100k - 500k)</label>
                    </div>
                    <div class="filter-item">
                        <input type="radio" id="price_high" name="price" value="high" <?= $priceFilter === 'high' ? 'checked' : '' ?> onchange="filterProducts()">
                        <label for="price_high">Premium (> Rp 500k)</label>
                    </div>
                </div>
            </div>

            <hr style="border: none; border-top: 1px solid var(--border-dark); margin: 20px 0;">

            <div class="filter-section">
                <h3>Stok</h3>
                <div class="filter-group">
                    <div class="filter-item">
                        <input type="radio" id="stock_available" name="stock" value="available" <?= $stockFilter === 'available' ? 'checked' : '' ?> onchange="filterProducts()">
                        <label for="stock_available">Tersedia Saja</label>
                    </div>
                    <div class="filter-item">
                        <input type="radio" id="stock_all" name="stock" value="all" <?= $stockFilter === 'all' ? 'checked' : '' ?> onchange="filterProducts()">
                        <label for="stock_all">Semua (Termasuk Habis)</label>
                    </div>
                </div>
            </div>

            <a href="/Amimi/shop.php" class="btn btn-outline" style="width: 100%; margin-top: 20px; display: block; text-align: center; padding: 10px;">Reset Filter</a>
        </aside>

        <!-- Main Content -->
        <div class="shop-content">
            <!-- Toolbar -->
            <div class="shop-toolbar">
                <div class="search-bar">
                    <form action="/Amimi/shop.php" method="GET" style="display: flex; position: relative;">
                        <input type="text" name="search" placeholder=" Cari produk..." value="<?= htmlspecialchars($searchQuery) ?>">
                        <button type="submit" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--primary-gold); cursor: pointer; font-size: 16px;"></button>
                    </form>
                </div>
                <select class="sort-dropdown" onchange="changeSortByForm(this.value)">
                    <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>Terbaru</option>
                    <option value="price_low" <?= $sortBy === 'price_low' ? 'selected' : '' ?>>Harga Terendah</option>
                    <option value="price_high" <?= $sortBy === 'price_high' ? 'selected' : '' ?>>Harga Tertinggi</option>
                </select>
            </div>

            <!-- Products Grid -->
            <?php if (!empty($products)): ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): 
                        $profit = $product['price'] - $product['cost_price'];
                        $profitPercent = round(($profit / $product['cost_price']) * 100);
                    ?>
                        <div class="product-card">
                            <div class="product-image has-sale">
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
                                    <?php if (isLoggedIn()): ?>
                                        <button class="btn-add-cart" onclick="addToCartQuick(<?= $product['id'] ?>, '<?= htmlspecialchars($product['name']) ?>')" <?= $product['stock'] == 0 ? 'disabled style="opacity:0.5;"' : '' ?>>
                                            <?= $product['stock'] == 0 ? 'Habis' : 'Beli' ?>
                                        </button>
                                    <?php else: ?>
                                        <a href="/Amimi/auth/login.php" class="btn-add-cart" style="display: flex; align-items: center; justify-content: center; text-decoration: none;">Beli</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon"></div>
                    <div class="empty-state-title">Produk tidak ditemukan</div>
                    <p class="empty-state-desc">Coba ubah filter atau kata kunci pencarian Anda</p>
                    <a href="/Amimi/shop.php" class="btn btn-gold" style="display: inline-block;">Lihat Semua Produk</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function filterProducts() {
    const category = document.querySelector('input[name="category"]:checked')?.value || '';
    const price = document.querySelector('input[name="price"]:checked')?.value || '';
    const stock = document.querySelector('input[name="stock"]:checked')?.value || '';
    const sort = '<?= $sortBy ?>';
    const search = '<?= htmlspecialchars($searchQuery) ?>';
    
    let url = '/Amimi/shop.php?';
    if (category) url += 'category=' + category + '&';
    if (price) url += 'price=' + price + '&';
    if (stock) url += 'stock=' + stock + '&';
    if (search) url += 'search=' + encodeURIComponent(search) + '&';
    url += 'sort=' + sort;
    
    window.location.href = url;
}

function changeSortByForm(value) {
    const category = document.querySelector('input[name="category"]:checked')?.value || '';
    const price = document.querySelector('input[name="price"]:checked')?.value || '';
    const stock = document.querySelector('input[name="stock"]:checked')?.value || '';
    const search = '<?= htmlspecialchars($searchQuery) ?>';
    
    let url = '/Amimi/shop.php?';
    if (category) url += 'category=' + category + '&';
    if (price) url += 'price=' + price + '&';
    if (stock) url += 'stock=' + stock + '&';
    if (search) url += 'search=' + encodeURIComponent(search) + '&';
    url += 'sort=' + value;
    
    window.location.href = url;
}

function addToCartQuick(productId, productName) {
    // For products without size options, add directly
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
