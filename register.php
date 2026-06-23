<?php
// auth/register.php
session_start();
require_once '../config/database.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: /' . $_SESSION['role'] . '/dashboard.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $role     = $_POST['role']     ?? '';
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';

    $allowedRoles = ['organizer', 'seller', 'customer'];

    if (!$name || !$email || !$role || !$password) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!in_array($role, $allowedRoles)) {
        $error = 'Please select a valid role.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $db   = getDB();
        $chk  = $db->prepare("SELECT id FROM users WHERE email = ?");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins  = $db->prepare("INSERT INTO users (name, email, password, role, phone, created_at) VALUES (?,?,?,?,?,NOW())");
            $ins->execute([$name, $email, $hash, $role, $phone]);
            $success = 'Account created! You can now sign in.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — CYUTFest</title>
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
        <div class="auth-feature-icon">⚡</div>
        <div class="auth-feature-text"><strong>Free to join</strong> — No hidden fees, just great events</div>
      </div>
      <div class="auth-feature">
        <div class="auth-feature-icon">🔒</div>
        <div class="auth-feature-text"><strong>Secure</strong> — Your data is safe with us</div>
      </div>
      <div class="auth-feature">
        <div class="auth-feature-icon">📱</div>
        <div class="auth-feature-text"><strong>Easy ordering</strong> — Browse, cart, checkout in seconds</div>
      </div>
    </div>
  </div>

  <!-- Right panel -->
  <div class="auth-right">
    <div class="auth-form-box">
      <h2>Create Account</h2>
      <p class="auth-subtitle">Join CYUTFest and be part of your school events</p>

      <?php if ($error): ?>
        <div class="alert alert-danger" data-auto-dismiss><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?>
          <a href="/auth/login.php" style="font-weight:700;color:var(--navy);">Sign In →</a>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="form-group">
          <label class="form-label" for="name">Full Name <span class="required">*</span></label>
          <input type="text" id="name" name="name" class="form-control"
                 placeholder="Your full name"
                 value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="email">Email Address <span class="required">*</span></label>
          <input type="text" id="email" name="email" class="form-control"
                 placeholder="you@school.edu"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="phone">Phone Number</label>
          <input type="text" id="phone" name="phone" class="form-control"
                 placeholder="+62 8xx xxxx xxxx"
                 value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
        </div>

        <!-- Role radio group -->
        <div class="form-group">
          <label class="form-label">I am joining as <span class="required">*</span></label>
          <div class="radio-group">
            <?php foreach (['organizer' => '🎪 Event Organizer', 'seller' => '🏪 Seller', 'customer' => '🛒 Customer'] as $val => $label): ?>
              <label class="radio-option">
                <input type="radio" name="role" value="<?= $val ?>"
                  <?= (($_POST['role'] ?? '') === $val) ? 'checked' : '' ?> required>
                <?= $label ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="password">Password <span class="required">*</span></label>
            <input type="password" id="password" name="password" class="form-control"
                   placeholder="Min. 6 characters" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="confirm">Confirm Password <span class="required">*</span></label>
            <input type="password" id="confirm" name="confirm" class="form-control"
                   placeholder="Re-enter password" required>
          </div>
        </div>

        <button type="submit" class="btn btn-gold btn-block" style="margin-bottom:10px;">
          Create Account
        </button>
        <button type="reset" class="btn btn-reset btn-block">Clear Form</button>
      </form>

      <div class="auth-divider"><span>already have an account?</span></div>
      <a href="/auth/login.php" class="btn btn-outline btn-block">Sign In</a>
    </div>
  </div>

</div>
<script src="/assets/js/main.js"></script>
</body>
</html>
