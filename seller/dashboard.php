<?php
// seller/dashboard.php
require_once '../config/database.php';
requireRole('seller');
$pageTitle   = 'Seller Dashboard';
$sidebarRole = 'seller';
$user        = currentUser();

$db = getDB();

$myStore = $db->prepare("SELECT s.*, e.title AS event_title FROM stores s JOIN events e ON s.event_id = e.id WHERE s.seller_id = ? ORDER BY s.created_at DESC LIMIT 1");
$myStore->execute([$user['id']]);
$myStore = $myStore->fetch();

$totalProducts = $db->prepare("SELECT COUNT(*) FROM products p JOIN stores s ON p.store_id = s.id WHERE s.seller_id = ?");
$totalProducts->execute([$user['id']]);
$totalProducts = (int)$totalProducts->fetchColumn();

$pendingOrders = $db->prepare("SELECT COUNT(*) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.seller_id = ? AND o.status = 'pending'");
$pendingOrders->execute([$user['id']]);
$pendingOrders = (int)$pendingOrders->fetchColumn();

$revenue = $db->prepare("SELECT COALESCE(SUM(oi.price * oi.quantity),0) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.seller_id = ? AND o.status = 'done'");
$revenue->execute([$user['id']]);
$revenue = (float)$revenue->fetchColumn();

$recentOrders = $db->prepare("SELECT o.*, u.name AS customer_name FROM orders o JOIN users u ON o.customer_id = u.id JOIN order_items oi ON oi.order_id = o.id WHERE oi.seller_id = ? GROUP BY o.id ORDER BY o.created_at DESC LIMIT 5");
$recentOrders->execute([$user['id']]);
$recentOrders = $recentOrders->fetchAll();

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="main-content">
  <div class="topbar">
    <span class="topbar-title">Seller Dashboard</span>
    <div class="topbar-actions">
      <a href="<?= BASE_URL ?>/seller/manage-products.php" class="btn btn-gold btn-sm">➕ Add Product</a>
    </div>
  </div>

  <div class="ribbon-header">
    <h1>My Store Dashboard ✦</h1>
    <p><?= $myStore ? htmlspecialchars($myStore['store_name']) . ' · ' . htmlspecialchars($myStore['event_title']) : 'Set up your store to start selling.' ?></p>
    <div class="gold-line"></div>
  </div>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon">🍜</div>
      <div>
        <div class="stat-label">Products</div>
        <div class="stat-value"><?= $totalProducts ?></div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">🔔</div>
      <div>
        <div class="stat-label">Pending Orders</div>
        <div class="stat-value"><?= $pendingOrders ?></div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">💰</div>
      <div>
        <div class="stat-label">Total Revenue</div>
        <div class="stat-value" style="font-size:1.1rem;">Rp <?= number_format($revenue, 0, ',', '.') ?></div>
      </div>
    </div>
  </div>

  <?php if (!$myStore): ?>
    <div class="alert alert-warning">
      You haven't set up a store yet. <a href="<?= BASE_URL ?>/seller/apply-event.php" style="font-weight:700;">Apply to an event first →</a>
    </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header">
      <h3>Recent Orders</h3>
      <a href="<?= BASE_URL ?>/seller/manage-orders.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th><th>Action</th></tr>
        </thead>
        <tbody>
          <?php if (empty($recentOrders)): ?>
            <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:32px">No orders yet.</td></tr>
          <?php else: ?>
            <?php foreach ($recentOrders as $ord): ?>
              <tr>
                <td><strong>#<?= str_pad($ord['id'], 4, '0', STR_PAD_LEFT) ?></strong></td>
                <td><?= htmlspecialchars($ord['customer_name']) ?></td>
                <td>Rp <?= number_format($ord['total_price'], 0, ',', '.') ?></td>
                <td><span class="badge badge-<?= $ord['status'] ?>"><?= ucfirst($ord['status']) ?></span></td>
                <td><?= date('d M, H:i', strtotime($ord['created_at'])) ?></td>
                <td><a href="<?= BASE_URL ?>/seller/manage-orders.php?order_id=<?= $ord['id'] ?>" class="btn btn-sm btn-outline">Detail</a></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
