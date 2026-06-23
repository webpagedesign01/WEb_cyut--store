<?php
// customer/events.php
require_once '../config/database.php';
requireRole('customer');
$pageTitle   = 'Browse Events';
$sidebarRole = 'customer';
$user        = currentUser();

$db = getDB();

$search = trim($_GET['search'] ?? '');
$sql    = "SELECT e.*, u.name AS organizer_name, (SELECT COUNT(*) FROM stores s WHERE s.event_id = e.id) AS store_count FROM events e JOIN users u ON e.organizer_id = u.id WHERE e.status = 'approved'";
$params = [];
if ($search) {
    $sql .= " AND (e.title LIKE ? OR e.location LIKE ?)";
    $params = ["%$search%", "%$search%"];
}
$sql .= " ORDER BY e.event_date ASC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="main-content">
  <div class="topbar">
    <span class="topbar-title">Browse Events</span>
    <div class="topbar-actions">
      <a href="<?= BASE_URL ?>/customer/cart.php" class="btn btn-gold btn-sm">🛒 My Cart</a>
    </div>
  </div>

  <div class="page-header">
    <h1>School Events</h1>
    <p>Discover events near you and find your favorite stands.</p>
  </div>

  <!-- Search -->
  <form method="GET" style="display:flex;gap:12px;margin-bottom:28px;">
    <input type="text" name="search" class="form-control" style="max-width:340px;"
           placeholder="Search events or locations..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="btn btn-primary">Search</button>
    <?php if ($search): ?>
      <a href="<?= BASE_URL ?>/customer/events.php" class="btn btn-reset">Clear</a>
    <?php endif; ?>
  </form>

  <?php if (empty($events)): ?>
    <div class="empty-state">
      <div class="empty-icon">📅</div>
      <h3>No events found</h3>
      <p><?= $search ? "No events match \"$search\"." : 'No events are available right now. Check back soon!' ?></p>
    </div>
  <?php else: ?>
    <p style="color:var(--muted);font-size:.85rem;margin-bottom:20px;"><?= count($events) ?> event(s) available</p>
    <div class="events-grid">
      <?php foreach ($events as $ev): ?>
        <div class="event-card">
          <div class="event-card-banner"><h3><?= htmlspecialchars($ev['title']) ?></h3></div>
          <div class="event-card-body">
            <div class="event-meta" style="margin-bottom:14px;">
              <span>📍 <?= htmlspecialchars($ev['location']) ?></span>
              <span>📅 <?= date('d M Y', strtotime($ev['event_date'])) ?></span>
              <span>🏪 <?= $ev['store_count'] ?> store(s)</span>
              <span>👤 By <?= htmlspecialchars($ev['organizer_name']) ?></span>
            </div>
            <?php if ($ev['description']): ?>
              <p style="font-size:.82rem;color:var(--muted);margin-bottom:14px;"><?= htmlspecialchars(substr($ev['description'],0,100)) ?>...</p>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/customer/stores.php?event_id=<?= $ev['id'] ?>" class="btn btn-gold btn-block">Explore Stores →</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
