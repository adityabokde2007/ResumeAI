<?php
// auth/login.php
require_once '../includes/config.php';
initSession();

// Already logged in
if (!empty($_SESSION['user_id'])) {
    header('Location: ../user/dashboard.php'); exit;
}

$error = '';
$success = '';
$email = '';
$login_success = false;

if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $success = 'Your password has been successfully reset. You can now log in.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please fill in all fields.';
        } else {
            $db   = getDB();
            $stmt = $db->prepare("SELECT id, name, email, password, is_active FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$user) {
                $error = 'No account found with that email.';
            } elseif (!$user['is_active']) {
                $error = 'Your account has been suspended. Please contact support.';
            } elseif (!password_verify($password, $user['password'])) {
                $error = 'Incorrect password. Please try again.';
            } else {
                // Login success
                session_regenerate_id(true);
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_name']  = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                setcookie('has_account', '1', time() + (86400 * 365), '/');

                // Update last login
                $upd = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $upd->bind_param('i', $user['id']);
                $upd->execute();
                $upd->close();

                $login_success = true;
            }
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
  <title>Login — ResumeAI</title>
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

    <h1 class="auth-headline">
      Welcome to<br><span>ResumeAI</span>
    </h1>
    <p class="auth-sub">
      Access professional resume analysis tools and optimize your job applications with automated feedback.
    </p>

    <div class="auth-features">
      <div class="auth-feature">
        <div class="auth-feature-dot"></div>
        Comprehensive ATS scoring
      </div>
      <div class="auth-feature">
        <div class="auth-feature-dot"></div>
        Skill gap analysis
      </div>
      <div class="auth-feature">
        <div class="auth-feature-dot"></div>
        Contextual improvement suggestions
      </div>
      <div class="auth-feature">
        <div class="auth-feature-dot"></div>
        Secure tracking and reporting
      </div>
    </div>
  </div>

  <!-- Right Panel -->
  <div class="auth-right">
    <div class="auth-box">
      <h2>Welcome back</h2>

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

      <?php if ($login_success): ?>
      <script>
        document.addEventListener("DOMContentLoaded", () => {
          showToast('Login successful', 'success');
          setTimeout(() => {
            window.location.href = '../user/dashboard.php';
          }, 1500);
        });
      </script>
      <?php endif; ?>

      <form method="POST" id="loginForm" novalidate>
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

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

        <div style="display:flex;justify-content:flex-start;margin-bottom:20px;">
          <a href="forgot-password.php" style="font-size:13px;color:var(--blue);font-weight:500;">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg" id="submitBtn">
          Sign In
        </button>

        <p class="auth-after" style="text-align:center; margin-top:18px; color:var(--text-3);">Don't have an account? <a href="register.php" class="link-underline">Create one free</a></p>
      </form>
    </div>
  </div>
</div>

<script>
function togglePass(id, btn) {
  const inp = document.getElementById(id);
  const showing = inp.type === 'text';
  inp.type = showing ? 'password' : 'text';
  if (!btn) return;
  if (showing) {
    btn.innerHTML = `\n      <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>`;
  } else {
    btn.innerHTML = `\n      <svg class="icon-eye-off" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18"/><path stroke-linecap="round" stroke-linejoin="round" d="M9.88 9.88A3 3 0 0114.12 14.12"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.46 12.27A10.94 10.94 0 0112 7c4.48 0 8.27 2.94 9.54 7-.18.56-.43 1.09-.73 1.58"/></svg>`;
  }
}
document.getElementById('loginForm').addEventListener('submit', function(e) {
  let ok = true;
  const email = document.getElementById('email');
  const pass  = document.getElementById('password');
  document.getElementById('emailErr').textContent = '';
  document.getElementById('passErr').textContent  = '';
  if (!email.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
    document.getElementById('emailErr').textContent = 'Please enter a valid email.';
    email.classList.add('error'); ok = false;
  } else { email.classList.remove('error'); }
  if (pass.value.length < 6) {
    document.getElementById('passErr').textContent = 'Password must be at least 6 characters.';
    pass.classList.add('error'); ok = false;
  } else { pass.classList.remove('error'); }
  if (!ok) { e.preventDefault(); return; }
  const btn = document.getElementById('submitBtn');
  btn.innerHTML = '<div class="spinner"></div> Signing in...';
  btn.disabled = true;
});
</script>
</body>
</html>
