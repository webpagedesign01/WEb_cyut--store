<?php
// organizer/seller-applications.php
require_once '../config/database.php';
requireRole('organizer');
$pageTitle   = 'Seller Applications';
$sidebarRole = 'organizer';
$user        = currentUser();

$db = getDB();

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['app_id'])) {
    $appId  = (int)$_POST['app_id'];
    $action = $_POST['action'];
    if (in_array($action, ['approved', 'rejected'])) {
        $upd = $db->prepare("UPDATE seller_applications sa
                              JOIN events e ON sa.event_id = e.id
                              SET sa.status = ?
                              WHERE sa.id = ? AND e.organizer_id = ?");
        $upd->execute([$action, $appId, $user['id']]);
    }
    header('Location: ' . BASE_URL . '/organizer/seller-applications.php?msg=' . $action);
    exit;
}

// Get organizer's events for filter
$myEvents = $db->prepare("SELECT id, title FROM events WHERE organizer_id = ? AND status = 'approved' ORDER BY event_date DESC");
$myEvents->execute([$user['id']]);
$myEvents = $myEvents->fetchAll();

$filterEvent  = (int)($_GET['event_id'] ?? 0);
$filterStatus = $_GET['status'] ?? '';

$sql = "SELECT sa.*, e.title AS event_title, u.name AS seller_name, u.email AS seller_email, u.phone AS seller_phone
        FROM seller_applications sa
        JOIN events e ON sa.event_id = e.id
        JOIN users u ON sa.seller_id = u.id
        WHERE e.organizer_id = ?";
$params = [$user['id']];
if ($filterEvent)  { $sql .= " AND sa.event_id = ?"; $params[] = $filterEvent; }
if ($filterStatus) { $sql .= " AND sa.status = ?";   $params[] = $filterStatus; }
$sql .= " ORDER BY sa.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="main-content">
  <div class="topbar">
    <span class="topbar-title">Seller Applications</span>
  </div>

  <?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success" data-auto-dismiss>Application <?= htmlspecialchars($_GET['msg']) ?> successfully.</div>
  <?php endif; ?>

  <div class="page-header">
    <h1>Seller Applications</h1>
    <p>Review and approve sellers who want to participate in your events.</p>
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
      <?php foreach (['pending','approved','rejected'] as $s): ?>
        <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <a href="<?= BASE_URL ?>/organizer/seller-applications.php" class="btn btn-reset btn-sm">Clear</a>
  </form>

  <div class="card">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Seller</th>
            <th>Contact</th>
            <th>Event</th>
            <th>Applied</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($applications)): ?>
            <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:40px">No applications found.</td></tr>
          <?php else: ?>
            <?php foreach ($applications as $i => $app): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><strong><?= htmlspecialchars($app['seller_name']) ?></strong></td>
                <td style="font-size:.82rem;color:var(--muted);">
                  <?= htmlspecialchars($app['seller_email']) ?><br>
                  <?= htmlspecialchars($app['seller_phone'] ?? '-') ?>
                </td>
                <td><?= htmlspecialchars($app['event_title']) ?></td>
                <td><?= date('d M Y', strtotime($app['created_at'])) ?></td>
                <td><span class="badge badge-<?= $app['status'] ?>"><?= ucfirst($app['status']) ?></span></td>
                <td>
                  <?php if ($app['status'] === 'pending'): ?>
                    <form method="POST" style="display:flex;gap:6px;">
                      <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                      <button type="submit" name="action" value="approved" class="btn btn-success btn-sm">✓ Approve</button>
                      <button type="submit" name="action" value="rejected" class="btn btn-danger btn-sm"
                              data-confirm="Reject this seller?">✕ Reject</button>
                    </form>
                  <?php else: ?>
                    <span class="text-muted" style="font-size:.82rem;">—</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
