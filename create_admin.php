<?php
require_once __DIR__ . '/config/database.php';

// Create or update admin account
$email = 'admin@amimi.com';
$password = 'password123';
$name = 'Admin Amimi';

// Check if admin exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Create admin account
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $role = 'admin';
    
    $insertStmt = $conn->prepare("INSERT INTO users (name, email, password, role, phone, address) VALUES (?, ?, ?, ?, '081-2345-6789', 'Jl. Admin, Jakarta')");
    $insertStmt->bind_param("ssss", $name, $email, $hashedPassword, $role);
    
    if ($insertStmt->execute()) {
        echo " Admin account created successfully!<br>";
        echo "<strong>Email:</strong> " . htmlspecialchars($email) . "<br>";
        echo "<strong>Password:</strong> " . htmlspecialchars($password) . "<br><br>";
    } else {
        echo " Error creating admin: " . $conn->error;
    }
} else {
    // Update existing admin password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $updateStmt->bind_param("ss", $hashedPassword, $email);
    
    if ($updateStmt->execute()) {
        echo " Admin account updated!<br>";
        echo "<strong>Email:</strong> " . htmlspecialchars($email) . "<br>";
        echo "<strong>Password:</strong> " . htmlspecialchars($password) . "<br><br>";
    }
}

echo '<a href="/Amimi/auth/login.php"> Go to Login</a>';
?>
