<?php
/**
 * Helper Functions - Amimi Shop
 */

session_start();

// Detect project folder and set base path constants so URLs work even when folder name changes
$projectRoot = dirname(__DIR__);
$projectFolder = basename($projectRoot);
if (!defined('PROJECT_FOLDER')) define('PROJECT_FOLDER', $projectFolder);
if (!defined('BASE_PATH')) define('BASE_PATH', '/' . PROJECT_FOLDER . '/');
if (!defined('BASE_URL')) {
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('BASE_URL', $scheme . '://' . $host . BASE_PATH);
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Require login - redirect if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        setFlash('error', 'Silakan login terlebih dahulu.');
        redirect('/Amimi/auth/login.php');
    }
}

/**
 * Require admin role
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        setFlash('error', 'Akses ditolak. Anda bukan admin.');
        redirect('/Amimi/index.php');
    }
}

/**
 * Redirect to URL
 */
function redirect($url) {
    // Replace hardcoded project folder in URLs with dynamic BASE_PATH
    if (defined('PROJECT_FOLDER')) {
        $hard = '/' . PROJECT_FOLDER . '/';
        if (strpos($url, $hard) !== false) {
            $url = str_replace($hard, BASE_PATH, $url);
        }
    }
    // If a relative path is provided without leading slash, prefix BASE_PATH
    if (strpos($url, '/') !== 0 && defined('BASE_PATH')) {
        $url = BASE_PATH . ltrim($url, '/');
    }
    header("Location: $url");
    exit;
}

/**
 * Format number as Rupiah
 */
function formatRupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}

/**
 * Generate Customer ID (AMM-XXXXX)
 */
function generateCustomerId($conn) {
    $result = $conn->query("SELECT customer_id FROM users ORDER BY id DESC LIMIT 1");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastNum = intval(substr($row['customer_id'], 4));
        $newNum = $lastNum + 1;
    } else {
        $newNum = 1;
    }
    return 'AMM-' . str_pad($newNum, 5, '0', STR_PAD_LEFT);
}

/**
 * Generate Order Number (ORD-YYYYMMDD-XXXX)
 */
function generateOrderNumber($conn) {
    $date = date('Ymd');
    $result = $conn->query("SELECT order_number FROM orders WHERE DATE(created_at) = CURDATE() ORDER BY id DESC LIMIT 1");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastNum = intval(substr($row['order_number'], -4));
        $newNum = $lastNum + 1;
    } else {
        $newNum = 1;
    }
    return 'ORD-' . $date . '-' . str_pad($newNum, 4, '0', STR_PAD_LEFT);
}

/**
 * Set flash message
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Display flash message HTML
 */
function displayFlash() {
    $flash = getFlash();
    if ($flash) {
        $typeClass = $flash['type'] === 'success' ? 'flash-success' : ($flash['type'] === 'error' ? 'flash-error' : 'flash-info');
        $icon = $flash['type'] === 'success' ? '' : ($flash['type'] === 'error' ? '' : 'ℹ');
        echo '<div class="flash-message ' . $typeClass . '" id="flashMessage">';
        echo '<span class="flash-icon">' . $icon . '</span>';
        echo '<span>' . htmlspecialchars($flash['message']) . '</span>';
        echo '<button class="flash-close" onclick="this.parentElement.remove()">×</button>';
        echo '</div>';
    }
}

/**
 * Sanitize input
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Get cart count for current user
 */
function getCartCount($conn) {
    if (!isLoggedIn()) return 0;
    $userId = $_SESSION['user_id'];
    $result = $conn->query("SELECT SUM(quantity) as total FROM cart WHERE user_id = $userId");
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

/**
 * CSRF token generation
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF hidden input
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCsrfToken() . '">';
}

/**
 * Get user data by ID
 */
function getUserById($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Get product image URL with fallback
 */
function getProductImage($image) {
    $uploadsRel = BASE_PATH . 'uploads/products/' . $image;
    $path = rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR) . str_replace('/', DIRECTORY_SEPARATOR, $uploadsRel);
    if ($image && file_exists($path)) {
        return $uploadsRel;
    }
    return BASE_PATH . 'assets/img/no-image.png';
}

/**
 * Get order status label
 */
function getOrderStatusLabel($status) {
    $labels = [
        'pending' => ['label' => 'Menunggu', 'class' => 'badge-warning'],
        'processing' => ['label' => 'Diproses', 'class' => 'badge-info'],
        'shipped' => ['label' => 'Dikirim', 'class' => 'badge-primary'],
        'delivered' => ['label' => 'Selesai', 'class' => 'badge-success'],
        'cancelled' => ['label' => 'Dibatalkan', 'class' => 'badge-danger']
    ];
    return $labels[$status] ?? ['label' => $status, 'class' => 'badge-secondary'];
}

/**
 * Get payment status label
 */
function getPaymentStatusLabel($status) {
    $labels = [
        'pending' => ['label' => 'Belum Bayar', 'class' => 'badge-warning'],
        'confirmed' => ['label' => 'Sudah Bayar', 'class' => 'badge-success'],
        'failed' => ['label' => 'Gagal', 'class' => 'badge-danger']
    ];
    return $labels[$status] ?? ['label' => $status, 'class' => 'badge-secondary'];
}

/**
 * Get payment method label
 */
function getPaymentMethodLabel($method) {
    $labels = [
        'cod' => 'Bayar di Tempat (COD)',
        'mbanking' => 'M-Banking',
        'ewallet' => 'E-Wallet'
    ];
    return $labels[$method] ?? $method;
}

// ============================================
// CHAT SYSTEM FUNCTIONS
// ============================================

/**
 * Send message and trigger auto-reply
 */
function sendMessage($senderId, $message, $subject = '') {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, message, subject, message_type) VALUES (?, ?, ?, 'user_message')");
    $stmt->bind_param("iss", $senderId, $message, $subject);
    $messageId = null;
    
    if ($stmt->execute()) {
        $messageId = $conn->insert_id;
        
        // Trigger auto-reply
        triggerAutoReply($senderId, $message, $messageId);
        
        return ['success' => true, 'id' => $messageId];
    }
    
    return ['success' => false, 'error' => $conn->error];
}

/**
 * Trigger auto-reply based on keywords
 */
function triggerAutoReply($userId, $messageText, $messageId) {
    global $conn;
    
    $messageUpper = strtoupper($messageText);
    
    // Get admin user
    $adminRes = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    $admin = $adminRes->fetch_assoc();
    $adminId = $admin ? $admin['id'] : NULL;
    
    // Get active auto-reply templates
    $replyQuery = $conn->query("
        SELECT * FROM auto_reply_templates 
        WHERE is_active = 1 
        ORDER BY priority DESC, id ASC
    ");
    
    $selectedTemplate = null;
    $defaultTemplate = null;
    
    while ($template = $replyQuery->fetch_assoc()) {
        if (empty($template['trigger_keywords'])) {
            $defaultTemplate = $template;
            continue;
        }
        
        $keywords = explode(',', strtoupper($template['trigger_keywords']));
        foreach ($keywords as $kw) {
            $kw = trim($kw);
            if (!empty($kw) && strpos($messageUpper, $kw) !== false) {
                $selectedTemplate = $template;
                break 2;
            }
        }
    }
    
    if (!$selectedTemplate && $defaultTemplate) {
        $selectedTemplate = $defaultTemplate;
    }
    
    if ($selectedTemplate) {
        // Send auto-reply
        $autoReplyStmt = $conn->prepare("
            INSERT INTO messages (sender_id, receiver_id, message, message_type) 
            VALUES (?, ?, ?, 'auto_reply')
        ");
        $autoReplyStmt->bind_param("iis", $adminId, $userId, $selectedTemplate['reply_message']);
        $autoReplyStmt->execute();
    }
}

/**
 * Get unread message count for admin (only user_message type that admin hasn't read)
 */
function getUnreadMessageCount() {
    global $conn;
    
    $result = $conn->query("
        SELECT COUNT(*) as count FROM messages 
        WHERE is_read = 0 AND message_type = 'user_message'
    ");
    $data = $result->fetch_assoc();
    return $data['count'] ?? 0;
}

/**
 * Get unread message count for specific user
 */
function getUserUnreadCount($userId) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count FROM messages 
        WHERE receiver_id = ? AND is_read = 0
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    return $data['count'] ?? 0;
}

/**
 * Get chat history for a user
 */
function getChatHistory($userId, $limit = 50) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT m.*, u.name, u.avatar
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE (m.sender_id = ? OR m.receiver_id = ?)
        ORDER BY m.created_at DESC
        LIMIT ?
    ");
    $stmt->bind_param("iii", $userId, $userId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    
    return array_reverse($messages);
}

/**
 * Get all user chat conversations for admin
 */
function getAllChatConversations() {
    global $conn;
    
    $result = $conn->query("
        SELECT 
            u.id,
            u.name,
            u.email,
            u.customer_id,
            u.avatar,
            (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND message_type = 'user_message') as total_messages,
            (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND is_read = 0 AND message_type = 'user_message') as unread_count,
            (SELECT MAX(created_at) FROM messages WHERE sender_id = u.id) as last_message_time
        FROM users u
        WHERE u.role = 'customer' AND EXISTS (
            SELECT 1 FROM messages WHERE sender_id = u.id
        )
        ORDER BY last_message_time DESC
    ");
    
    $conversations = [];
    while ($row = $result->fetch_assoc()) {
        $conversations[] = $row;
    }
    
    return $conversations;
}

/**
 * Mark messages as read for customer (marks admin_reply and auto_reply sent TO customer)
 */
function markMessagesAsRead($userId) {
    global $conn;
    
    $stmt = $conn->prepare("
        UPDATE messages SET is_read = 1 
        WHERE receiver_id = ? AND is_read = 0
    ");
    $stmt->bind_param("i", $userId);
    return $stmt->execute();
}

/**
 * Mark messages as read for admin (marks user_message FROM customer as read)
 */
function markMessagesAsReadForAdmin($customerId) {
    global $conn;
    
    $stmt = $conn->prepare("
        UPDATE messages SET is_read = 1 
        WHERE sender_id = ? AND is_read = 0 AND message_type = 'user_message'
    ");
    $stmt->bind_param("i", $customerId);
    return $stmt->execute();
}

/**
 * Reply to message
 */
function replyToMessage($adminId, $customerId, $replyText) {
    global $conn;
    
    $stmt = $conn->prepare("
        INSERT INTO messages (sender_id, receiver_id, message, message_type) 
        VALUES (?, ?, ?, 'admin_reply')
    ");
    $stmt->bind_param("iis", $adminId, $customerId, $replyText);
    
    if ($stmt->execute()) {
        return ['success' => true, 'id' => $conn->insert_id];
    }
    
    return ['success' => false, 'error' => $conn->error];
}
