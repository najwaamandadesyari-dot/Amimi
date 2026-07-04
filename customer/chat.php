<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
if (isAdmin()) {
    redirect(BASE_PATH . 'admin/chat.php');
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];

// Handle message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    $subject = trim($_POST['subject'] ?? '');
    
    if (!empty($message)) {
        $result = sendMessage($userId, $message, $subject);
        if ($result['success']) {
            setFlash('success', ' Pesan terkirim! Admin akan meresponnya segera.');
        } else {
            setFlash('error', ' Gagal mengirim pesan.');
        }
        redirect(BASE_PATH . 'customer/chat.php');
    }
}

// Get chat history
$chatHistory = getChatHistory($userId);
markMessagesAsRead($userId);

$pageTitle = 'Hubungi Kami - Amimi Shop';
include __DIR__ . '/../includes/header.php';
?>

<style>
    .chat-container {
        max-width: 900px;
        margin: 30px auto;
        padding: 0 20px;
        min-height: 80vh;
        display: flex;
        flex-direction: column;
    }

    .chat-header {
        background: linear-gradient(135deg, #ff5e3a 0%, #e62e00 100%);
        border-radius: 12px 12px 0 0;
        padding: 25px;
        color: #1a1a1a;
        margin-bottom: 0;
    }

    .chat-header h1 {
        margin: 0 0 8px 0;
        font-size: 28px;
    }

    .chat-header p {
        margin: 0;
        opacity: 0.9;
        font-size: 14px;
    }

    .chat-box {
        background: var(--card-dark);
        border: 1px solid var(--border-dark);
        border-radius: 0 0 12px 12px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 25px;
        background: #0f0f1e;
        max-height: 450px;
        scroll-behavior: smooth;
        color: #e0e0e0;
    }

    .message {
        margin-bottom: 20px;
        display: flex;
        gap: 12px;
        animation: fadeIn 0.3s ease-in;
        justify-content: flex-start;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .message.owner {
        justify-content: flex-end;
    }

    .message-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--primary-gold);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #1a1a1a;
        flex-shrink: 0;
    }

    .message.owner .message-avatar {
        order: 2;
    }

    .message-content {
        flex: none;
        max-width: 70%;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .message.owner .message-content {
        align-items: flex-end;
    }

    .message-sender {
        font-size: 12px;
        color: #aaaaaa;
        font-weight: 600;
    }

    .message-text {
        background: #1e1e38;
        padding: 12px 16px;
        border-radius: 8px;
        line-height: 1.6;
        word-wrap: break-word;
        white-space: pre-wrap;
        color: #e0e0e0;
    }

    .message.owner .message-text {
        background: linear-gradient(135deg, #ff5e3a 0%, #e62e00 100%);
        color: #1a1a1a;
    }

    .message-time {
        font-size: 11px;
        color: #888888;
    }

    .message-badge {
        font-size: 10px;
        padding: 2px 8px;
        border-radius: 4px;
        margin-top: 4px;
        width: fit-content;
    }

    .badge-auto {
        background-color: rgba(100, 200, 255, 0.2);
        color: #64c8ff;
    }

    .badge-admin {
        background-color: rgba(212, 175, 55, 0.2);
        color: var(--primary-gold);
    }

    .chat-input-area {
        padding: 20px;
        background: var(--card-dark);
        border-top: 1px solid var(--border-dark);
    }

    .form-group {
        margin-bottom: 12px;
    }

    .form-group:last-child {
        margin-bottom: 0;
    }

    .form-group label {
        display: block;
        font-size: 12px;
        color: var(--text-muted);
        margin-bottom: 6px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 10px 12px;
        background: #0f0f1e;
        border: 1px solid var(--border-dark);
        color: #e0e0e0;
        border-radius: 6px;
        font-family: inherit;
        font-size: 13px;
    }

    .form-group input::placeholder,
    .form-group textarea::placeholder {
        color: #666666;
    }

    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: var(--primary-gold);
        box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
    }

    .form-group textarea {
        resize: vertical;
        min-height: 80px;
        max-height: 150px;
    }

    .btn-send {
        width: 100%;
        padding: 12px;
        background: linear-gradient(135deg, #ff5e3a 0%, #e62e00 100%);
        color: #1a1a1a;
        border: none;
        border-radius: 6px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 14px;
    }

    .btn-send:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(212, 175, 55, 0.3);
    }

    .btn-send:active {
        transform: translateY(0);
    }

    .empty-chat {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px;
        text-align: center;
        color: #888888;
    }

    .empty-chat-icon {
        font-size: 64px;
        margin-bottom: 16px;
    }

    .empty-chat-title {
        font-size: 18px;
        font-weight: 700;
        color: #e0e0e0;
        margin-bottom: 8px;
    }

    .empty-chat-desc {
        font-size: 14px;
        margin-bottom: 20px;
    }

    .info-box {
        background: rgba(212, 175, 55, 0.1);
        border-left: 4px solid var(--primary-gold);
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        font-size: 13px;
        line-height: 1.6;
    }

    .info-box strong {
        color: var(--primary-gold);
    }

    @media (max-width: 768px) {
        .chat-messages {
            max-height: 300px;
        }

        .message-content {
            max-width: 85%;
        }

        .chat-container {
            margin: 15px auto;
        }

        .chat-header {
            padding: 15px;
        }

        .chat-header h1 {
            font-size: 22px;
        }
    }
</style>

<div class="chat-container">
    <div class="chat-header">
        <h1> Hubungi Kami</h1>
        <p>Halo <?= htmlspecialchars($userName) ?>! Kirim pertanyaan kepada tim customer service kami</p>
    </div>

    <div class="chat-box">
        <div class="chat-messages" id="chatMessages">
            <?php if (empty($chatHistory)): ?>
                <div class="empty-chat">
                    <div class="empty-chat-icon"></div>
                    <div class="empty-chat-title">Belum Ada Pesan</div>
                    <div class="empty-chat-desc">Kirim pesan pertamamu untuk mulai percakapan dengan tim customer service kami</div>
                    <div style="font-size: 12px; color: var(--text-muted);">
                        Kami siap membantu Anda 24/7!
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($chatHistory as $msg): 
                    $isOwner = $msg['sender_id'] == $userId;
                    $avatar = strtoupper(substr(explode(' ', $msg['name'])[0], 0, 1));
                ?>
                    <div class="message <?= $isOwner ? 'owner' : '' ?>">
                        <div class="message-avatar"><?= $avatar ?></div>
                        <div class="message-content">
                            <div class="message-sender">
                                <?= htmlspecialchars($msg['name']) ?>
                            </div>
                            <div class="message-text"><?= htmlspecialchars($msg['message']) ?></div>
                            <div style="display: flex; align-items: center; gap: 8px; justify-content: <?= $isOwner ? 'flex-end' : 'flex-start' ?>;">
                                <div class="message-time"><?= date('d M, H:i', strtotime($msg['created_at'])) ?></div>
                                <?php if ($msg['message_type'] === 'auto_reply'): ?>
                                    <div class="message-badge badge-auto"> Auto</div>
                                <?php elseif ($msg['message_type'] === 'admin_reply'): ?>
                                    <div class="message-badge badge-admin"> Admin</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="chat-input-area">
            <div class="info-box">
                <strong> Info:</strong> Respon auto akan dikirim segera, kemudian admin akan membalas pertanyaan Anda dalam 1-2 jam kerja.
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="subject">Subjek (Opsional)</label>
                    <input type="text" id="subject" name="subject" placeholder="Contoh: Pertanyaan tentang pengiriman" maxlength="150">
                </div>

                <div class="form-group">
                    <label for="message">Pesan <span style="color: var(--primary-gold);">*</span></label>
                    <textarea id="message" name="message" placeholder="Tulis pertanyaan atau masalahmu di sini..." required></textarea>
                </div>

                <button type="submit" class="btn-send"> Kirim Pesan</button>
            </form>
        </div>
    </div>

    <div style="margin-top: 20px; padding: 15px; background: rgba(212, 175, 55, 0.1); border-radius: 6px; text-align: center; font-size: 13px; color: var(--text-muted);">
        Butuh bantuan lebih cepat? Hubungi kami: <strong style="color: var(--primary-gold);"> +62 812-3456-7890</strong> | <strong style="color: var(--primary-gold);"> support@amimi.shop</strong>
    </div>
</div>

<script>
// Auto-scroll to bottom when new messages appear
document.addEventListener('DOMContentLoaded', function() {
    const chatMessages = document.getElementById('chatMessages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Focus on message textarea
    const messageInput = document.getElementById('message');
    if (messageInput && document.querySelectorAll('.message').length > 0) {
        messageInput.focus();
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
