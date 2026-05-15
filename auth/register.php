<?php
// auth/register.php
require_once '../includes/config.php';
initSession();

if (!empty($_SESSION['user_id'])) {
    header('Location: ../user/dashboard.php'); exit;
}

$error = $success = '';
$name = $email = '';
$register_success = false;

if (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
    $success = 'Your account has been successfully deleted.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (empty($name) || empty($email) || empty($password)) {
            $error = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $db   = getDB();
            $chk  = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $chk->bind_param('s', $email);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) {
                $error = 'An account with this email already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $ins  = $db->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                $ins->bind_param('sss', $name, $email, $hash);
                if ($ins->execute()) {
                    $user_id = $ins->insert_id;
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    setcookie('has_account', '1', time() + (86400 * 365), '/');
                    $register_success = true;
                } else {
                    $error = 'Something went wrong. Please try again.';
                }
                $ins->close();
            }
            $chk->close();
        }
    }
}
$csrf = csrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account — ResumeAI</title>
  <link rel="stylesheet" href="../assets/css/main.css">
  <script src="../assets/js/theme.js"></script>
  <script src="../assets/js/main.js"></script>
</head>
<body>

<div class="auth-page">
  <!-- Left Panel -->
  <div class="auth-left">
    <div class="auth-logo">
      <img src="../assets/images/logo.png" alt="ResumeAI" style="height: 160px; min-width: 150px; object-fit: contain; margin: -50px 0 -40px -20px; display: block;">
    </div>
    <h1 class="auth-headline">Create your<br><span>Account</span></h1>
    <p class="auth-sub">Join ResumeAI to access enterprise-grade resume optimization and analysis tools.</p>
    <div class="auth-features">
      <div class="auth-feature"><div class="auth-feature-dot"></div>Standardized ATS evaluation</div>
      <div class="auth-feature"><div class="auth-feature-dot"></div>Immediate analytical feedback</div>
      <div class="auth-feature"><div class="auth-feature-dot"></div>Role-specific keyword targeting</div>
      <div class="auth-feature"><div class="auth-feature-dot"></div>Centralized document management</div>
    </div>
  </div>

  <!-- Right Panel -->
  <div class="auth-right">
    <div class="auth-box">
      <h2>Create your account</h2>

      <?php if ($error): ?>
      <script>
        document.addEventListener("DOMContentLoaded", () => {
          showToast(<?= json_encode($error) ?>, 'error');
        });
      </script>
      <?php endif; ?>

      <?php if ($success): ?>
      <script>
        document.addEventListener("DOMContentLoaded", () => {
          showToast(<?= json_encode($success) ?>, 'success');
        });
      </script>
      <?php endif; ?>

      <?php if ($register_success): ?>
      <script>
        document.addEventListener("DOMContentLoaded", () => {
          showToast('Registration successful', 'success');
          setTimeout(() => {
            window.location.href = '../user/dashboard.php';
          }, 1500);
        });
      </script>
      <?php endif; ?>

      <form method="POST" id="regForm" novalidate>
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

        <div class="form-group">
          <label for="name">Full Name</label>
          <div class="input-wrap">
            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <input type="text" class="form-control" id="name" name="name"
              placeholder="Enter your name" value="<?= htmlspecialchars($name) ?>" required>
          </div>
          <div class="form-hint" id="nameErr"></div>
        </div>

        <div class="form-group">
          <label for="email">Email Address</label>
          <div class="input-wrap">
            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
            </svg>
            <input type="email" class="form-control" id="email" name="email"
              placeholder="Enter your email" value="<?= htmlspecialchars($email) ?>" required>
          </div>
          <div class="form-hint" id="emailErr"></div>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-wrap">
            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            <input type="password" class="form-control" id="password" name="password"
              placeholder="Enter your password" required>
            <button type="button" class="toggle-pass" onclick="togglePass('password', this)">
              <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
          </div>
          <div class="form-hint" id="passErr"></div>
        </div>

        <div class="form-group">
          <label for="confirm_password">Confirm Password</label>
          <div class="input-wrap">
            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
              placeholder="Re-enter your password" required>
            <button type="button" class="toggle-pass" onclick="togglePass('confirm_password', this)">
              <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
          </div>
          <div class="form-hint" id="confErr"></div>
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg" id="submitBtn">
          Create Account
        </button>

        <p class="auth-after" style="text-align:center; margin-top:18px; color:var(--text-3);">Already have an account? <a href="login.php" class="link-underline">Sign in</a></p>
      </form>
    </div>
  </div>
</div>

<script>
function togglePass(id, btn) {
  const inp = document.getElementById(id);
  const showing = inp.type === 'text';
  inp.type = showing ? 'password' : 'text';
  // swap icon inside the button between eye and eye-off
  if (!btn) return;
  if (showing) {
    btn.innerHTML = `\n      <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>`;
  } else {
    btn.innerHTML = `\n      <svg class="icon-eye-off" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18"/><path stroke-linecap="round" stroke-linejoin="round" d="M9.88 9.88A3 3 0 0114.12 14.12"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.46 12.27A10.94 10.94 0 0112 7c4.48 0 8.27 2.94 9.54 7-.18.56-.43 1.09-.73 1.58"/></svg>`;
  }
}
document.getElementById('regForm').addEventListener('submit', function(e) {
  let ok = true;
  ['nameErr','emailErr','passErr','confErr'].forEach(id => document.getElementById(id).textContent = '');
  const name  = document.getElementById('name').value.trim();
  const email = document.getElementById('email').value.trim();
  const pass  = document.getElementById('password').value;
  const conf  = document.getElementById('confirm_password').value;
  if (name.length < 2) { document.getElementById('nameErr').textContent = 'Name is too short.'; ok = false; }
  if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) { document.getElementById('emailErr').textContent = 'Enter a valid email.'; ok = false; }
  if (pass.length < 8) { document.getElementById('passErr').textContent = 'Min. 8 characters required.'; ok = false; }
  if (pass !== conf) { document.getElementById('confErr').textContent = 'Passwords do not match.'; ok = false; }
  if (!ok) { e.preventDefault(); return; }
  const btn = document.getElementById('submitBtn');
  btn.innerHTML = '<div class="spinner"></div> Creating account...';
  btn.disabled = true;
});
</script>
</body>
</html>
