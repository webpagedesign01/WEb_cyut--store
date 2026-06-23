<?php
// seller/manage-store.php
require_once '../config/database.php';
requireRole('seller');
$pageTitle   = 'My Store';
$sidebarRole = 'seller';
$user        = currentUser();

$db = getDB();

$error   = '';
$success = '';

// Get approved event for this seller
$approvedEvents = $db->prepare("SELECT sa.event_id, e.title, e.event_date FROM seller_applications sa JOIN events e ON sa.event_id = e.id WHERE sa.seller_id = ? AND sa.status = 'approved' ORDER BY e.event_date DESC");
$approvedEvents->execute([$user['id']]);
$approvedEvents = $approvedEvents->fetchAll();

// Get existing store
$store = $db->prepare("SELECT s.*, e.title AS event_title FROM stores s JOIN events e ON s.event_id = e.id WHERE s.seller_id = ? ORDER BY s.created_at DESC LIMIT 1");
$store->execute([$user['id']]);
$store = $store->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventId     = (int)($_POST['event_id']    ?? 0);
    $storeName   = trim($_POST['store_name']   ?? '');
    $description = trim($_POST['description']  ?? '');
    $storeId     = (int)($_POST['store_id']    ?? 0);

    if (!$storeName) {
        $error = 'Store name is required.';
    } elseif ($storeId) {
        // Update
        $upd = $db->prepare("UPDATE stores SET store_name=?, description=? WHERE id=? AND seller_id=?");
        $upd->execute([$storeName, $description, $storeId, $user['id']]);
        $success = 'Store updated successfully.';
    } else {
        // Create
        $ins = $db->prepare("INSERT INTO stores (event_id, seller_id, store_name, description, created_at) VALUES (?,?,?,?,NOW())");
        $ins->execute([$eventId, $user['id'], $storeName, $description]);
        $success = 'Store created successfully!';
    }
    // Reload store
    $store = $db->prepare("SELECT s.*, e.title AS event_title FROM stores s JOIN events e ON s.event_id = e.id WHERE s.seller_id = ? ORDER BY s.created_at DESC LIMIT 1");
    $store->execute([$user['id']]);
    $store = $store->fetch();
}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="main-content">
  <div class="topbar">
    <span class="topbar-title">My Store</span>
    <div class="topbar-actions">
      <a href="<?= BASE_URL ?>/seller/manage-products.php" class="btn btn-gold btn-sm">Manage Products →</a>
    </div>
  </div>

  <div class="page-header">
    <h1>Store Setup</h1>
    <p>Configure your store details for the event.</p>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger" data-auto-dismiss><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success" data-auto-dismiss><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <?php if (empty($approvedEvents)): ?>
    <div class="alert alert-warning">
      You need to be approved for an event before creating a store. <a href="<?= BASE_URL ?>/seller/apply-event.php" style="font-weight:700;">Apply to an event →</a>
    </div>
  <?php else: ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:28px;align-items:start;">

      <!-- Store form -->
      <div class="card">
        <div class="card-header"><h3><?= $store ? 'Edit Store' : 'Create Store' ?></h3></div>
        <div class="card-body">
          <form method="POST" action="">
            <?php if ($store): ?>
              <input type="hidden" name="store_id" value="<?= $store['id'] ?>">
            <?php endif; ?>

            <?php if (!$store): ?>
              <div class="form-group">
                <label class="form-label" for="event_id">Event <span class="required">*</span></label>
                <select id="event_id" name="event_id" class="form-control" required>
                  <option value="">— Select event —</option>
                  <?php foreach ($approvedEvents as $ev): ?>
                    <option value="<?= $ev['event_id'] ?>"><?= htmlspecialchars($ev['title']) ?> — <?= date('d M Y', strtotime($ev['event_date'])) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            <?php endif; ?>

            <div class="form-group">
              <label class="form-label" for="store_name">Store Name <span class="required">*</span></label>
              <input type="text" id="store_name" name="store_name" class="form-control"
                     placeholder="e.g. Bakso Mantap"
                     value="<?= htmlspecialchars($store['store_name'] ?? $_POST['store_name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
              <label class="form-label" for="description">Description</label>
              <textarea id="description" name="description" class="form-control" rows="4"
                        placeholder="Tell customers about your store..."><?= htmlspecialchars($store['description'] ?? '') ?></textarea>
            </div>

            <div style="display:flex;gap:10px;">
              <button type="submit" class="btn btn-gold"><?= $store ? '💾 Save Changes' : '🏪 Create Store' ?></button>
              <button type="reset" class="btn btn-reset">Reset</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Store preview -->
      <?php if ($store): ?>
        <div class="card">
          <div class="card-header"><h3>Store Preview</h3></div>
          <div class="card-body">
            <div style="text-align:center;padding:20px 0 30px;">
              <div style="width:80px;height:80px;border-radius:50%;background:var(--gold-pale);border:3px solid var(--gold);display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 16px;">🏪</div>
              <h2 style="color:var(--navy)"><?= htmlspecialchars($store['store_name']) ?></h2>
              <p style="color:var(--muted);font-size:.9rem;margin-top:6px"><?= htmlspecialchars($store['description'] ?: 'No description yet.') ?></p>
              <div style="margin-top:16px;padding:10px 16px;background:var(--gold-pale);border-radius:var(--radius-sm);font-size:.85rem;color:var(--navy);">
                📅 <?= htmlspecialchars($store['event_title']) ?>
              </div>
            </div>
            <a href="<?= BASE_URL ?>/seller/manage-products.php" class="btn btn-primary btn-block">Manage Products →</a>
          </div>
        </div>
      <?php endif; ?>

    </div>
  <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
