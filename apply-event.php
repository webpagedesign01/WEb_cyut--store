<?php
// seller/apply-event.php
require_once '../config/database.php';
requireRole('seller');
$pageTitle   = 'Apply to Event';
$sidebarRole = 'seller';
$user        = currentUser();

$db = getDB();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventId   = (int)($_POST['event_id']   ?? 0);
    $storeName = trim($_POST['store_name']   ?? '');
    $storeDesc = trim($_POST['description']  ?? '');
    $category  = $_POST['category'] ?? '';

    if (!$eventId || !$storeName) {
        $error = 'Please select an event and enter your store name.';
    } else {
        // Check already applied
        $chk = $db->prepare("SELECT id FROM seller_applications WHERE event_id = ? AND seller_id = ?");
        $chk->execute([$eventId, $user['id']]);
        if ($chk->fetch()) {
            $error = 'You have already applied to this event.';
        } else {
            $ins = $db->prepare("INSERT INTO seller_applications (event_id, seller_id, status, created_at) VALUES (?,?,'pending',NOW())");
            $ins->execute([$eventId, $user['id']]);
            $success = 'Application submitted! Waiting for organizer approval.';
        }
    }
}

// Available approved events
$events = $db->prepare("SELECT e.*, u.name AS organizer_name FROM events e JOIN users u ON e.organizer_id = u.id WHERE e.status = 'approved' ORDER BY e.event_date ASC");
$events->execute();
$events = $events->fetchAll();

// My applications
$myApps = $db->prepare("SELECT sa.*, e.title AS event_title, e.event_date FROM seller_applications sa JOIN events e ON sa.event_id = e.id WHERE sa.seller_id = ? ORDER BY sa.created_at DESC");
$myApps->execute([$user['id']]);
$myApps = $myApps->fetchAll();

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="main-content">
  <div class="topbar">
    <span class="topbar-title">Apply to Event</span>
  </div>

  <div class="page-header">
    <h1>Apply to an Event</h1>
    <p>Browse approved events and register your stand.</p>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger" data-auto-dismiss><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success" data-auto-dismiss><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:28px;align-items:start;">

    <!-- Apply form -->
    <div class="card">
      <div class="card-header"><h3>Register Your Stand</h3></div>
      <div class="card-body">
        <?php if (empty($events)): ?>
          <div class="empty-state" style="padding:28px 0;">
            <div class="empty-icon">📅</div>
            <p>No approved events available right now.</p>
          </div>
        <?php else: ?>
          <form method="POST" action="">
            <div class="form-group">
              <label class="form-label" for="event_id">Select Event <span class="required">*</span></label>
              <select id="event_id" name="event_id" class="form-control" required>
                <option value="">— Choose an event —</option>
                <?php foreach ($events as $ev): ?>
                  <option value="<?= $ev['id'] ?>" <?= (isset($_POST['event_id']) && $_POST['event_id'] == $ev['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($ev['title']) ?> — <?= date('d M Y', strtotime($ev['event_date'])) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label" for="store_name">Store Name <span class="required">*</span></label>
              <input type="text" id="store_name" name="store_name" class="form-control"
                     placeholder="e.g. Bakso Mantap"
                     value="<?= htmlspecialchars($_POST['store_name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
              <label class="form-label" for="description">Store Description</label>
              <textarea id="description" name="description" class="form-control" rows="3"
                        placeholder="What will you sell at this event?"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <!-- Category radio -->
            <div class="form-group">
              <label class="form-label">Primary Category <span class="required">*</span></label>
              <div class="radio-group">
                <?php foreach (['food' => '🍜 Food', 'drink' => '🥤 Drinks', 'snack' => '🍿 Snacks', 'merch' => '👕 Merchandise'] as $val => $label): ?>
                  <label class="radio-option">
                    <input type="radio" name="category" value="<?= $val ?>"
                      <?= (($_POST['category'] ?? '') === $val) ? 'checked' : '' ?> required>
                    <?= $label ?>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>

            <div style="display:flex;gap:10px;">
              <button type="submit" class="btn btn-gold">Submit Application</button>
              <button type="reset" class="btn btn-reset">Reset</button>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <!-- My applications -->
    <div class="card">
      <div class="card-header"><h3>My Applications</h3></div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Event</th><th>Date</th><th>Status</th></tr>
          </thead>
          <tbody>
            <?php if (empty($myApps)): ?>
              <tr><td colspan="3" style="text-align:center;color:var(--muted);padding:28px">No applications yet.</td></tr>
            <?php else: ?>
              <?php foreach ($myApps as $app): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($app['event_title']) ?></strong></td>
                  <td><?= date('d M Y', strtotime($app['event_date'])) ?></td>
                  <td><span class="badge badge-<?= $app['status'] ?>"><?= ucfirst($app['status']) ?></span></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<?php include '../includes/footer.php'; ?>
