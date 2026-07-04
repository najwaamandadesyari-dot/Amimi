<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // Optionally delete the associated file from disk if desired
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    if ($product) {
        if (!empty($product['image'])) {
            $path = __DIR__ . '/../uploads/products/' . $product['image'];
            if (file_exists($path)) {
                unlink($path);
            }
        }
        
        // Delete from database
        $delete = $conn->prepare("DELETE FROM products WHERE id = ?");
        $delete->bind_param("i", $id);
        if ($delete->execute()) {
            setFlash('success', 'Produk berhasil dihapus permanent.');
        } else {
            setFlash('error', 'Gagal menghapus produk dari database.');
        }
    } else {
        setFlash('error', 'Produk tidak ditemukan.');
    }
} else {
    setFlash('error', 'Parameter tidak valid.');
}

redirect('/Amimi/admin/products.php');
