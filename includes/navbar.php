<?php
// includes/navbar.php
// $sidebarRole must be set before including this file: 'organizer' | 'seller' | 'customer'
if (!isset($sidebarRole)) $sidebarRole = $_SESSION['role'] ?? 'customer';

$navsByRole = [
    'organizer' => [
        ['href' => '/organizer/dashboard.php',          'icon' => '🏠', 'label' => 'Dashboard'],
        ['href' => '/organizer/create-event.php',       'icon' => '➕', 'label' => 'Create Event'],
        ['href' => '/organizer/manage-events.php',      'icon' => '📅', 'label' => 'Manage Events'],
        ['href' => '/organizer/seller-applications.php','icon' => '🏪', 'label' => 'Seller Applications'],
        ['href' => '/organizer/event-orders.php',       'icon' => '📦', 'label' => 'Event Orders'],
    ],
    'seller' => [
        ['href' => '/seller/dashboard.php',         'icon' => '🏠', 'label' => 'Dashboard'],
        ['href' => '/seller/apply-event.php',        'icon' => '🎪', 'label' => 'Apply to Event'],
        ['href' => '/seller/manage-store.php',       'icon' => '🏪', 'label' => 'My Store'],
        ['href' => '/seller/manage-products.php',    'icon' => '🍜', 'label' => 'Products'],
        ['href' => '/seller/manage-orders.php',      'icon' => '📦', 'label' => 'Orders'],
        ['href' => '/seller/chat.php',               'icon' => '💬', 'label' => 'Chat'],
    ],
    'customer' => [
        ['href' => '/customer/dashboard.php',       'icon' => '🏠', 'label' => 'Dashboard'],
        ['href' => '/customer/events.php',           'icon' => '🎉', 'label' => 'Browse Events'],
        ['href' => '/customer/stores.php',           'icon' => '🏪', 'label' => 'Stores'],
        ['href' => '/customer/cart.php',             'icon' => '🛒', 'label' => 'My Cart'],
        ['href' => '/customer/order-history.php',    'icon' => '📋', 'label' => 'Order History'],
        ['href' => '/customer/chat.php',             'icon' => '💬', 'label' => 'Chat'],
    ],
];

$roleName = [
    'organizer' => 'Event Organizer',
    'seller'    => 'Seller',
    'customer'  => 'Customer',
];

$navItems = $navsByRole[$sidebarRole] ?? [];
$currentPath = $_SERVER['REQUEST_URI'];
$user = currentUser();
$initials = strtoupper(substr($user['name'], 0, 2));
?>

<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="logo-text">CYUTFest</div>
    <div class="logo-sub">School Event Marketplace</div>
  </div>

  <div class="sidebar-role-badge"><?= $roleName[$sidebarRole] ?? $sidebarRole ?></div>

  <nav class="sidebar-nav">
    <?php foreach ($navItems as $item): ?>
      <a href="<?= BASE_URL . $item['href'] ?>"
         class="<?= (strpos($currentPath, basename($item['href'])) !== false) ? 'active' : '' ?>">
        <span class="nav-icon"><?= $item['icon'] ?></span>
        <?= $item['label'] ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="user-info">
      <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
      <div>
        <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
        <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
      </div>
    </div>
    <a href="<?= BASE_URL ?>/auth/logout.php" class="btn-logout">← Log Out</a>
  </div>
</aside>
