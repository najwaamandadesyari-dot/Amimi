<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/google_auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    redirect('/Amimi/index.php');
}

$email = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Email dan Password wajib diisi.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            // Login success
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['customer_id'] = $user['customer_id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_avatar'] = $user['avatar'];

            setFlash('success', 'Selamat datang kembali, ' . $user['name'] . '!');
            if ($user['role'] === 'admin') {
                redirect('/Amimi/admin/index.php');
            } else {
                redirect('/Amimi/index.php');
            }
        } else {
            $error = 'Email atau Password salah.';
        }
    }
}

$pageTitle = 'Masuk';
include __DIR__ . '/../includes/header.php';
?>

<div class="container auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <h2 class="auth-logo"><img src="/Amimi/assets/img/logo.jpeg" alt="Amimi Logo" style="height: 48px; border-radius: 8px;"></h2>
            <p class="auth-subtitle">Masuk ke akun Anda</p>
        </div>

        <?php if ($error): ?>
            <div style="background-color: rgba(255, 82, 82, 0.1); border: 1px solid var(--danger); color: var(--danger); padding: 12px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; text-align: center;">
                 <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="/Amimi/auth/login.php" method="POST">
            <?= csrfField() ?>
            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="nama@email.com" value="<?= htmlspecialchars($email) ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-gold btn-block" style="margin-top: 10px;">Masuk</button>
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
            Masuk dengan Google
        </a>

        <div class="auth-footer">
            Belum punya akun? <a href="/Amimi/auth/register.php">Daftar Sekarang</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
