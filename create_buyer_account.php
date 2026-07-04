<?php
/**
 * Script untuk membuat akun pembeli test
 * Jalankan sekali melalui browser atau terminal
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Data pembeli
$name = 'kim dokja';
$email = 'pembeli@amimi.com';
$password = 'password';
$phone = '081234567890';
$address = 'Jl. Nebula No. 51, Kota Amimi';

// Check if email already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    die(" Email sudah terdaftar!");
}

// Generate Customer ID
$result = $conn->query("SELECT customer_id FROM users ORDER BY id DESC LIMIT 1");
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $lastNum = intval(substr($row['customer_id'], 4));
    $newNum = $lastNum + 1;
} else {
    $newNum = 1;
}
$customerId = 'AMM-' . str_pad($newNum, 5, '0', STR_PAD_LEFT);

// Hash password
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Insert user
$insert = $conn->prepare("INSERT INTO users (customer_id, name, email, password, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, 'customer')");
$insert->bind_param("ssssss", $customerId, $name, $email, $hashedPassword, $phone, $address);

if ($insert->execute()) {
    echo " Akun pembeli berhasil dibuat!<br>";
    echo "<strong>Email:</strong> pembeli@amimi.con<br>";
    echo "<strong>Password:</strong> password<br>";
    echo "<strong>Customer ID:</strong> " . htmlspecialchars($customerId) . "<br>";
    echo "<strong>Nama:</strong> " . htmlspecialchars($name) . "<br><br>";
    echo '<a href="/Amimi/auth/login.php"> Klik di sini untuk login</a>';
} else {
    echo " Gagal membuat akun: " . $conn->error;
}

$conn->close();
?>
