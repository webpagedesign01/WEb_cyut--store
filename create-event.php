<?php
// organizer/create-event.php
require_once '../config/database.php';
requireRole('organizer');
$pageTitle   = 'Create Event';
$sidebarRole = 'organizer';
$user        = currentUser();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $location    = trim($_POST['location']    ?? '');
    $event_date  = $_POST['event_date']  ?? '';
    $event_type  = $_POST['event_type']  ?? '';

    if (!$title || !$location || !$event_date) {
        $error = 'Title, location, and date are required.';
    } else {
        $db  = getDB();
        $ins = $db->prepare("INSERT INTO events (organizer_id, title, description, location, event_date, status, created_at) VALUES (?,?,?,?,?,'pending',NOW())");
        $ins->execute([$user['id'], $title, $description, $location, $event_date]);
        $success = 'Event submitted for review! Status: Pending.';
    }
}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="main-content">
  <div class="topbar">
    <span class="topbar-title">Create New Event</span>
    <div class="topbar-actions">
      <a href="/organizer/manage-events.php" class="btn btn-outline btn-sm">← My Events</a>
    </div>
  </div>

  <div class="page-header">
    <h1>Create New Event</h1>
    <p>Submit your school event for admin approval. Once approved, you can accept seller registrations.</p>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger" data-auto-dismiss><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?>
      <a href="/organizer/manage-events.php" style="font-weight:700;">View Events →</a>
    </div>
  <?php endif; ?>

  <div class="card" style="max-width:720px;">
    <div class="card-header"><h3>Event Details</h3></div>
    <div class="card-body">
      <form method="POST" action="">

        <div class="form-group">
          <label class="form-label" for="title">Event Title <span class="required">*</span></label>
          <input type="text" id="title" name="title" class="form-control"
                 placeholder="e.g. Market Day 2026"
                 value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
        </div>

        <!-- Event Type radio -->
        <div class="form-group">
          <label class="form-label">Event Type <span class="required">*</span></label>
          <div class="radio-group">
            <?php foreach (['bazaar' => '🛍️ School Bazaar', 'market_day' => '🏪 Market Day', 'cultural' => '🎭 Cultural Festival', 'performance' => '🎤 Performing Arts'] as $val => $label): ?>
              <label class="radio-option">
                <input type="radio" name="event_type" value="<?= $val ?>"
                  <?= (($_POST['event_type'] ?? '') === $val) ? 'checked' : '' ?> required>
                <?= $label ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="description">Description</label>
          <textarea id="description" name="description" class="form-control" rows="4"
                    placeholder="Describe your event, what visitors can expect..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="location">Location <span class="required">*</span></label>
            <input type="text" id="location" name="location" class="form-control"
                   placeholder="e.g. School Auditorium"
                   value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="event_date">Event Date <span class="required">*</span></label>
            <input type="date" id="event_date" name="event_date" class="form-control"
                   value="<?= htmlspecialchars($_POST['event_date'] ?? '') ?>"
                   min="<?= date('Y-m-d') ?>" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="max_sellers">Max Sellers Allowed</label>
            <input type="number" id="max_sellers" name="max_sellers" class="form-control"
                   placeholder="e.g. 20" min="1" max="200"
                   value="<?= htmlspecialchars($_POST['max_sellers'] ?? '') ?>">
            <span class="form-hint">Leave blank for unlimited</span>
          </div>
          <div class="form-group">
            <label class="form-label" for="entry_fee">Seller Stand Fee (Rp)</label>
            <input type="number" id="entry_fee" name="entry_fee" class="form-control"
                   placeholder="e.g. 50000" min="0"
                   value="<?= htmlspecialchars($_POST['entry_fee'] ?? '') ?>">
            <span class="form-hint">Leave blank if free</span>
          </div>
        </div>

        <div style="display:flex;gap:12px;margin-top:8px;">
          <button type="submit" class="btn btn-gold">🚀 Submit Event</button>
          <button type="reset" class="btn btn-reset">Reset Form</button>
        </div>

        <p class="form-hint" style="margin-top:12px;">
          ℹ️ Your event will be reviewed by an admin. Status will start as <strong>Pending</strong>.
        </p>
      </form>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
