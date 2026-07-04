<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

echo "<pre style='font-family:monospace; padding:20px; background:#111; color:#0f0; font-size:13px;'>";

echo "=== CEK PRODUK DI DATABASE ===\n";
$result = $conn->query("SELECT id, name, image FROM products");
if (!$result) {
    echo " ERROR: " . $conn->error . "\n";
} elseif ($result->num_rows == 0) {
    echo "  Tidak ada produk di database!\n";
} else {
    while ($row = $result->fetch_assoc()) {
        $imgVal = $row['image'];
        echo "ID:{$row['id']} | {$row['name']}\n";
        echo "  image kolom = " . (empty($imgVal) ? "(KOSONG/NULL)" : "'" . $imgVal . "'") . "\n";
        if (!empty($imgVal)) {
            $fullPath = __DIR__ . '/uploads/products/' . $imgVal;
            echo "  file path  = $fullPath\n";
            echo "  file exist = " . (file_exists($fullPath) ? " ADA" : " TIDAK ADA") . "\n";
        }
        echo "\n";
    }
}

echo "=== FILE DI uploads/products/ ===\n";
$dir = __DIR__ . '/uploads/products/';
if (is_dir($dir)) {
    $files = scandir($dir);
    foreach ($files as $f) {
        if ($f !== '.' && $f !== '..') {
            echo "   $f (" . filesize($dir.$f) . " bytes)\n";
        }
    }
} else {
    echo " Folder uploads/products/ tidak ditemukan!\n";
}

echo "\n=== CEK BASE_PATH ===\n";
echo "BASE_PATH = " . BASE_PATH . "\n";
echo "DOCUMENT_ROOT = " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script dir = " . __DIR__ . "\n";

echo "</pre>";
echo "<br><a href='/Amimi/shop.php' style='background:#e62e00;color:#000;padding:12px 24px;border-radius:6px;font-weight:700;text-decoration:none;'> Pergi ke Shop</a>";
?>
