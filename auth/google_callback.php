<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/google_auth.php';
require_once __DIR__ . '/../includes/functions.php';

$code = isset($_GET['code']) ? $_GET['code'] : '';

if (!$code) {
    setFlash('error', 'Google authentication failed: Code not received.');
    redirect('/Amimi/auth/login.php');
}

// Exchange code for token
$tokenData = getGoogleAccessToken($code);
if (isset($tokenData['error'])) {
    setFlash('error', 'Google OAuth Error: ' . htmlspecialchars($tokenData['error_description'] ?? $tokenData['error']));
    redirect('/Amimi/auth/login.php');
}

$accessToken = $tokenData['access_token'];

// Get user info
$googleUser = getGoogleUserInfo($accessToken);
if (!$googleUser || !isset($googleUser['email'])) {
    setFlash('error', 'Gagal mengambil data profil Google.');
    redirect('/Amimi/auth/login.php');
}

$googleId = $googleUser['id'];
$name = $googleUser['name'];
$email = $googleUser['email'];
$avatar = $googleUser['picture'] ?? '';

// Check if user already exists
$stmt = $conn->prepare("SELECT * FROM users WHERE google_id = ? OR email = ?");
$stmt->bind_param("ss", $googleId, $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    // User exists. Update google_id and avatar if empty
    if (empty($user['google_id']) || empty($user['avatar'])) {
        $update = $conn->prepare("UPDATE users SET google_id = ?, avatar = ? WHERE id = ?");
        $update->bind_param("ssi", $googleId, $avatar, $user['id']);
        $update->execute();
        $user['google_id'] = $googleId;
        $user['avatar'] = $avatar;
    }
} else {
    // New user registration
    $customerId = generateCustomerId($conn);
    
    $insert = $conn->prepare("INSERT INTO users (customer_id, name, email, google_id, avatar, role) VALUES (?, ?, ?, ?, ?, 'customer')");
    $insert->bind_param("sssss", $customerId, $name, $email, $googleId, $avatar);
    $insert->execute();
    
    // Fetch inserted user
    $userId = $insert->insert_id;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
}

// Log user in
$_SESSION['user_id'] = $user['id'];
$_SESSION['customer_id'] = $user['customer_id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['user_avatar'] = $user['avatar'];

setFlash('success', 'Berhasil masuk dengan Google! Selamat datang, ' . $user['name'] . '!');

if ($user['role'] === 'admin') {
    redirect('/Amimi/admin/index.php');
} else {
    redirect('/Amimi/index.php');
}
