<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

requireAdmin();

// Fetch products
$query = "
    SELECT p.*, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    ORDER BY p.id DESC
";
$result = $conn->query($query);
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

$pageTitle = 'Kelola Produk';
include __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 20px;">
    <div class="admin-section-header">
        <h1 style="font-size: 28px; margin-bottom: 0;"> Kelola Produk Katalog</h1>
        <a href="/Amimi/admin/product_form.php" class="btn btn-gold">+ Tambah Produk Baru</a>
    </div>

    <!-- Product Table Section -->
    <div class="admin-section" style="margin-top: 30px; padding: 0; overflow: hidden;">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Gambar</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th>Harga Beli (Modal)</th>
                        <th>Harga Jual</th>
                        <th>Stok</th>
                        <th>Ukuran</th>
                        <th>Status</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <!-- Simple icon preview instead of hard file system lookup for safety -->
                                    <div style="width: 44px; height: 44px; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); display: flex; align-items: center; justify-content: center; border-radius: 6px; border: 1px solid var(--border-dark);">
                                        <span style="font-size: 20px;"><?= $product['category_id'] == 1 ? '' : ($product['category_id'] == 2 ? '' : ($product['category_id'] == 3 ? '' : ($product['category_id'] == 4 ? '' : ($product['category_id'] == 5 ? '' : '')))) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($product['name']) ?></strong>
                                    <br>
                                    <small style="color: var(--text-muted); font-size: 11px;"><?= substr(htmlspecialchars($product['description']), 0, 50) ?>...</small>
                                </td>
                                <td><?= htmlspecialchars($product['category_name']) ?></td>
                                <td><span style="color: var(--text-muted);"><?= formatRupiah($product['cost_price']) ?></span></td>
                                <td><strong style="color: var(--primary-gold);"><?= formatRupiah($product['price']) ?></strong></td>
                                <td>
                                    <?php if ($product['stock'] <= 5): ?>
                                        <span style="color: var(--danger); font-weight: 700;"><?= $product['stock'] ?> (Kritis)</span>
                                    <?php else: ?>
                                        <span><?= $product['stock'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $product['sizes'] ? '<span class="badge badge-info">' . $product['sizes'] . '</span>' : '<span style="color:var(--text-muted); font-size:12px;">N/A</span>' ?>
                                </td>
                                <td>
                                    <?php if ($product['is_active']): ?>
                                        <span class="badge badge-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 8px; justify-content: center;">
                                        <a href="/Amimi/admin/product_form.php?id=<?= $product['id'] ?>" class="btn btn-outline btn-sm" title="Edit"> Edit</a>
                                        <a href="/Amimi/admin/product_delete.php?id=<?= $product['id'] ?>" class="btn btn-danger btn-sm" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini permanent?');"> Hapus</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px; color: var(--text-muted);">Belum ada produk terdaftar. Silakan tambahkan produk baru.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
