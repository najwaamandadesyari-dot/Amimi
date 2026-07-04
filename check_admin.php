<?php
require_once __DIR__ . '/config/database.php';

echo "<pre style='font-family:monospace; padding:20px; background:#111; color:#0f0;'>";

// Cek semua user
$result = $conn->query("SELECT id, name, email, role, SUBSTRING(password,1,30) as pass_preview, created_at FROM users ORDER BY id");
echo "=== SEMUA USER DI DATABASE ===\n";
if ($result->num_rows == 0) {
    echo "  TIDAK ADA USER SAMA SEKALI! Database kosong.\n";
} else {
    while ($row = $result->fetch_assoc()) {
        echo "ID:{$row['id']} | {$row['email']} | role:{$row['role']} | pass:{$row['pass_preview']}...\n";
    }
}

// Cek struktur tabel users
echo "\n=== STRUKTUR TABEL USERS ===\n";
$cols = $conn->query("DESCRIBE users");
if ($cols) {
    while($c = $cols->fetch_assoc()) {
        echo "{$c['Field']}: {$c['Type']} | null:{$c['Null']} | default:{$c['Default']}\n";
    }
} else {
    echo "ERROR: Tabel users tidak ada! " . $conn->error . "\n";
}

// Test koneksi DB
echo "\n=== INFO KONEKSI ===\n";
echo "DB Host: " . DB_HOST . "\n";
echo "DB Name: " . DB_NAME . "\n";
echo "DB User: " . DB_USER . "\n";
echo "Koneksi: " . ($conn->ping() ? " OK" : " GAGAL") . "\n";

// Reset/buat admin 
echo "\n=== RESET AKUN ADMIN ===\n";
$email = 'admin@amimi.com';
$password = 'admin123';
$hashedPw = password_hash($password, PASSWORD_BCRYPT);

// Cek apakah admin ada
$check = $conn->prepare("SELECT id, role FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$existing = $check->get_result()->fetch_assoc();

if ($existing) {
    // Update password dan pastikan role = admin
    $upd = $conn->prepare("UPDATE users SET password = ?, role = 'admin' WHERE email = ?");
    $upd->bind_param("ss", $hashedPw, $email);
    if ($upd->execute()) {
        echo " Password admin direset!\n";
        echo "   Email: $email\n";
        echo "   Password baru: $password\n";
        echo "   Role lama: {$existing['role']} → admin\n";
    } else {
        echo " Gagal update: " . $conn->error . "\n";
    }
} else {
    // Buat akun admin baru
    $ins = $conn->prepare("INSERT INTO users (name, email, password, role, customer_id) VALUES ('Admin Amimi', ?, ?, 'admin', 'AMM-ADMIN')");
    $ins->bind_param("ss", $email, $hashedPw);
    if ($ins->execute()) {
        echo " Akun admin BARU dibuat!\n";
        echo "   Email: $email\n";
        echo "   Password: $password\n";
    } else {
        echo " Gagal buat admin: " . $conn->error . "\n";
    }
}

echo "\n";
echo "</pre>";
echo "<br><a href='/Amimi/auth/login.php' style='background:#e62e00;color:#000;padding:12px 24px;border-radius:6px;font-weight:700;text-decoration:none;'> Pergi ke Halaman Login</a>";
