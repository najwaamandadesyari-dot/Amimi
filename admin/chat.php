<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

// Get all conversations
$conversations = getAllChatConversations();

// Get unread count
$unreadCount = getUnreadMessageCount();

// If specific user chat is selected, get their messages
$selectedUserId = null;
$selectedUserChat = [];
$selectedUserInfo = null;

if (isset($_GET['user_id'])) {
    $selectedUserId = intval($_GET['user_id']);
    $selectedUserChat = getChatHistory($selectedUserId);
    
    // Get user info
    $userStmt = $conn->prepare("SELECT id, name, email, customer_id, phone FROM users WHERE id = ?");
    $userStmt->bind_param("i", $selectedUserId);
    $userStmt->execute();
    $selectedUserInfo = $userStmt->get_result()->fetch_assoc();
    
    // Mark as read
    markMessagesAsReadForAdmin($selectedUserId);
}

// Second chat window (Dual System)
$selectedUserId2 = null;
$selectedUserChat2 = [];
$selectedUserInfo2 = null;

if (isset($_GET['user_id_2'])) {
    $selectedUserId2 = intval($_GET['user_id_2']);
    $selectedUserChat2 = getChatHistory($selectedUserId2);
    
    $userStmt2 = $conn->prepare("SELECT id, name, email, customer_id, phone FROM users WHERE id = ?");
    $userStmt2->bind_param("i", $selectedUserId2);
    $userStmt2->execute();
    $selectedUserInfo2 = $userStmt2->get_result()->fetch_assoc();
    
    markMessagesAsReadForAdmin($selectedUserId2);
}

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_to_user'])) {
    $customerId = intval($_POST['reply_to_user']);
    $replyText = trim($_POST['reply_message']);
    
    if (!empty($replyText)) {
        $result = replyToMessage($_SESSION['user_id'], $customerId, $replyText);
        if ($result['success']) {
            setFlash('success', ' Balasan terkirim ke pelanggan!');
        } else {
            setFlash('error', ' Gagal mengirim balasan.');
        }
    }
    
    // Maintain dual chat state in redirect
    $redirectUrl = BASE_PATH . 'admin/chat.php?';
    if ($selectedUserId) $redirectUrl .= 'user_id=' . $selectedUserId . '&';
    if ($selectedUserId2) $redirectUrl .= 'user_id_2=' . $selectedUserId2;
    redirect($redirectUrl);
}

$pageTitle = 'Chat Management - Amimi Shop';
include __DIR__ . '/../includes/header.php';
?>

<style>
    .chat-admin-container {
        max-width: 1400px;
        margin: 30px auto;
        padding: 0 20px;
        display: flex;
        gap: 20px;
        min-height: 70vh;
        height: 70vh;
    }

    .conversation-list {
        width: 300px;
        flex-shrink: 0;
        background: var(--card-dark);
        border: 1px solid var(--border-dark);
        border-radius: 12px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    
    .chat-windows-wrapper {
        flex: 1;
        display: flex;
        gap: 20px;
        min-width: 0;
    }

    .conversation-list {
        background: var(--card-dark);
        border: 1px solid var(--border-dark);
        border-radius: 12px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .list-header {
        background: linear-gradient(135deg, #ff5e3a 0%, #e62e00 100%);
        padding: 15px;
        color: #1a1a1a;
        font-weight: 700;
        font-size: 14px;
    }

    .list-header .badge {
        background: rgba(26, 26, 46, 0.3);
        color: #1a1a1a;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
    }

    .conversations {
        overflow-y: auto;
        max-height: 70vh;
        flex: 1;
    }

    .conversation-item {
        padding: 12px 15px;
        border-bottom: 1px solid var(--border-dark);
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
    }

    .conversation-item:hover {
        background: rgba(212, 175, 55, 0.1);
        border-left: 4px solid var(--primary-gold);
        padding-left: 11px;
    }

    .conversation-item.active {
        background: rgba(212, 175, 55, 0.15);
        border-left: 4px solid var(--primary-gold);
        padding-left: 11px;
    }

    .conversation-name {
        font-weight: 600;
        color: var(--text-color);
        font-size: 13px;
        margin-bottom: 4px;
    }

    .conversation-preview {
        font-size: 12px;
        color: var(--text-muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .conversation-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 6px;
        font-size: 11px;
    }

    .conversation-time {
        color: var(--text-muted);
    }

    .unread-badge {
        background: linear-gradient(135deg, #ff5e3a 0%, #e62e00 100%);
        color: #1a1a1a;
        padding: 2px 6px;
        border-radius: 4px;
        font-weight: 700;
        font-size: 11px;
        min-width: 20px;
        text-align: center;
    }

    .empty-list {
        padding: 30px 15px;
        text-align: center;
        color: var(--text-muted);
        font-size: 13px;
    }

    .chat-window {
        flex: 1;
        min-width: 0;
        background: var(--card-dark);
        border: 1px solid var(--border-dark);
        border-radius: 12px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .chat-window-header {
        background: linear-gradient(135deg, #ff5e3a 0%, #e62e00 100%);
        padding: 15px;
        color: #1a1a1a;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .chat-window-header h2 {
        margin: 0;
        font-size: 16px;
    }

    .chat-window-info {
        font-size: 12px;
        opacity: 0.9;
    }

    .empty-window {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: var(--text-muted);
        text-align: center;
        padding: 40px;
    }

    .empty-window-icon {
        font-size: 64px;
        margin-bottom: 16px;
    }

    .chat-messages-window {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        background: #0f0f1e;
        color: #e0e0e0;
    }

    .message-admin {
        margin-bottom: 20px;
        display: flex;
        gap: 12px;
    }

    .message-admin.user {
        justify-content: flex-end;
    }

    .message-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--primary-gold);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #1a1a1a;
        flex-shrink: 0;
        font-size: 12px;
    }

    .message-admin.user .message-avatar {
        order: 2;
    }

    .message-body {
        flex: 1;
        max-width: 60%;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .message-admin.user .message-body {
        align-items: flex-end;
    }

    .message-sender {
        font-size: 11px;
        color: #aaaaaa;
        font-weight: 600;
    }

    .message-text {
        background: #1e1e38;
        padding: 10px 12px;
        border-radius: 6px;
        font-size: 13px;
        line-height: 1.5;
        white-space: pre-wrap;
        word-wrap: break-word;
        color: #e0e0e0;
    }

    .message-admin.user .message-text {
        background: linear-gradient(135deg, #ff5e3a 0%, #e62e00 100%);
        color: #1a1a1a;
    }

    .message-time {
        font-size: 10px;
        color: #888888;
    }

    .reply-form {
        padding: 15px;
        border-top: 1px solid var(--border-dark);
        background: var(--card-dark);
        display: flex;
        gap: 10px;
    }

    .reply-input {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .reply-input textarea {
        padding: 10px;
        background: #0f0f1e;
        border: 1px solid var(--border-dark);
        color: #e0e0e0;
        border-radius: 6px;
        font-family: inherit;
        font-size: 13px;
        resize: none;
        min-height: 80px;
    }

    .reply-input textarea::placeholder {
        color: #666666;
    }

    .reply-input textarea:focus {
        outline: none;
        border-color: var(--primary-gold);
    }

    .reply-buttons {
        display: flex;
        gap: 8px;
    }

    .btn-reply {
        flex: 1;
        padding: 8px 12px;
        background: linear-gradient(135deg, #ff5e3a 0%, #e62e00 100%);
        color: #1a1a1a;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        font-size: 12px;
        transition: all 0.2s ease;
    }

    .btn-reply:hover {
        transform: translateY(-2px);
    }

    @media (max-width: 1024px) {
        .chat-admin-container {
            flex-direction: column;
            height: auto;
        }

        .conversation-list {
            width: 100%;
            height: 300px;
        }
        
        .chat-windows-wrapper {
            flex-direction: column;
        }
    }

    .page-title {
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 5px;
        color: var(--text-color);
    }

    .page-subtitle {
        font-size: 14px;
        color: var(--text-muted);
        margin-bottom: 20px;
    }

    .no-chat-selected {
        text-align: center;
        padding: 40px;
        color: var(--text-muted);
    }
</style>

<div style="max-width: 1400px; margin: 30px auto; padding: 0 20px;">
    <div class="page-title"> Manajemen Chat Pelanggan</div>
    <div class="page-subtitle">
        Kelola percakapan dengan pelanggan • <span style="color: var(--primary-gold);"><?= $unreadCount ?> Pesan belum dibaca</span>
    </div>
</div>

<div class="chat-admin-container">
    <!-- Conversation List -->
    <div class="conversation-list">
        <div class="list-header">
            Percakapan
            <?php if ($unreadCount > 0): ?>
                <span class="badge"><?= $unreadCount ?> Baru</span>
            <?php endif; ?>
        </div>

        <div class="conversations">
            <?php if (empty($conversations)): ?>
                <div class="empty-list">
                     Belum ada percakapan
                </div>
            <?php else: ?>
                <?php foreach ($conversations as $conv): 
                    $isActive1 = $selectedUserId === $conv['id'];
                    $isActive2 = $selectedUserId2 === $conv['id'];
                    $lastTime = $conv['last_message_time'] ? date('d M, H:i', strtotime($conv['last_message_time'])) : '-';
                    
                    // URL builders for dual chat
                    $url1 = BASE_PATH . 'admin/chat.php?user_id=' . $conv['id'] . ($selectedUserId2 ? '&user_id_2=' . $selectedUserId2 : '');
                    $url2 = BASE_PATH . 'admin/chat.php?user_id_2=' . $conv['id'] . ($selectedUserId ? '&user_id=' . $selectedUserId : '');
                ?>
                    <div class="conversation-item <?= ($isActive1 || $isActive2) ? 'active' : '' ?>" style="display:flex; flex-direction:column;">
                        <a href="<?= $url1 ?>" style="text-decoration: none; color: inherit; flex:1;">
                            <div class="conversation-name">
                                <?= htmlspecialchars($conv['name']) ?>
                                <?php if ($conv['customer_id']): ?>
                                    <span style="font-size: 10px; color: var(--text-muted);">(<?= $conv['customer_id'] ?>)</span>
                                <?php endif; ?>
                            </div>
                            <div class="conversation-preview"><?= htmlspecialchars($conv['email']) ?></div>
                            <div class="conversation-meta">
                                <span class="conversation-time"><?= $lastTime ?></span>
                                <?php if ($conv['unread_count'] > 0): ?>
                                    <span class="unread-badge"><?= $conv['unread_count'] ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div style="margin-top: 8px; text-align: right;">
                            <a href="<?= $url2 ?>" style="font-size: 10px; padding: 3px 8px; background: var(--border-dark); color: white; text-decoration: none; border-radius: 4px;">Buka di Chat 2</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Chat Windows Wrapper -->
    <div class="chat-windows-wrapper">
        <!-- Chat Window 1 -->
        <div class="chat-window">
            <?php if (!$selectedUserId): ?>
                <div class="empty-window">
                    <div class="empty-window-icon"></div>
                    <div style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">Pilih Percakapan 1</div>
                    <div style="font-size: 13px;">Klik pelanggan di sisi kiri untuk membaca dan membalas pesan</div>
                </div>
            <?php else: ?>
                <!-- Chat Header -->
                <div class="chat-window-header">
                    <div>
                        <div class="chat-window-header h2">Chat 1: <?= htmlspecialchars($selectedUserInfo['name']) ?></div>
                        <div class="chat-window-info">
                            <a href="<?= BASE_PATH ?>admin/chat.php<?= ($selectedUserId2 ? '?user_id_2='.$selectedUserId2 : '') ?>" style="color:var(--danger); text-decoration:none; font-size:11px; padding:2px 6px; border:1px solid var(--danger); border-radius:4px; margin-right:8px;">Tutup</a>
                            <?= $selectedUserInfo['customer_id'] ?>
                        </div>
                    </div>
                </div>

                <!-- Messages -->
                <div class="chat-messages-window" id="chat-messages-1">
                    <?php if (empty($selectedUserChat)): ?>
                        <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                            <div style="font-size: 48px; margin-bottom: 12px;"></div>
                            <div>Belum ada pesan</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($selectedUserChat as $msg):
                            $isOwner = $msg['sender_id'] == $_SESSION['user_id'];
                            $avatar = strtoupper(substr(explode(' ', $msg['name'])[0], 0, 1));
                        ?>
                            <div class="message-admin <?= $isOwner ? 'user' : '' ?>">
                                <div class="message-avatar"><?= $avatar ?></div>
                                <div class="message-body">
                                    <div class="message-sender"><?= htmlspecialchars($msg['name']) ?></div>
                                    <div class="message-text"><?= htmlspecialchars($msg['message']) ?></div>
                                    <div class="message-time"><?= date('d M Y, H:i', strtotime($msg['created_at'])) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Reply Form -->
                <form method="POST" class="reply-form">
                    <input type="hidden" name="reply_to_user" value="<?= $selectedUserId ?>">
                    <div class="reply-input">
                        <textarea name="reply_message" placeholder="Balas Chat 1..." required></textarea>
                        <div class="reply-buttons">
                            <button type="submit" class="btn-reply"> Kirim ke Chat 1</button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        
        <!-- Chat Window 2 (Dual System) -->
        <?php if ($selectedUserId2): ?>
        <div class="chat-window" style="border-left: 2px solid var(--primary-gold);">
            <!-- Chat Header -->
            <div class="chat-window-header" style="background: linear-gradient(135deg, #16213e 0%, #1a1a2e 100%); color: white; border-bottom: 1px solid var(--primary-gold);">
                <div>
                    <div class="chat-window-header h2" style="color:var(--primary-gold);">Chat 2: <?= htmlspecialchars($selectedUserInfo2['name']) ?></div>
                    <div class="chat-window-info">
                        <a href="<?= BASE_PATH ?>admin/chat.php<?= ($selectedUserId ? '?user_id='.$selectedUserId : '') ?>" style="color:var(--danger); text-decoration:none; font-size:11px; padding:2px 6px; border:1px solid var(--danger); border-radius:4px; margin-right:8px;">Tutup</a>
                        <?= $selectedUserInfo2['customer_id'] ?>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <div class="chat-messages-window" id="chat-messages-2">
                <?php if (empty($selectedUserChat2)): ?>
                    <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                        <div style="font-size: 48px; margin-bottom: 12px;"></div>
                        <div>Belum ada pesan</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($selectedUserChat2 as $msg):
                        $isOwner = $msg['sender_id'] == $_SESSION['user_id'];
                        $avatar = strtoupper(substr(explode(' ', $msg['name'])[0], 0, 1));
                    ?>
                        <div class="message-admin <?= $isOwner ? 'user' : '' ?>">
                            <div class="message-avatar"><?= $avatar ?></div>
                            <div class="message-body">
                                <div class="message-sender"><?= htmlspecialchars($msg['name']) ?></div>
                                <div class="message-text"><?= htmlspecialchars($msg['message']) ?></div>
                                <div class="message-time"><?= date('d M Y, H:i', strtotime($msg['created_at'])) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Reply Form -->
            <form method="POST" class="reply-form">
                <input type="hidden" name="reply_to_user" value="<?= $selectedUserId2 ?>">
                <div class="reply-input">
                    <textarea name="reply_message" placeholder="Balas Chat 2..." required></textarea>
                    <div class="reply-buttons">
                        <button type="submit" class="btn-reply" style="background: linear-gradient(135deg, #16213e 0%, #1a1a2e 100%); color: var(--primary-gold); border: 1px solid var(--primary-gold);"> Kirim ke Chat 2</button>
                    </div>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto-scroll to bottom
document.addEventListener('DOMContentLoaded', function() {
    const messagesWindow1 = document.getElementById('chat-messages-1');
    if (messagesWindow1) {
        messagesWindow1.scrollTop = messagesWindow1.scrollHeight;
    }
    const messagesWindow2 = document.getElementById('chat-messages-2');
    if (messagesWindow2) {
        messagesWindow2.scrollTop = messagesWindow2.scrollHeight;
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
