<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);

    if (empty($name) || empty($phone) || empty($address)) {
        $error = 'Nama, No. Telepon, dan Alamat wajib diisi.';
    } else {
        $profileImage = $user['profile_image'];
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/profiles/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $fileName = 'profile_' . $userId . '_' . time() . '.' . strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadDir . $fileName)) {
                if ($profileImage && file_exists($uploadDir . $profileImage)) unlink($uploadDir . $profileImage);
                $profileImage = $fileName;
            }
        }
        
        $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, address = ?, profile_image = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $name, $phone, $address, $profileImage, $userId);
        if ($stmt->execute()) {
            $_SESSION['user_name'] = $name;
            setFlash('success', 'Profil Anda berhasil diperbarui.');
            redirect('/Amimi/customer/profile.php');
        } else {
            $error = 'Gagal memperbarui profil. Silakan coba lagi.';
        }
    }
}

// Fetch current details
$user = getUserById($conn, $userId);

$pageTitle = 'Profil Saya';
include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 800px; padding-top: 20px;">
    <h1 style="font-size: 28px; margin-bottom: 24px;"> Profil Pelanggan</h1>

    <div class="cart-layout" style="grid-template-columns: 240px 1fr; gap: 30px;">
        <!-- Left: Customer Card -->
        <div style="background-color: var(--card-dark); border: 1px solid var(--border-dark); border-radius: 12px; padding: 24px; text-align: center; height: fit-content;">
            <?php if (!empty($user['profile_image'])): ?>
                <img src="/Amimi/uploads/profiles/<?= htmlspecialchars($user['profile_image']) ?>" alt="Avatar" style="width: 100px; height: 100px; border-radius: 50%; border: 2px solid var(--primary-gold); margin-bottom: 15px; object-fit: cover;">
            <?php elseif (!empty($user['avatar'])): ?>
                <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar" style="width: 100px; height: 100px; border-radius: 50%; border: 2px solid var(--primary-gold); margin-bottom: 15px; object-fit: cover;">
            <?php else: ?>
                <div style="width: 100px; height: 100px; border-radius: 50%; background-color: var(--border-dark); display: flex; align-items: center; justify-content: center; font-size: 36px; font-weight: 700; color: var(--primary-gold); border: 2px solid var(--primary-gold); margin: 0 auto 15px auto;">
                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                </div>
            <?php endif; ?>
            
            <h3 style="font-size: 18px; margin-bottom: 5px;"><?= htmlspecialchars($user['name']) ?></h3>
            <span style="font-size: 11px; background-color: rgba(245, 166, 35, 0.15); color: var(--primary-gold); padding: 4px 10px; border-radius: 20px; font-weight: 700; text-transform: uppercase;">
                ID: <?= htmlspecialchars($user['customer_id']) ?>
            </span>
            <p style="color: var(--text-muted); font-size: 12px; margin-top: 15px;">Mendaftar sejak:<br><?= date('d M Y', strtotime($user['created_at'])) ?></p>
        </div>

        <!-- Right: Edit Form -->
        <div class="checkout-section" style="margin-bottom: 0;">
            <h3 class="checkout-section-title"> Perbarui Data Diri</h3>
            
            <?php if ($error): ?>
                <div style="background-color: rgba(255, 82, 82, 0.1); border: 1px solid var(--danger); color: var(--danger); padding: 12px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; text-align: center;">
                     <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="/Amimi/customer/profile.php" method="POST" enctype="multipart/form-data">
                <?= csrfField() ?>
                
                <div class="form-group">
                    <label class="form-label" for="profile_image">Foto Profil (Opsional)</label>
                    <input type="file" name="profile_image" id="profile_image" class="form-control" accept="image/*">
                </div>

                <div class="form-group">
                    <label class="form-label">Email (Tidak dapat diubah)</label>
                    <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly style="background-color: #1a1a24; color: var(--text-muted); cursor: not-allowed;">
                </div>

                <div class="form-group">
                    <label class="form-label" for="name">Nama Lengkap</label>
                    <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone">No. Telepon / WhatsApp</label>
                    <input type="text" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="address">Alamat Pengiriman Utama</label>
                    <textarea name="address" id="address" class="form-control" rows="4" required><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-gold btn-block" style="margin-top: 10px;">Simpan Perubahan </button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
