<?php
// auth/login.php
session_start();
require_once '../config/database.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: /' . ($_SESSION['role'] ?? 'customer') . '/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please fill in all fields.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];
            header('Location: /' . $user['role'] . '/dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Log In — CYUTFest</title>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="auth-page">

  <!-- Left panel -->
  <div class="auth-left">
    <div class="auth-brand">
      <div class="auth-brand-logo">CYUTFest</div>
      <div class="auth-brand-tagline">School Event Marketplace</div>
    </div>

    <div class="auth-features">
      <div class="auth-feature">
        <div class="auth-feature-icon">🎪</div>
        <div class="auth-feature-text"><strong>Organizers</strong> — Create and manage school events with ease</div>
      </div>
      <div class="auth-feature">
        <div class="auth-feature-icon">🏪</div>
        <div class="auth-feature-text"><strong>Sellers</strong> — Set up your stand and start taking orders</div>
      </div>
      <div class="auth-feature">
        <div class="auth-feature-icon">🛒</div>
        <div class="auth-feature-text"><strong>Customers</strong> — Browse, order, and enjoy your event</div>
      </div>
    </div>
  </div>

  <!-- Right panel -->
  <div class="auth-right">
    <div class="auth-form-box">
      <h2>Welcome back</h2>
      <p class="auth-subtitle">Sign in to your CYUTFest account</p>

      <?php if ($error): ?>
        <div class="alert alert-danger" data-auto-dismiss><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="form-group">
          <label class="form-label" for="email">Email Address <span class="required">*</span></label>
          <input type="text" id="email" name="email" class="form-control"
                 placeholder="you@school.edu"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Password <span class="required">*</span></label>
          <input type="password" id="password" name="password" class="form-control"
                 placeholder="Enter your password" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block" style="margin-bottom:12px;">
          Sign In
        </button>
        <button type="reset" class="btn btn-reset btn-block">Clear</button>
      </form>

      <div class="auth-divider"><span>don't have an account?</span></div>
      <a href="/auth/register.php" class="btn btn-outline btn-block">Create Account</a>

      <p style="text-align:center; margin-top:24px; font-size:0.78rem; color:var(--muted);">
        <a href="/index.php" style="color:var(--gold);">← Back to home</a>
      </p>
    </div>
  </div>

</div>
<script src="/assets/js/main.js"></script>
</body>
</html>
