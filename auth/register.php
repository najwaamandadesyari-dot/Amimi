<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/google_auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    redirect('/Amimi/index.php');
}

$name = '';
$email = '';
$phone = '';
$address = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($name) || empty($email) || empty($phone) || empty($address) || empty($password)) {
        $error = 'Semua field wajib diisi.';
    } elseif ($password !== $confirm_password) {
        $error = 'Konfirmasi password tidak cocok.';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';
    } else {
        // Check if email already registered
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Email sudah terdaftar. Silakan gunakan email lain.';
        } else {
            // Generate Customer ID
            $customerId = generateCustomerId($conn);
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            
            // Insert user
            $insert = $conn->prepare("INSERT INTO users (customer_id, name, email, password, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, 'customer')");
            $insert->bind_param("ssssss", $customerId, $name, $email, $hashedPassword, $phone, $address);
            
            if ($insert->execute()) {
                // Get the inserted user's ID
                $userId = $insert->insert_id;
                
                // Set session
                $_SESSION['user_id'] = $userId;
                $_SESSION['customer_id'] = $customerId;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'customer';
                
                setFlash('success', 'Pendaftaran berhasil! Selamat datang di Amimi Shop. ID Pelanggan Anda: ' . $customerId);
                redirect('/Amimi/index.php');
            } else {
                $error = 'Terjadi kesalahan sistem saat mendaftar. Silakan coba lagi.';
            }
        }
    }
}

$pageTitle = 'Daftar Akun';
include __DIR__ . '/../includes/header.php';
?>

<div class="container auth-wrapper">
    <div class="auth-card" style="max-width: 500px;">
        <div class="auth-header">
            <h2 class="auth-logo"><img src="/Amimi/assets/img/logo.jpeg" alt="Amimi Logo" style="height: 48px; border-radius: 8px;"></h2>
            <p class="auth-subtitle">Daftar akun pelanggan baru</p>
        </div>

        <?php if ($error): ?>
            <div style="background-color: rgba(255, 82, 82, 0.1); border: 1px solid var(--danger); color: var(--danger); padding: 12px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; text-align: center;">
                 <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="/Amimi/auth/register.php" method="POST">
            <?= csrfField() ?>
            <div class="form-group">
                <label class="form-label" for="name">Nama Lengkap</label>
                <input type="text" name="name" id="name" class="form-control" placeholder="Nama Lengkap Anda" value="<?= htmlspecialchars($name) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="nama@email.com" value="<?= htmlspecialchars($email) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="phone">No. Telepon</label>
                <input type="text" name="phone" id="phone" class="form-control" placeholder="Contoh: 08123456789" value="<?= htmlspecialchars($phone) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="address">Alamat Pengiriman Lengkap</label>
                <textarea name="address" id="address" class="form-control" rows="3" placeholder="Masukkan alamat lengkap pengiriman paket" required><?= htmlspecialchars($address) ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Minimal 8 karakter" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Konfirmasi Password</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Ulangi password" required>
            </div>

            <button type="submit" class="btn btn-gold btn-block" style="margin-top: 10px;">Daftar Sekarang </button>
        </form>

        <div class="auth-divider">ATAU</div>

        <a href="<?= getGoogleAuthUrl() ?>" class="btn btn-google btn-block">
            <!-- Google Icon Simplified SVG -->
            <svg width="18" height="18" viewBox="0 0 24 24" style="margin-right: 8px;">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
            </svg>
            Daftar dengan Google
        </a>

        <div class="auth-footer">
            Sudah punya akun? <a href="/Amimi/auth/login.php">Masuk</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
