<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$error = '';
$success = '';
$uploadDir = __DIR__ . '/../uploads/promotions/';
$flyerPath = $uploadDir . 'flyer.jpg';
$hasFlyer = file_exists($flyerPath);

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        if ($hasFlyer && unlink($flyerPath)) {
            setFlash('success', 'Flayer promosi berhasil dihapus.');
            redirect('/Amimi/admin/promo_form.php');
        } else {
            $error = 'Gagal menghapus flayer.';
        }
    } else {
        if (isset($_FILES['flyer']) && $_FILES['flyer']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['flyer']['tmp_name'];
            $fileName = $_FILES['flyer']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($fileExtension, $allowedExtensions)) {
                // Konversi gambar ke format seragam atau cukup simpan sebagai flyer.jpg
                // Karena HTML pakai src="/Amimi/uploads/promotions/flyer.jpg", kita paksa ext .jpg (atau cukup rename jadi flyer.jpg)
                if (move_uploaded_file($fileTmpPath, $flyerPath)) {
                    setFlash('success', 'Flayer promosi berhasil diunggah dan diperbarui.');
                    redirect('/Amimi/admin/promo_form.php');
                } else {
                    $error = 'Gagal menyimpan gambar ke direktori uploads.';
                }
            } else {
                $error = 'Ekstensi gambar tidak valid. Gunakan JPG, JPEG, PNG, atau WEBP.';
            }
        } else {
            $error = 'Silakan pilih gambar yang ingin diunggah.';
        }
    }
}

$pageTitle = 'Upload Flayer Promosi';
include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 600px; padding-top: 40px;">
    <div style="margin-bottom: 20px;">
        <a href="/Amimi/index.php" style="color: var(--text-muted); font-size: 14px;">← Kembali ke Beranda</a>
    </div>

    <div class="checkout-section">
        <h3 class="checkout-section-title"> Kelola Flayer Promosi</h3>
        <p style="color: var(--text-muted); margin-bottom: 20px; font-size: 14px;">
            Unggah gambar flayer promosi di sini. Flayer akan ditampilkan secara otomatis di bagian atas beranda pelanggan.
        </p>

        <?php if ($error): ?>
            <div style="background-color: rgba(255, 82, 82, 0.1); border: 1px solid var(--danger); color: var(--danger); padding: 12px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; text-align: center;">
                 <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($hasFlyer): ?>
            <div style="margin-bottom: 25px; border: 1px solid var(--border-dark); border-radius: 12px; overflow: hidden; position: relative;">
                <div style="background: rgba(0,0,0,0.5); position: absolute; top: 10px; right: 10px; padding: 4px 10px; border-radius: 4px; font-size: 12px; color: #fff;">Flayer Aktif</div>
                <img src="/Amimi/uploads/promotions/flyer.jpg?t=<?= time() ?>" alt="Current Flyer" style="width: 100%; display: block;">
            </div>
            
            <form action="/Amimi/admin/promo_form.php" method="POST" style="margin-bottom: 25px;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="btn btn-outline" style="width: 100%; color: var(--danger); border-color: var(--danger);" onclick="return confirm('Hapus flayer promosi saat ini?');"> Hapus Flayer Saat Ini</button>
            </form>
        <?php else: ?>
            <div style="padding: 40px; text-align: center; border: 1px dashed var(--border-dark); border-radius: 12px; margin-bottom: 25px; color: var(--text-muted);">
                Belum ada flayer promosi yang aktif.
            </div>
        <?php endif; ?>

        <form action="/Amimi/admin/promo_form.php" method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>
            <div class="form-group">
                <label class="form-label" for="flyer">Unggah Gambar Baru</label>
                <input type="file" name="flyer" id="flyer" class="form-control" accept="image/*" required>
                <small style="color: var(--text-muted); display: block; margin-top: 6px;">Format yang didukung: JPG, PNG, WEBP. Saran rasio: Lebar (misal 1200x400 px).</small>
            </div>
            
            <button type="submit" class="btn btn-gold btn-block" style="margin-top: 15px;">Unggah Flayer </button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
