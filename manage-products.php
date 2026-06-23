<?php
// seller/manage-products.php
require_once '../config/database.php';
requireRole('seller');
$pageTitle   = 'Manage Products';
$sidebarRole = 'seller';
$user        = currentUser();

$db = getDB();

// Get seller's store
$store = $db->prepare("SELECT * FROM stores WHERE seller_id = ? ORDER BY created_at DESC LIMIT 1");
$store->execute([$user['id']]);
$store = $store->fetch();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $store) {
    $action    = $_POST['action'] ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);

    if ($action === 'delete' && $productId) {
        $del = $db->prepare("DELETE FROM products WHERE id = ? AND store_id = ?");
        $del->execute([$productId, $store['id']]);
        $success = 'Product deleted.';
    } else {
        $name        = trim($_POST['name']        ?? '');
        $description = trim($_POST['description'] ?? '');
        $price       = (float)($_POST['price']    ?? 0);
        $stock       = (int)($_POST['stock']      ?? 0);
        $categoryId  = (int)($_POST['category_id'] ?? 0);

        if (!$name || $price <= 0) {
            $error = 'Product name and price are required.';
        } elseif ($productId) {
            $upd = $db->prepare("UPDATE products SET name=?, description=?, price=?, stock=?, category_id=? WHERE id=? AND store_id=?");
            $upd->execute([$name, $description, $price, $stock, $categoryId, $productId, $store['id']]);
            $success = 'Product updated.';
        } else {
            $ins = $db->prepare("INSERT INTO products (store_id, category_id, name, description, price, stock, created_at) VALUES (?,?,?,?,?,?,NOW())");
            $ins->execute([$store['id'], $categoryId, $name, $description, $price, $stock]);
            $success = 'Product added!';
        }
    }
}

$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$products = [];
if ($store) {
    $stmt = $db->prepare("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.store_id = ? ORDER BY p.created_at DESC");
    $stmt->execute([$store['id']]);
    $products = $stmt->fetchAll();
}

// Edit mode
$editProduct = null;
if (isset($_GET['edit'])) {
    $ep = $db->prepare("SELECT * FROM products WHERE id = ? AND store_id = ?");
    $ep->execute([(int)$_GET['edit'], $store['id'] ?? 0]);
    $editProduct = $ep->fetch();
}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="main-content">
  <div class="topbar">
    <span class="topbar-title">Manage Products</span>
  </div>

  <div class="page-header">
    <h1>Products</h1>
    <p><?= $store ? htmlspecialchars($store['store_name']) : 'Set up your store first.' ?></p>
  </div>

  <?php if (!$store): ?>
    <div class="alert alert-warning">Please <a href="/seller/manage-store.php" style="font-weight:700;">set up your store first →</a></div>
  <?php else: ?>

    <?php if ($error): ?>
      <div class="alert alert-danger" data-auto-dismiss><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success" data-auto-dismiss><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:360px 1fr;gap:28px;align-items:start;">

      <!-- Add / Edit form -->
      <div class="card">
        <div class="card-header"><h3><?= $editProduct ? 'Edit Product' : 'Add Product' ?></h3></div>
        <div class="card-body">
          <form method="POST" action="">
            <?php if ($editProduct): ?>
              <input type="hidden" name="product_id" value="<?= $editProduct['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
              <label class="form-label" for="name">Product Name <span class="required">*</span></label>
              <input type="text" id="name" name="name" class="form-control"
                     placeholder="e.g. Bakso Urat"
                     value="<?= htmlspecialchars($editProduct['name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
              <label class="form-label" for="description">Description</label>
              <textarea id="description" name="description" class="form-control" rows="3"
                        placeholder="Short product description..."><?= htmlspecialchars($editProduct['description'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
              <label class="form-label" for="category_id">Category</label>
              <select id="category_id" name="category_id" class="form-control">
                <option value="">— Select Category —</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat['id'] ?>" <?= (($editProduct['category_id'] ?? 0) == $cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label" for="price">Price (Rp) <span class="required">*</span></label>
                <input type="number" id="price" name="price" class="form-control"
                       placeholder="e.g. 15000" min="0" step="500"
                       value="<?= htmlspecialchars($editProduct['price'] ?? '') ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="stock">Stock <span class="required">*</span></label>
                <input type="number" id="stock" name="stock" class="form-control"
                       placeholder="e.g. 50" min="0"
                       value="<?= htmlspecialchars($editProduct['stock'] ?? '') ?>" required>
              </div>
            </div>

            <div style="display:flex;gap:10px;">
              <button type="submit" class="btn btn-gold"><?= $editProduct ? '💾 Update' : '➕ Add Product' ?></button>
              <button type="reset" class="btn btn-reset">Reset</button>
              <?php if ($editProduct): ?>
                <a href="/seller/manage-products.php" class="btn btn-outline btn-sm">Cancel</a>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>

      <!-- Product list -->
      <div class="card">
        <div class="card-header">
          <h3>My Products (<?= count($products) ?>)</h3>
        </div>
        <?php if (empty($products)): ?>
          <div class="empty-state">
            <div class="empty-icon">🍜</div>
            <h3>No products yet</h3>
            <p>Add your first product using the form.</p>
          </div>
        <?php else: ?>
          <div class="table-wrap">
            <table>
              <thead>
                <tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Actions</th></tr>
              </thead>
              <tbody>
                <?php foreach ($products as $prod): ?>
                  <tr>
                    <td><strong><?= htmlspecialchars($prod['name']) ?></strong><br><span style="font-size:.78rem;color:var(--muted)"><?= htmlspecialchars(substr($prod['description'],0,50)) ?>...</span></td>
                    <td><?= htmlspecialchars($prod['category_name'] ?? '—') ?></td>
                    <td>Rp <?= number_format($prod['price'], 0, ',', '.') ?></td>
                    <td>
                      <span style="color:<?= $prod['stock'] < 5 ? 'var(--danger)' : 'inherit' ?>">
                        <?= $prod['stock'] ?>
                      </span>
                    </td>
                    <td>
                      <a href="?edit=<?= $prod['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                      <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="product_id" value="<?= $prod['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger"
                                data-confirm="Delete '<?= htmlspecialchars($prod['name']) ?>'?">Delete</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

    </div>
  <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
