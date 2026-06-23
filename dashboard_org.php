<?php
// organizer/dashboard.php
require_once '../config/database.php';
requireRole('organizer');
$pageTitle   = 'Dashboard';
$sidebarRole = 'organizer';
$user        = currentUser();

$db = getDB();

$totalEvents = $db->prepare("SELECT COUNT(*) FROM events WHERE organizer_id = ?");
$totalEvents->execute([$user['id']]);
$totalEvents = (int)$totalEvents->fetchColumn();

$pendingApps = $db->prepare("SELECT COUNT(*) FROM seller_applications sa JOIN events e ON sa.event_id = e.id WHERE e.organizer_id = ? AND sa.status = 'pending'");
$pendingApps->execute([$user['id']]);
$pendingApps = (int)$pendingApps->fetchColumn();

$totalOrders = $db->prepare("SELECT COUNT(*) FROM orders o JOIN events e ON o.event_id = e.id WHERE e.organizer_id = ?");
$totalOrders->execute([$user['id']]);
$totalOrders = (int)$totalOrders->fetchColumn();

$activeEvents = $db->prepare("SELECT COUNT(*) FROM events WHERE organizer_id = ? AND status = 'approved'");
$activeEvents->execute([$user['id']]);
$activeEvents = (int)$activeEvents->fetchColumn();

$recentEvents = $db->prepare("SELECT * FROM events WHERE organizer_id = ? ORDER BY created_at DESC LIMIT 5");
$recentEvents->execute([$user['id']]);
$recentEvents = $recentEvents->fetchAll();

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="main-content">
  <div class="topbar">
    <span class="topbar-title">Organizer Dashboard</span>
    <div class="topbar-actions">
      <a href="/organizer/create-event.php" class="btn btn-gold btn-sm">➕ New Event</a>
    </div>
  </div>

  <div class="ribbon-header">
    <h1>Welcome back, <?= htmlspecialchars($user['name']) ?> ✦</h1>
    <p>Manage your events, review seller applications, and monitor orders.</p>
    <div class="gold-line"></div>
  </div>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon">📅</div>
      <div>
        <div class="stat-label">Total Events</div>
        <div class="stat-value"><?= $totalEvents ?></div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">✅</div>
      <div>
        <div class="stat-label">Active Events</div>
        <div class="stat-value"><?= $activeEvents ?></div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">🏪</div>
      <div>
        <div class="stat-label">Pending Sellers</div>
        <div class="stat-value"><?= $pendingApps ?></div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">📦</div>
      <div>
        <div class="stat-label">Total Orders</div>
        <div class="stat-value"><?= $totalOrders ?></div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h3>Recent Events</h3>
      <a href="/organizer/manage-events.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Event Title</th>
            <th>Location</th>
            <th>Date</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recentEvents)): ?>
            <tr><td colspan="5" style="text-align:center;color:var(--muted);padding:32px">No events yet. <a href="/organizer/create-event.php" style="color:var(--gold)">Create one →</a></td></tr>
          <?php else: ?>
            <?php foreach ($recentEvents as $ev): ?>
              <tr>
                <td><strong><?= htmlspecialchars($ev['title']) ?></strong></td>
                <td><?= htmlspecialchars($ev['location']) ?></td>
                <td><?= date('d M Y', strtotime($ev['event_date'])) ?></td>
                <td><span class="badge badge-<?= $ev['status'] ?>"><?= ucfirst($ev['status']) ?></span></td>
                <td><a href="/organizer/manage-events.php?id=<?= $ev['id'] ?>" class="btn btn-sm btn-outline">Manage</a></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
