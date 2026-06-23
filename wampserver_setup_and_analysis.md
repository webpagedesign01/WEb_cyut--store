# CYUTFest — WampServer Setup, Flaws & Improvements

---

## Part 1: Step-by-Step WampServer Setup

### Step 1 — Install & Start WampServer

1. Download WampServer from [wampserver.com](https://wampserver.com) (64-bit recommended)
2. Install it to the default location (`C:\wamp64`)
3. Launch WampServer — the tray icon should turn **green** (all services running)

> [!IMPORTANT]
> If the icon stays **orange** or **red**, port 80 may be in use. Right-click the tray icon → Apache → Use a port other than 80 (e.g. `8080`).

---

### Step 2 — Restructure the Project for WampServer

Your code currently has a **flat file structure** (all `.php` files in one folder), but the `require_once` paths reference a **nested folder structure** like `../config/database.php` and `../includes/header.php`. You need to reorganize for it to work.

**Move/copy your project into `C:\wamp64\www\cyutfest\` with this structure:**

```
C:\wamp64\www\cyutfest\
├── config/
│   └── database.php          ← your current database.php
├── includes/
│   ├── header.php             ← your current header.php
│   ├── navbar.php             ← your current navbar.php
│   └── footer.php             ← your current footer.php
├── assets/
│   ├── css/
│   │   └── style.css          ← your current style.css
│   └── js/
│       └── main.js            ← your current main.js
├── auth/
│   ├── login.php              ← your current login.php
│   ├── register.php           ← your current register.php
│   └── logout.php             ← your current logout.php
├── organizer/
│   ├── dashboard.php          ← your current dashboard_org.php
│   ├── create-event.php       ← your current create-event.php
│   ├── manage-events.php      ← your current manage-events.php
│   ├── seller-applications.php← your current seller-applications.php
│   └── event-orders.php       ← your current event-orders.php
├── seller/
│   ├── dashboard.php          ← your current dashboard_sel.php
│   ├── apply-event.php        ← your current apply-event.php
│   ├── manage-store.php       ← your current manage-store.php
│   ├── manage-products.php    ← your current manage-products.php
│   ├── manage-orders.php      ← your current manage-orders.php
│   └── chat.php               ← your current chat.php
├── customer/
│   ├── dashboard.php          ← your current dashboard_cus.php
│   ├── events.php             ← your current events.php
│   └── stores.php             ← your current stores.php
└── index.php                  ← homepage (create new or redirect to login)
```

> [!WARNING]
> **This restructuring is critical.** Without it, every `require_once '../config/database.php'` and `include '../includes/header.php'` will fail with a **fatal error** because the relative paths won't resolve.

---

### Step 3 — Create the Database

1. Open phpMyAdmin: go to `http://localhost/phpmyadmin` in your browser
2. Click **New** (left sidebar) to create a new database
3. Name it `cyutfest_db`, set collation to `utf8mb4_general_ci`, click **Create**
4. Click the **SQL** tab and paste the following schema:

```sql
-- ============================================
-- CYUTFest Database Schema
-- ============================================

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('organizer','seller','customer') NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `created_at` DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `organizer_id` INT NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `location` VARCHAR(200) NOT NULL,
  `event_date` DATE NOT NULL,
  `event_type` VARCHAR(50) DEFAULT NULL,
  `max_sellers` INT DEFAULT NULL,
  `entry_fee` DECIMAL(12,2) DEFAULT NULL,
  `status` ENUM('pending','approved','rejected','finished') DEFAULT 'pending',
  `created_at` DATETIME NOT NULL,
  FOREIGN KEY (`organizer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `seller_applications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT NOT NULL,
  `seller_id` INT NOT NULL,
  `status` ENUM('pending','approved','rejected') DEFAULT 'pending',
  `created_at` DATETIME NOT NULL,
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`seller_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `stores` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT NOT NULL,
  `seller_id` INT NOT NULL,
  `store_name` VARCHAR(150) NOT NULL,
  `description` TEXT,
  `created_at` DATETIME NOT NULL,
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`seller_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed some default categories
INSERT INTO `categories` (`name`) VALUES
('Food'), ('Drinks'), ('Snacks'), ('Merchandise'), ('Desserts');

CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `store_id` INT NOT NULL,
  `category_id` INT DEFAULT NULL,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT,
  `price` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `stock` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT NOT NULL,
  `event_id` INT NOT NULL,
  `total_price` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `status` ENUM('pending','processing','ready','done','cancelled') DEFAULT 'pending',
  `created_at` DATETIME NOT NULL,
  FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `seller_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `price` DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`seller_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `carts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `chats` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT NOT NULL,
  `seller_id` INT NOT NULL,
  `sender_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`seller_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

5. Click **Go** to execute

---

### Step 4 — Verify Database Credentials

Your [database.php](file:///c:/Users/user/Documents/cyutfest/database.php) already uses the correct WampServer defaults:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'cyutfest_db');
define('DB_USER', 'root');
define('DB_PASS', '');          // WampServer default = no password
```

No changes needed here.

---

### Step 5 — Create a Test User (Seed Data)

In phpMyAdmin → `cyutfest_db` → SQL tab, run:

```sql
-- Password: "password123" (bcrypt hash)
INSERT INTO `users` (`name`, `email`, `password`, `role`, `phone`, `created_at`) VALUES
('Organizer Demo', 'org@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'organizer', '+62 812 0000 0001', NOW()),
('Seller Demo', 'seller@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seller', '+62 812 0000 0002', NOW()),
('Customer Demo', 'cust@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', '+62 812 0000 0003', NOW());

-- Add a test event (already approved)
INSERT INTO `events` (`organizer_id`, `title`, `description`, `location`, `event_date`, `status`, `created_at`) VALUES
(1, 'Market Day 2026', 'Bazaar kampus semester genap', 'Gedung Auditorium CYUT', '2026-07-15', 'approved', NOW());
```

> **Login credentials for all 3 users:** email as above, password: `password123`

---

### Step 6 — Open in Browser

Navigate to:
- **Login:** `http://localhost/cyutfest/auth/login.php`
- **Register:** `http://localhost/cyutfest/auth/register.php`

> [!IMPORTANT]
> Because your code uses **absolute paths** like `/assets/css/style.css` and `/auth/login.php`, those will resolve to `http://localhost/assets/css/style.css` instead of `http://localhost/cyutfest/assets/css/style.css`. See **Flaw #1** below for the fix.

---

## Part 2: Flaws Found in the Code

### 🔴 Flaw #1 — Absolute Paths Will Break on WampServer (CRITICAL)

**Every single page** uses hardcoded absolute paths like:
- `href="/assets/css/style.css"` → resolves to `localhost/assets/css/style.css` ❌
- `href="/auth/login.php"` → resolves to `localhost/auth/login.php` ❌
- `header('Location: /organizer/dashboard.php')` → wrong ❌

**Files affected:** All 23 files.

**Fix:** Define a `BASE_URL` constant in `database.php`:
```php
define('BASE_URL', '/cyutfest');
```
Then replace all absolute paths:
```php
// Before
<link rel="stylesheet" href="/assets/css/style.css">
// After
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
```

---

### 🔴 Flaw #2 — Flat File Structure Doesn't Match `require_once` Paths (CRITICAL)

All files are in the same folder, but they reference `../config/database.php`, `../includes/header.php`, etc.

| File | require_once path | Where it expects the file |
|---|---|---|
| [login.php](file:///c:/Users/user/Documents/cyutfest/login.php) L4 | `'../config/database.php'` | Parent directory `/config/` |
| [manage-products.php](file:///c:/Users/user/Documents/cyutfest/manage-products.php) L3 | `'../config/database.php'` | Parent directory `/config/` |
| All dashboard files | `'../includes/header.php'` | Parent directory `/includes/` |

**Fix:** Restructure as shown in Step 2.

---

### 🟡 Flaw #3 — No CSRF Protection

All forms submit via POST without any CSRF token. An attacker could craft a malicious page that submits forms on behalf of a logged-in user.

**Files affected:** [login.php](file:///c:/Users/user/Documents/cyutfest/login.php), [register.php](file:///c:/Users/user/Documents/cyutfest/register.php), [manage-products.php](file:///c:/Users/user/Documents/cyutfest/manage-products.php), [manage-store.php](file:///c:/Users/user/Documents/cyutfest/manage-store.php), [manage-orders.php](file:///c:/Users/user/Documents/cyutfest/manage-orders.php), [create-event.php](file:///c:/Users/user/Documents/cyutfest/create-event.php), [seller-applications.php](file:///c:/Users/user/Documents/cyutfest/seller-applications.php), [chat.php](file:///c:/Users/user/Documents/cyutfest/chat.php)

**Fix:** Generate a token per session and validate it:
```php
// In database.php — add helper
function csrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// In every form:
<input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

// In every POST handler:
if ($_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    die('Invalid CSRF token.');
}
```

---

### 🟡 Flaw #4 — Delete Actions Use POST Without JavaScript Confirm

In [manage-products.php](file:///c:/Users/user/Documents/cyutfest/manage-products.php) L183–184, the delete button has `data-confirm` but relies on [main.js](file:///c:/Users/user/Documents/cyutfest/main.js) to intercept the click. If JS fails to load (wrong path), the product will be deleted without confirmation.

**Fix:** Add `onclick="return confirm('...')"` as a fallback on the button itself.

---

### 🟡 Flaw #5 — No `event_type` Column Used in INSERT

In [create-event.php](file:///c:/Users/user/Documents/cyutfest/create-event.php) L23, the `event_type` field is collected from the form (L17) but **never inserted** into the database:
```php
// Line 23 — event_type is missing from the INSERT
$ins = $db->prepare("INSERT INTO events (organizer_id, title, description, location, event_date, status, created_at) VALUES (?,?,?,?,?,'pending',NOW())");
```

**Fix:** Add `event_type` to the INSERT:
```php
$ins = $db->prepare("INSERT INTO events (organizer_id, title, description, location, event_date, event_type, status, created_at) VALUES (?,?,?,?,?,?,'pending',NOW())");
$ins->execute([$user['id'], $title, $description, $location, $event_date, $event_type]);
```

---

### 🟡 Flaw #6 — `max_sellers` and `entry_fee` Fields Are Never Saved

In [create-event.php](file:///c:/Users/user/Documents/cyutfest/create-event.php) L102–116, the form collects `max_sellers` and `entry_fee` but the PHP handler **never reads them from `$_POST`**.

---

### 🟡 Flaw #7 — `apply-event.php` Collects Store Name/Description but Never Uses Them

In [apply-event.php](file:///c:/Users/user/Documents/cyutfest/apply-event.php) L16–18, `store_name`, `description`, and `category` are captured from the form, but the INSERT on L29 only saves `event_id`, `seller_id`, `status`, and `created_at`.

---

### 🟡 Flaw #8 — Email Input Uses `type="text"` Instead of `type="email"`

In [login.php](file:///c:/Users/user/Documents/cyutfest/login.php) L84 and [register.php](file:///c:/Users/user/Documents/cyutfest/register.php) L108:
```html
<input type="text" id="email" ...>
```
This skips browser-native email validation.

**Fix:** Change to `type="email"`.

---

### 🟠 Flaw #9 — No `index.php` Exists

There is no landing page or homepage. Navigating to `http://localhost/cyutfest/` will show a directory listing (security risk) or a 403 error.

---

### 🟠 Flaw #10 — Missing Pages Referenced in Navbar

The [navbar.php](file:///c:/Users/user/Documents/cyutfest/navbar.php) links to pages that **don't exist** in your project:
- `/customer/cart.php` — not created
- `/customer/order-history.php` — not created
- `/customer/product-detail.php` — not created (linked from [stores.php](file:///c:/Users/user/Documents/cyutfest/stores.php) L76)

---

### 🟠 Flaw #11 — `seller-applications.php` Outputs Unsanitized GET Parameter

In [seller-applications.php](file:///c:/Users/user/Documents/cyutfest/seller-applications.php) L57:
```php
<div class="alert alert-success">Application <?= htmlspecialchars($_GET['msg']) ?> successfully.</div>
```
While `htmlspecialchars` is used (good), the message content is controlled by the user via the URL query string, which could be used for phishing (e.g. `?msg=deleted%20your%20account`).

**Fix:** Use a whitelist of allowed messages instead of echoing the raw parameter.

---

## Part 3: Suggested Improvements

### 🚀 1. Add an `index.php` Landing Page
Create a simple homepage that redirects logged-in users to their dashboard, or shows a public landing/login page.

### 🚀 2. Create Missing Pages
Build `cart.php`, `order-history.php`, and `product-detail.php` for the customer role.

### 🚀 3. Add an Admin Role
Currently no one can approve events (status stays `pending` forever). Add an `admin` role with a dashboard to approve/reject events.

### 🚀 4. Use PRG (Post-Redirect-Get) Consistently
Some pages (e.g., [manage-events.php](file:///c:/Users/user/Documents/cyutfest/manage-events.php)) correctly redirect after POST. Others (e.g., [manage-products.php](file:///c:/Users/user/Documents/cyutfest/manage-products.php), [create-event.php](file:///c:/Users/user/Documents/cyutfest/create-event.php)) re-render the page, which causes duplicate submissions on refresh.

### 🚀 5. Add Image Upload for Products & Events
Products and events don't have images. Adding file upload (even basic `move_uploaded_file`) would greatly improve the UI.

### 🚀 6. Real-Time Chat
The current chat requires page refresh to see new messages. Consider adding AJAX polling or WebSocket support.

### 🚀 7. Add Pagination
Pages like manage-orders, event-orders, and product lists load **all records at once**. Add `LIMIT/OFFSET` pagination for performance.

### 🚀 8. Stock Validation on Orders
There's no check to prevent ordering more items than available stock. Add stock-check logic in the order/cart flow.

### 🚀 9. Responsive Mobile Layout
The sidebar layout uses CSS Grid but there is no mobile hamburger menu or responsive breakpoints visible. Test on smaller screens.

### 🚀 10. Environment Configuration
Move database credentials to a `.env` file (which is already in your `.gitignore`) instead of hardcoding them in `database.php`.

---

## Quick Reference — Test Flow on WampServer

| Step | Action | URL |
|------|--------|-----|
| 1 | Register as Organizer | `/cyutfest/auth/register.php` |
| 2 | Login as Organizer | `/cyutfest/auth/login.php` |
| 3 | Create Event | `/cyutfest/organizer/create-event.php` |
| 4 | ⚠️ Manually approve event in phpMyAdmin | `UPDATE events SET status='approved' WHERE id=1` |
| 5 | Register & Login as Seller | `/cyutfest/auth/register.php` |
| 6 | Apply to event | `/cyutfest/seller/apply-event.php` |
| 7 | Login as Organizer → Approve seller | `/cyutfest/organizer/seller-applications.php` |
| 8 | Login as Seller → Create Store | `/cyutfest/seller/manage-store.php` |
| 9 | Add Products | `/cyutfest/seller/manage-products.php` |
| 10 | Register & Login as Customer | Browse events & stores |
