<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$isEdit = ($id > 0);

$name = '';
$description = '';
$categoryId = '';
$price = '';
$costPrice = '';
$stock = '';
$sizesArr = [];
$sizeStocks = [];
$isActive = 1;
$error = '';

// Load data if editing
if ($isEdit) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    if (!$product) {
        setFlash('error', 'Produk tidak ditemukan.');
        redirect('/Amimi/admin/products.php');
    }
    
    $name = $product['name'];
    $description = $product['description'];
    $categoryId = $product['category_id'];
    $price = $product['price'];
    $costPrice = $product['cost_price'];
    $stock = $product['stock'];
    $sizesArr = $product['sizes'] ? array_map('trim', explode(',', $product['sizes'])) : [];
    $isActive = $product['is_active'];
    
    // Fetch size stocks
    $stmt2 = $conn->prepare("SELECT * FROM product_sizes WHERE product_id = ?");
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    while ($rs = $res2->fetch_assoc()) {
        $sizeStocks[$rs['size']] = $rs['stock'];
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $categoryId = intval($_POST['category_id']);
    $price = floatval($_POST['price']);
    $costPrice = floatval($_POST['cost_price']);
    $stock = intval($_POST['stock']);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Size check
    $selectedSizes = isset($_POST['sizes']) ? $_POST['sizes'] : [];
    $sizesString = !empty($selectedSizes) ? implode(',', $selectedSizes) : null;
    $sizeStocksInput = isset($_POST['size_stock']) ? $_POST['size_stock'] : [];

    if (in_array($categoryId, [1, 2, 3])) {
        $stock = 0;
        foreach ($selectedSizes as $sz) {
            $stock += intval($sizeStocksInput[$sz] ?? 0);
        }
    }

    if (empty($name) || $categoryId <= 0 || $price <= 0 || $costPrice <= 0 || $stock < 0) {
        $error = 'Semua field wajib diisi dengan nilai yang valid. Jika ini pakaian, pastikan Anda mengisi stok di masing-masing ukuran.';
    } else {
        // Image Processing
        $imageName = $isEdit ? $product['image'] : '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['image']['tmp_name'];
            $fileName = $_FILES['image']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($fileExtension, $allowedExtensions)) {
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $uploadFileDir = __DIR__ . '/../uploads/products/';
                
                // Create directory if not exists
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }
                
                $dest_path = $uploadFileDir . $newFileName;
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $imageName = $newFileName;
                }
            } else {
                $error = 'Ekstensi gambar tidak valid. Gunakan JPG, JPEG, PNG, atau WEBP.';
            }
        }

        if (empty($error)) {
            if ($isEdit) {
                // Update product
                $update = $conn->prepare("UPDATE products SET category_id = ?, name = ?, description = ?, price = ?, cost_price = ?, stock = ?, sizes = ?, image = ?, is_active = ? WHERE id = ?");
                $update->bind_param("issddissii", $categoryId, $name, $description, $price, $costPrice, $stock, $sizesString, $imageName, $isActive, $id);
                
                if ($update->execute()) {
                    // Sync product_sizes
                    $conn->query("DELETE FROM product_sizes WHERE product_id = " . $id);
                    if (in_array($categoryId, [1, 2, 3]) && !empty($selectedSizes)) {
                        $psStmt = $conn->prepare("INSERT INTO product_sizes (product_id, size, stock) VALUES (?, ?, ?)");
                        foreach ($selectedSizes as $sz) {
                            $stk = intval($sizeStocksInput[$sz] ?? 0);
                            $psStmt->bind_param("isi", $id, $sz, $stk);
                            $psStmt->execute();
                        }
                    }
                    
                    setFlash('success', 'Produk "' . $name . '" berhasil diperbarui.');
                    redirect('/Amimi/admin/products.php');
                } else {
                    $error = 'Gagal menyimpan perubahan ke database.';
                }
            } else {
                // Insert product
                $insert = $conn->prepare("INSERT INTO products (category_id, name, description, price, cost_price, stock, sizes, image, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $insert->bind_param("issddissi", $categoryId, $name, $description, $price, $costPrice, $stock, $sizesString, $imageName, $isActive);
                
                if ($insert->execute()) {
                    $newProductId = $insert->insert_id;
                    
                    // Sync product_sizes
                    if (in_array($categoryId, [1, 2, 3]) && !empty($selectedSizes)) {
                        $psStmt = $conn->prepare("INSERT INTO product_sizes (product_id, size, stock) VALUES (?, ?, ?)");
                        foreach ($selectedSizes as $sz) {
                            $stk = intval($sizeStocksInput[$sz] ?? 0);
                            $psStmt->bind_param("isi", $newProductId, $sz, $stk);
                            $psStmt->execute();
                        }
                    }
                    
                    setFlash('success', 'Produk "' . $name . '" berhasil ditambahkan.');
                    redirect('/Amimi/admin/products.php');
                } else {
                    $error = 'Gagal menyimpan produk baru ke database.';
                }
            }
        }
    }
}

// Fetch categories for dropdown
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");

$pageTitle = $isEdit ? 'Edit Produk' : 'Tambah Produk';
include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 800px; padding-top: 20px;">
    <div style="margin-bottom: 20px;">
        <a href="/Amimi/admin/products.php" style="color: var(--text-muted); font-size: 14px;">← Kembali ke Kelola Produk</a>
    </div>

    <div class="checkout-section" style="margin-bottom: 0;">
        <h3 class="checkout-section-title"><?= $isEdit ? ' Edit Data Produk' : ' Tambah Produk Baru ke Katalog' ?></h3>

        <?php if ($error): ?>
            <div style="background-color: rgba(255, 82, 82, 0.1); border: 1px solid var(--danger); color: var(--danger); padding: 12px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; text-align: center;">
                 <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="/Amimi/admin/product_form.php<?= $isEdit ? '?id=' . $id : '' ?>" method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>
            
            <div class="form-group">
                <label class="form-label" for="name">Nama Produk*</label>
                <input type="text" name="name" id="name" class="form-control" placeholder="Contoh: Kemeja Flanel Casual" value="<?= htmlspecialchars($name) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="category_id">Kategori Produk*</label>
                <select name="category_id" id="category_id" class="form-control" required onchange="toggleSizeOptions(this.value)">
                    <option value="">-- Pilih Kategori --</option>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                        <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                            <?= $cat['icon'] ?> <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Deskripsi Produk</label>
                <textarea name="description" id="description" class="form-control" rows="4" placeholder="Detail deskripsi bahan, ukuran real, keunggulan, dll."><?= htmlspecialchars($description) ?></textarea>
            </div>

            <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <label class="form-label" for="cost_price">Harga Beli (Modal)*</label>
                    <input type="number" name="cost_price" id="cost_price" class="form-control" placeholder="Contoh: 50000" value="<?= htmlspecialchars($costPrice) ?>" required>
                </div>
                <div>
                    <label class="form-label" for="price">Harga Jual*</label>
                    <input type="number" name="price" id="price" class="form-control" placeholder="Contoh: 85000" value="<?= htmlspecialchars($price) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="stock">Stok Produk*</label>
                <input type="number" name="stock" id="stock" class="form-control" placeholder="Contoh: 100" value="<?= htmlspecialchars($stock) ?>" required>
            </div>

            <!-- Size Checkboxes (Only shown/enabled for clothing categories: id 1, 2, 3) -->
            <div class="form-group" id="sizeOptionsGroup" style="border: 1px solid var(--border-dark); padding: 15px; border-radius: 8px; background-color: var(--bg-dark);">
                <label class="form-label" style="margin-bottom: 12px;">Pilihan Ukuran Baju & Stok (S-XL)</label>
                <div style="display: flex; gap: 20px;">
                    <?php foreach (['S', 'M', 'L', 'XL'] as $sz): ?>
                        <div style="display: flex; flex-direction: column; gap: 6px;">
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                <input type="checkbox" name="sizes[]" value="<?= $sz ?>" <?= in_array($sz, $sizesArr) ? 'checked' : '' ?> onchange="document.getElementById('stock_<?= $sz ?>').disabled = !this.checked;">
                                <span><?= $sz ?></span>
                            </label>
                            <input type="number" name="size_stock[<?= $sz ?>]" id="stock_<?= $sz ?>" class="form-control" style="width: 80px;" placeholder="Stok" value="<?= isset($sizeStocks[$sz]) ? $sizeStocks[$sz] : 0 ?>" <?= in_array($sz, $sizesArr) ? '' : 'disabled' ?>>
                        </div>
                    <?php endforeach; ?>
                </div>
                <small style="color: var(--text-muted); display: block; margin-top: 8px;">Centang ukuran yang tersedia lalu isi stok di bawahnya. Total stok akan otomatis dikalkulasi.</small>
            </div>

            <div class="form-group">
                <label class="form-label" for="image">Gambar Produk</label>
                <?php if ($isEdit && $product['image']): ?>
                    <div style="margin-bottom: 10px;">
                        <img src="/Amimi/uploads/products/<?= htmlspecialchars($product['image']) ?>" alt="Preview" style="max-width: 150px; border-radius: 8px; border: 1px solid var(--border-dark);">
                    </div>
                <?php endif; ?>
                <input type="file" name="image" id="image" class="form-control" accept="image/*">
            </div>

            <div class="form-group" style="display: flex; align-items: center; gap: 10px;">
                <input type="checkbox" name="is_active" id="is_active" value="1" <?= $isActive ? 'checked' : '' ?> style="width: 18px; height: 18px; cursor: pointer;">
                <label for="is_active" style="cursor: pointer; font-weight: 500;">Aktifkan Produk (Tampilkan di Katalog Pelanggan)</label>
            </div>

            <button type="submit" class="btn btn-gold btn-block" style="margin-top: 15px;">Simpan Produk </button>
        </form>
    </div>
</div>

<script>
function toggleSizeOptions(catId) {
    const group = document.getElementById('sizeOptionsGroup');
    const stockInput = document.getElementById('stock');
    // Categories 1 (Wanita), 2 (Pria), 3 (Anak) are clothing types.
    if (catId == '1' || catId == '2' || catId == '3') {
        group.style.opacity = '1';
        group.querySelectorAll('input[type="checkbox"]').forEach(i => i.disabled = false);
        group.querySelectorAll('input[type="number"]').forEach(i => {
            if(i.previousElementSibling.querySelector('input').checked) i.disabled = false;
        });
        stockInput.readOnly = true;
        stockInput.value = '0'; // Will be calculated on backend
        stockInput.parentElement.style.opacity = '0.5';
    } else {
        group.style.opacity = '0.4';
        group.querySelectorAll('input').forEach(i => {
            i.disabled = true;
            if(i.type === 'checkbox') i.checked = false; 
            if(i.type === 'number') i.value = '0';
        });
        stockInput.readOnly = false;
        stockInput.parentElement.style.opacity = '1';
    }
}

// Run on page load
document.addEventListener('DOMContentLoaded', () => {
    toggleSizeOptions(document.getElementById('category_id').value);
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
