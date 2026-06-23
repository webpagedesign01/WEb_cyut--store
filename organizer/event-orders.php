<?php
// organizer/event-orders.php
require_once '../config/database.php';
requireRole('organizer');
$pageTitle   = 'Event Orders';
$sidebarRole = 'organizer';
$user        = currentUser();

$db = getDB();

$myEvents = $db->prepare("SELECT id, title FROM events WHERE organizer_id = ? ORDER BY event_date DESC");
$myEvents->execute([$user['id']]);
$myEvents = $myEvents->fetchAll();

$filterEvent  = (int)($_GET['event_id'] ?? 0);
$filterStatus = $_GET['status'] ?? '';

$sql = "SELECT o.*, u.name AS customer_name, e.title AS event_title
        FROM orders o
        JOIN events e ON o.event_id = e.id
        JOIN users u ON o.customer_id = u.id
        WHERE e.organizer_id = ?";
$params = [$user['id']];
if ($filterEvent)  { $sql .= " AND o.event_id = ?"; $params[] = $filterEvent; }
if ($filterStatus) { $sql .= " AND o.status = ?";   $params[] = $filterStatus; }
$sql .= " ORDER BY o.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Revenue summary
$revSql = "SELECT SUM(o.total_price) AS revenue FROM orders o JOIN events e ON o.event_id = e.id WHERE e.organizer_id = ? AND o.status = 'done'";
$revParam = [$user['id']];
if ($filterEvent) { $revSql .= " AND o.event_id = ?"; $revParam[] = $filterEvent; }
$revenue = $db->prepare($revSql);
$revenue->execute($revParam);
$revenue = (float)$revenue->fetchColumn();

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="main-content">
  <div class="topbar">
    <span class="topbar-title">Event Orders</span>
  </div>

  <div class="page-header">
    <h1>Event Orders</h1>
    <p>Monitor all orders placed across your events.</p>
  </div>

  <div class="stats-grid" style="max-width:600px;margin-bottom:24px;">
    <div class="stat-card">
      <div class="stat-icon">📦</div>
      <div>
        <div class="stat-label">Total Orders</div>
        <div class="stat-value"><?= count($orders) ?></div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">💰</div>
      <div>
        <div class="stat-label">Completed Revenue</div>
        <div class="stat-value" style="font-size:1.1rem;">Rp <?= number_format($revenue, 0, ',', '.') ?></div>
      </div>
    </div>
  </div>

  <!-- Filters -->
  <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;margin-bottom:24px;">
    <select name="event_id" class="form-control" style="width:220px;">
      <option value="">All Events</option>
      <?php foreach ($myEvents as $ev): ?>
        <option value="<?= $ev['id'] ?>" <?= $filterEvent == $ev['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ev['title']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="status" class="form-control" style="width:160px;">
      <option value="">All Status</option>
      <?php foreach (['pending','processing','ready','done','cancelled'] as $s): ?>
        <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <a href="<?= BASE_URL ?>/organizer/event-orders.php" class="btn btn-reset btn-sm">Clear</a>
  </form>

  <div class="card">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Order #</th>
            <th>Customer</th>
            <th>Event</th>
            <th>Total</th>
            <th>Status</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($orders)): ?>
            <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:40px">No orders found.</td></tr>
          <?php else: ?>
            <?php foreach ($orders as $ord): ?>
              <tr>
                <td><strong>#<?= str_pad($ord['id'], 4, '0', STR_PAD_LEFT) ?></strong></td>
                <td><?= htmlspecialchars($ord['customer_name']) ?></td>
                <td><?= htmlspecialchars($ord['event_title']) ?></td>
                <td>Rp <?= number_format($ord['total_price'], 0, ',', '.') ?></td>
                <td>
                  <?php
                  $badgeMap = ['pending'=>'pending','processing'=>'processing','ready'=>'ready','done'=>'done','cancelled'=>'cancelled'];
                  $bc = $badgeMap[$ord['status']] ?? 'pending';
                  ?>
                  <span class="badge badge-<?= $bc ?>"><?= ucfirst($ord['status']) ?></span>
                </td>
                <td><?= date('d M Y, H:i', strtotime($ord['created_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
