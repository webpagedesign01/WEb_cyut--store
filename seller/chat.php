<?php
// seller/chat.php
require_once '../config/database.php';
requireRole('seller');
$pageTitle   = 'Chat';
$sidebarRole = 'seller';
$user        = currentUser();

$db = getDB();

// Send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'], $_POST['customer_id'])) {
    $customerId = (int)$_POST['customer_id'];
    $message    = trim($_POST['message']);
    if ($message) {
        $ins = $db->prepare("INSERT INTO chats (customer_id, seller_id, sender_id, message, is_read, created_at) VALUES (?,?,?,?,0,NOW())");
        $ins->execute([$customerId, $user['id'], $user['id'], $message]);
    }
    header('Location: ' . BASE_URL . '/seller/chat.php?with=' . $customerId);
    exit;
}

// Conversations list
$conversations = $db->prepare("SELECT DISTINCT u.id, u.name, u.email,
    (SELECT message FROM chats WHERE customer_id=u.id AND seller_id=? ORDER BY created_at DESC LIMIT 1) AS last_msg,
    (SELECT created_at FROM chats WHERE customer_id=u.id AND seller_id=? ORDER BY created_at DESC LIMIT 1) AS last_time,
    (SELECT COUNT(*) FROM chats WHERE customer_id=u.id AND seller_id=? AND sender_id != ? AND is_read=0) AS unread
    FROM chats c JOIN users u ON u.id = c.customer_id WHERE c.seller_id = ? ORDER BY last_time DESC");
$conversations->execute([$user['id'], $user['id'], $user['id'], $user['id'], $user['id']]);
$conversations = $conversations->fetchAll();

$activeCustomerId = (int)($_GET['with'] ?? ($conversations[0]['id'] ?? 0));
$messages = [];
$activeCustomer = null;

if ($activeCustomerId) {
    $acStmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $acStmt->execute([$activeCustomerId]);
    $activeCustomer = $acStmt->fetch();

    $msgStmt = $db->prepare("SELECT * FROM chats WHERE customer_id = ? AND seller_id = ? ORDER BY created_at ASC");
    $msgStmt->execute([$activeCustomerId, $user['id']]);
    $messages = $msgStmt->fetchAll();

    // Mark as read
    $db->prepare("UPDATE chats SET is_read=1 WHERE customer_id=? AND seller_id=? AND sender_id!=?")->execute([$activeCustomerId, $user['id'], $user['id']]);
}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="main-content">
  <div class="topbar">
    <span class="topbar-title">Customer Chat</span>
  </div>

  <div class="card" style="padding:0;overflow:hidden;">
    <div class="chat-container" style="height:calc(100vh - 160px);">

      <!-- Conversation list -->
      <div class="chat-list">
        <div style="padding:14px 18px;border-bottom:1px solid var(--border);font-size:.8rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:1px;">Customers</div>
        <?php if (empty($conversations)): ?>
          <div class="empty-state" style="padding:30px 16px;">
            <div class="empty-icon" style="font-size:2rem">💬</div>
            <p style="font-size:.82rem">No conversations yet.</p>
          </div>
        <?php else: ?>
          <?php foreach ($conversations as $conv): ?>
            <a href="<?= BASE_URL ?>/seller/chat.php?with=<?= $conv['id'] ?>" style="text-decoration:none;">
              <div class="chat-item <?= $activeCustomerId == $conv['id'] ? 'active' : '' ?>">
                <div class="user-avatar" style="width:38px;height:38px;font-size:.8rem;flex-shrink:0;">
                  <?= strtoupper(substr($conv['name'],0,2)) ?>
                </div>
                <div style="flex:1;min-width:0;">
                  <div class="chat-name"><?= htmlspecialchars($conv['name']) ?></div>
                  <div class="chat-preview"><?= htmlspecialchars($conv['last_msg'] ?? '') ?></div>
                </div>
                <?php if ($conv['unread'] > 0): ?>
                  <div class="unread-dot"></div>
                <?php endif; ?>
              </div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Chat area -->
      <div class="chat-main">
        <?php if ($activeCustomer): ?>
          <!-- Chat header -->
          <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;">
            <div class="user-avatar"><?= strtoupper(substr($activeCustomer['name'],0,2)) ?></div>
            <div>
              <div style="font-weight:700;color:var(--navy);"><?= htmlspecialchars($activeCustomer['name']) ?></div>
              <div style="font-size:.75rem;color:var(--muted);">Customer</div>
            </div>
          </div>

          <!-- Messages -->
          <div class="chat-messages">
            <?php if (empty($messages)): ?>
              <div style="text-align:center;color:var(--muted);font-size:.85rem;margin:auto;">Start the conversation!</div>
            <?php else: ?>
              <?php foreach ($messages as $msg): ?>
                <?php $isMine = $msg['sender_id'] == $user['id']; ?>
                <div class="msg <?= $isMine ? 'sent' : 'received' ?>">
                  <div class="msg-text"><?= htmlspecialchars($msg['message']) ?></div>
                  <div class="msg-time"><?= date('H:i', strtotime($msg['created_at'])) ?></div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <!-- Input -->
          <form method="POST" action="" class="chat-input-area">
            <input type="hidden" name="customer_id" value="<?= $activeCustomerId ?>">
            <input type="text" name="message" class="form-control" placeholder="Type a message..." autocomplete="off" required>
            <button type="submit" class="btn btn-gold" style="flex-shrink:0;">Send</button>
          </form>
        <?php else: ?>
          <div class="empty-state" style="margin:auto;">
            <div class="empty-icon">💬</div>
            <h3>No chat selected</h3>
            <p>Select a conversation from the left.</p>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
