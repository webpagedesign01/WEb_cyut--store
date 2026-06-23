<?php
// seller/manage-orders.php
require_once '../config/database.php';
requireRole('seller');
$pageTitle   = 'Manage Orders';
$sidebarRole = 'seller';
$user        = currentUser();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $orderId  = (int)$_POST['order_id'];
    $newStatus = $_POST['status'];
    $allowed  = ['pending','processing','ready','done','cancelled'];
    if (in_array($newStatus, $allowed)) {
        // Verify the order contains items from this seller
        $chk = $db->prepare("SELECT o.id FROM orders o JOIN order_items oi ON oi.order_id = o.id WHERE o.id = ? AND oi.seller_id = ? LIMIT 1");
        $chk->execute([$orderId, $user['id']]);
        if ($chk->fetch()) {
            $upd = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $upd->execute([$newStatus, $orderId]);
        }
    }
    header('Location: ' . BASE_URL . '/seller/manage-orders.php?msg=updated');
    exit;
}

$filterStatus = $_GET['status'] ?? '';
$sql = "SELECT o.*, u.name AS customer_name FROM orders o JOIN users u ON o.customer_id = u.id JOIN order_items oi ON oi.order_id = o.id WHERE oi.seller_id = ?";
$params = [$user['id']];
if ($filterStatus) { $sql .= " AND o.status = ?"; $params[] = $filterStatus; }
$sql .= " GROUP BY o.id ORDER BY o.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Order detail
$orderDetail = null;
$orderItems  = [];
if (isset($_GET['order_id'])) {
    $ordId = (int)$_GET['order_id'];
    $od = $db->prepare("SELECT o.*, u.name AS customer_name, u.phone AS customer_phone FROM orders o JOIN users u ON o.customer_id = u.id WHERE o.id = ?");
    $od->execute([$ordId]);
    $orderDetail = $od->fetch();
    $oi = $db->prepare("SELECT oi.*, p.name AS product_name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ? AND oi.seller_id = ?");
    $oi->execute([$ordId, $user['id']]);
    $orderItems = $oi->fetchAll();
}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="main-content">
  <div class="topbar">
    <span class="topbar-title">Manage Orders</span>
  </div>

  <?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success" data-auto-dismiss>Order status updated.</div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr <?= $orderDetail ? '380px' : '0' ?>;gap:24px;align-items:start;">

    <div>
      <!-- Filter -->
      <form method="GET" style="display:flex;gap:12px;align-items:center;margin-bottom:20px;">
        <select name="status" class="form-control" style="width:180px;">
          <option value="">All Orders</option>
          <?php foreach (['pending','processing','ready','done','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <a href="<?= BASE_URL ?>/seller/manage-orders.php" class="btn btn-reset btn-sm">Clear</a>
      </form>

      <div class="card">
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Update Status</th><th>Detail</th></tr>
            </thead>
            <tbody>
              <?php if (empty($orders)): ?>
                <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:40px">No orders found.</td></tr>
              <?php else: ?>
                <?php foreach ($orders as $ord): ?>
                  <tr>
                    <td><strong>#<?= str_pad($ord['id'], 4, '0', STR_PAD_LEFT) ?></strong></td>
                    <td><?= htmlspecialchars($ord['customer_name']) ?></td>
                    <td>Rp <?= number_format($ord['total_price'], 0, ',', '.') ?></td>
                    <td><span class="badge badge-<?= $ord['status'] ?>"><?= ucfirst($ord['status']) ?></span></td>
                    <td>
                      <?php if (!in_array($ord['status'], ['done','cancelled'])): ?>
                        <form method="POST" style="display:flex;gap:6px;align-items:center;">
                          <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                          <select name="status" class="form-control" style="width:140px;padding:6px 10px;font-size:.82rem;">
                            <?php foreach (['pending','processing','ready','done','cancelled'] as $s): ?>
                              <option value="<?= $s ?>" <?= $ord['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                          </select>
                          <button type="submit" class="btn btn-sm btn-primary">Save</button>
                        </form>
                      <?php else: ?>
                        <span class="text-muted" style="font-size:.82rem;">—</span>
                      <?php endif; ?>
                    </td>
                    <td><a href="?order_id=<?= $ord['id'] ?><?= $filterStatus ? '&status='.$filterStatus : '' ?>" class="btn btn-sm btn-outline">View</a></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Order detail panel -->
    <?php if ($orderDetail): ?>
      <div class="card" style="position:sticky;top:80px;">
        <div class="card-header">
          <h3>Order #<?= str_pad($orderDetail['id'], 4, '0', STR_PAD_LEFT) ?></h3>
          <span class="badge badge-<?= $orderDetail['status'] ?>"><?= ucfirst($orderDetail['status']) ?></span>
        </div>
        <div class="card-body" style="padding:18px;">
          <p style="font-size:.85rem;color:var(--muted);margin-bottom:14px;">
            👤 <?= htmlspecialchars($orderDetail['customer_name']) ?><br>
            📱 <?= htmlspecialchars($orderDetail['customer_phone'] ?? 'N/A') ?><br>
            🕐 <?= date('d M Y, H:i', strtotime($orderDetail['created_at'])) ?>
          </p>
          <table style="width:100%;font-size:.85rem;">
            <thead><tr style="border-bottom:1px solid var(--border);">
              <th style="text-align:left;padding:6px 0;font-weight:600;color:var(--navy);">Item</th>
              <th style="text-align:right;padding:6px 0;font-weight:600;color:var(--navy);">Subtotal</th>
            </tr></thead>
            <tbody>
              <?php foreach ($orderItems as $item): ?>
                <tr>
                  <td style="padding:8px 0;"><?= htmlspecialchars($item['product_name']) ?> × <?= $item['quantity'] ?></td>
                  <td style="text-align:right;padding:8px 0;">Rp <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot><tr style="border-top:2px solid var(--navy);">
              <td style="padding:10px 0;font-weight:700;color:var(--navy);">Total</td>
              <td style="text-align:right;font-weight:700;color:var(--gold);font-size:1rem;">Rp <?= number_format($orderDetail['total_price'], 0, ',', '.') ?></td>
            </tr></tfoot>
          </table>
        </div>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php include '../includes/footer.php'; ?>
