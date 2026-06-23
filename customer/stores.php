<?php
// customer/stores.php
require_once '../config/database.php';
requireRole('customer');
$pageTitle   = 'Stores';
$sidebarRole = 'customer';
$user        = currentUser();

$db = getDB();

$eventId = (int)($_GET['event_id'] ?? 0);
$search  = trim($_GET['search'] ?? '');

// Get event info
$event = null;
if ($eventId) {
    $ev = $db->prepare("SELECT * FROM events WHERE id = ? AND status = 'approved'");
    $ev->execute([$eventId]);
    $event = $ev->fetch();
}

$sql = "SELECT s.*, u.name AS seller_name, e.title AS event_title,
        (SELECT COUNT(*) FROM products p WHERE p.store_id = s.id) AS product_count
        FROM stores s JOIN users u ON s.seller_id = u.id JOIN events e ON s.event_id = e.id
        WHERE e.status = 'approved'";
$params = [];
if ($eventId) { $sql .= " AND s.event_id = ?"; $params[] = $eventId; }
if ($search)  { $sql .= " AND (s.store_name LIKE ? OR s.description LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
$sql .= " ORDER BY s.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$stores = $stmt->fetchAll();

$allEvents = $db->query("SELECT id, title FROM events WHERE status='approved' ORDER BY event_date ASC")->fetchAll();

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="main-content">
  <div class="topbar">
    <span class="topbar-title"><?= $event ? htmlspecialchars($event['title']) : 'All Stores' ?></span>
    <div class="topbar-actions">
      <a href="<?= BASE_URL ?>/customer/cart.php" class="btn btn-gold btn-sm">🛒 Cart</a>
    </div>
  </div>

  <div class="page-header">
    <h1><?= $event ? htmlspecialchars($event['title']) : 'All Stores' ?></h1>
    <p>Browse available stands and add your favorites to cart.</p>
  </div>

  <!-- Filters -->
  <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;margin-bottom:24px;">
    <select name="event_id" class="form-control" style="width:220px;">
      <option value="">All Events</option>
      <?php foreach ($allEvents as $ev): ?>
        <option value="<?= $ev['id'] ?>" <?= $eventId == $ev['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ev['title']) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="text" name="search" class="form-control" style="max-width:240px;"
           placeholder="Search stores..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="btn btn-primary btn-sm">Search</button>
    <a href="<?= BASE_URL ?>/customer/stores.php" class="btn btn-reset btn-sm">Clear</a>
  </form>

  <?php if (empty($stores)): ?>
    <div class="empty-state">
      <div class="empty-icon">🏪</div>
      <h3>No stores found</h3>
      <p>No stores match your search. Try a different event or keyword.</p>
    </div>
  <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:18px;">
      <?php foreach ($stores as $store): ?>
        <a href="<?= BASE_URL ?>/customer/product-detail.php?store_id=<?= $store['id'] ?>" style="text-decoration:none;">
          <div class="card" style="cursor:pointer;transition:box-shadow .2s,transform .2s;" onmouseover="this.style.boxShadow='var(--shadow)';this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow='';this.style.transform=''">
            <div style="background:linear-gradient(135deg,var(--navy) 0%,var(--navy-light) 100%);height:70px;display:flex;align-items:center;justify-content:center;font-size:2rem;">🏪</div>
            <div style="padding:16px 18px;">
              <h3 style="color:var(--navy);margin-bottom:4px;"><?= htmlspecialchars($store['store_name']) ?></h3>
              <p style="font-size:.8rem;color:var(--muted);margin-bottom:10px;"><?= htmlspecialchars(substr($store['description'] ?? 'Great products await!',0,80)) ?></p>
              <div style="display:flex;align-items:center;justify-content:space-between;font-size:.78rem;">
                <span style="color:var(--muted);">📅 <?= htmlspecialchars($store['event_title']) ?></span>
                <span style="color:var(--gold);font-weight:600;">🍜 <?= $store['product_count'] ?> items</span>
              </div>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
