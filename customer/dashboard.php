<?php
// customer/dashboard.php
require_once '../config/database.php';
requireRole('customer');
$pageTitle   = 'Dashboard';
$sidebarRole = 'customer';
$user        = currentUser();

$db = getDB();

$totalOrders = $db->prepare("SELECT COUNT(*) FROM orders WHERE customer_id = ?");
$totalOrders->execute([$user['id']]);
$totalOrders = (int)$totalOrders->fetchColumn();

$totalSpent = $db->prepare("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE customer_id = ? AND status='done'");
$totalSpent->execute([$user['id']]);
$totalSpent = (float)$totalSpent->fetchColumn();

$cartCount = $db->prepare("SELECT COALESCE(SUM(quantity),0) FROM carts WHERE customer_id = ?");
$cartCount->execute([$user['id']]);
$cartCount = (int)$cartCount->fetchColumn();

$upcomingEvents = $db->query("SELECT * FROM events WHERE status='approved' ORDER BY event_date ASC LIMIT 4")->fetchAll();

$recentOrders = $db->prepare("SELECT o.*, e.title AS event_title FROM orders o JOIN events e ON o.event_id = e.id WHERE o.customer_id = ? ORDER BY o.created_at DESC LIMIT 5");
$recentOrders->execute([$user['id']]);
$recentOrders = $recentOrders->fetchAll();

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="main-content">
  <div class="topbar">
    <span class="topbar-title">My Dashboard</span>
    <div class="topbar-actions">
      <a href="<?= BASE_URL ?>/customer/cart.php" class="btn btn-gold btn-sm">🛒 Cart <?= $cartCount > 0 ? "($cartCount)" : '' ?></a>
    </div>
  </div>

  <div class="ribbon-header">
    <h1>Hello, <?= htmlspecialchars($user['name']) ?>! ✦</h1>
    <p>Browse events, discover stores, and enjoy your school market experience.</p>
    <div class="gold-line"></div>
  </div>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon">📦</div>
      <div>
        <div class="stat-label">Total Orders</div>
        <div class="stat-value"><?= $totalOrders ?></div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">🛒</div>
      <div>
        <div class="stat-label">Items in Cart</div>
        <div class="stat-value"><?= $cartCount ?></div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">💰</div>
      <div>
        <div class="stat-label">Total Spent</div>
        <div class="stat-value" style="font-size:1.05rem;">Rp <?= number_format($totalSpent, 0, ',', '.') ?></div>
      </div>
    </div>
  </div>

  <!-- Upcoming events -->
  <div style="margin-bottom:28px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
      <h2 style="font-size:1.2rem;color:var(--navy);">Upcoming Events</h2>
      <a href="<?= BASE_URL ?>/customer/events.php" class="btn btn-outline btn-sm">See All</a>
    </div>
    <?php if (empty($upcomingEvents)): ?>
      <p style="color:var(--muted)">No events available right now.</p>
    <?php else: ?>
      <div class="events-grid">
        <?php foreach ($upcomingEvents as $ev): ?>
          <div class="event-card">
            <div class="event-card-banner"><h3><?= htmlspecialchars($ev['title']) ?></h3></div>
            <div class="event-card-body">
              <div class="event-meta" style="margin-bottom:14px;">
                <span>📍 <?= htmlspecialchars($ev['location']) ?></span>
                <span>📅 <?= date('d M Y', strtotime($ev['event_date'])) ?></span>
              </div>
              <a href="<?= BASE_URL ?>/customer/stores.php?event_id=<?= $ev['id'] ?>" class="btn btn-primary btn-sm btn-block">Browse Stores</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Recent orders -->
  <div class="card">
    <div class="card-header">
      <h3>Recent Orders</h3>
      <a href="<?= BASE_URL ?>/customer/order-history.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Order #</th><th>Event</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
          <?php if (empty($recentOrders)): ?>
            <tr><td colspan="5" style="text-align:center;color:var(--muted);padding:32px">No orders yet. <a href="<?= BASE_URL ?>/customer/events.php" style="color:var(--gold)">Start shopping →</a></td></tr>
          <?php else: ?>
            <?php foreach ($recentOrders as $ord): ?>
              <tr>
                <td><strong>#<?= str_pad($ord['id'],4,'0',STR_PAD_LEFT) ?></strong></td>
                <td><?= htmlspecialchars($ord['event_title']) ?></td>
                <td>Rp <?= number_format($ord['total_price'],0,',','.') ?></td>
                <td><span class="badge badge-<?= $ord['status'] ?>"><?= ucfirst($ord['status']) ?></span></td>
                <td><?= date('d M Y',strtotime($ord['created_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
