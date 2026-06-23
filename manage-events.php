<?php
// organizer/manage-events.php
require_once '../config/database.php';
requireRole('organizer');
$pageTitle   = 'Manage Events';
$sidebarRole = 'organizer';
$user        = currentUser();

$db = getDB();

// Handle status update (organizer can mark as Finished)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $eventId = (int)($_POST['event_id'] ?? 0);
    if ($_POST['action'] === 'finish') {
        $upd = $db->prepare("UPDATE events SET status='finished' WHERE id=? AND organizer_id=?");
        $upd->execute([$eventId, $user['id']]);
    }
    header('Location: /organizer/manage-events.php?msg=updated');
    exit;
}

// Filter
$filterStatus = $_GET['status'] ?? '';
$sql = "SELECT * FROM events WHERE organizer_id = ?";
$params = [$user['id']];
if ($filterStatus) { $sql .= " AND status = ?"; $params[] = $filterStatus; }
$sql .= " ORDER BY created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="main-content">
  <div class="topbar">
    <span class="topbar-title">Manage Events</span>
    <div class="topbar-actions">
      <a href="/organizer/create-event.php" class="btn btn-gold btn-sm">➕ New Event</a>
    </div>
  </div>

  <?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success" data-auto-dismiss>Event status updated successfully.</div>
  <?php endif; ?>

  <!-- Filter bar -->
  <form method="GET" style="display:flex;gap:12px;align-items:center;margin-bottom:24px;">
    <label class="form-label" style="margin:0;white-space:nowrap;">Filter by status:</label>
    <select name="status" class="form-control" style="width:180px;">
      <option value="">All Events</option>
      <?php foreach (['pending','approved','rejected','finished'] as $s): ?>
        <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <a href="/organizer/manage-events.php" class="btn btn-reset btn-sm">Clear</a>
  </form>

  <?php if (empty($events)): ?>
    <div class="empty-state">
      <div class="empty-icon">📅</div>
      <h3>No events found</h3>
      <p>You haven't created any events yet. Get started by creating your first event.</p>
      <a href="/organizer/create-event.php" class="btn btn-gold">Create Event</a>
    </div>
  <?php else: ?>
    <div class="events-grid">
      <?php foreach ($events as $ev): ?>
        <div class="event-card">
          <div class="event-card-banner">
            <h3><?= htmlspecialchars($ev['title']) ?></h3>
          </div>
          <div class="event-card-body">
            <div class="event-meta" style="margin-bottom:14px;">
              <span>📍 <?= htmlspecialchars($ev['location']) ?></span>
              <span>📅 <?= date('d M Y', strtotime($ev['event_date'])) ?></span>
              <span>🕐 Submitted <?= date('d M Y', strtotime($ev['created_at'])) ?></span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
              <span class="badge badge-<?= $ev['status'] ?>"><?= ucfirst($ev['status']) ?></span>
              <div style="display:flex;gap:8px;">
                <a href="/organizer/seller-applications.php?event_id=<?= $ev['id'] ?>" class="btn btn-sm btn-outline">Sellers</a>
                <a href="/organizer/event-orders.php?event_id=<?= $ev['id'] ?>" class="btn btn-sm btn-primary">Orders</a>
                <?php if ($ev['status'] === 'approved'): ?>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                    <input type="hidden" name="action" value="finish">
                    <button type="submit" class="btn btn-sm btn-danger"
                            data-confirm="Mark this event as Finished?">Finish</button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
