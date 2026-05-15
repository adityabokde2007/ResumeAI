<?php
require_once '../includes/config.php';
initSession();

if (!empty($_SESSION['user_id'])) {
    header('Location: ../user/dashboard.php'); exit;
}

$error = '';
$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$db = getDB();
$user_id = null;

if (empty($token)) {
    $error = 'Invalid or missing reset token. Please request a new link.';
} else {
    // Validate token exists and is not expired
    $stmt = $db->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expiry > NOW() LIMIT 1");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $user_id = $row['id'];
    } else {
        $error = 'This password reset link is invalid or has expired. Please request a new one.';
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_id) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            // Update password and clear token
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $upd = $db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
            $upd->bind_param('si', $hash, $user_id);
            if ($upd->execute()) {
                header('Location: login.php?reset=success');
                exit;
            } else {
                $error = 'Something went wrong. Please try again.';
            }
            $upd->close();
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
  <title>Reset Password — ResumeAI</title>
  <link rel="stylesheet" href="../assets/css/main.css">
  <script src="../assets/js/theme.js"></script>
</head>
<body>

<div class="auth-page">
  <div class="auth-left">
    <div class="auth-logo">
      <img src="../assets/images/logo.png" alt="ResumeAI" style="height: 160px; min-width: 150px; object-fit: contain; margin: -50px 0 -40px -20px; display: block;">
    </div>
    <h1 class="auth-headline">Create New<br><span>Password</span></h1>
    <p class="auth-sub">Choose a strong, unique password that you haven't used before.</p>
  </div>

  <div class="auth-right">
    <div class="auth-box">
      <h2>New Password</h2>

      <?php if ($error): ?>
      <div class="alert alert-error">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <?php if ($user_id && !$error): ?>
      <form method="POST" id="resetForm" novalidate>
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <div class="form-group">
          <label for="password">New Password</label>
          <div class="input-wrap">
            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            <input type="password" class="form-control" id="password" name="password" placeholder="Enter new password" required>
            <button type="button" class="toggle-pass" onclick="togglePass('password', this)">
              <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
          </div>
          <div class="form-hint" id="passErr"></div>
        </div>

        <div class="form-group">
          <label for="confirm_password">Confirm New Password</label>
          <div class="input-wrap">
            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Re-enter new password" required>
            <button type="button" class="toggle-pass" onclick="togglePass('confirm_password', this)">
              <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
          </div>
          <div class="form-hint" id="confErr"></div>
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg" id="submitBtn">
          Save New Password
        </button>
      </form>
      <?php else: ?>
        <p class="auth-after" style="text-align:center; margin-top:18px;">
          <a href="forgot-password.php" class="btn btn-outline btn-full">Request New Link</a>
        </p>
      <?php endif; ?>
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

<?php if ($user_id && !$error): ?>
document.getElementById('resetForm').addEventListener('submit', function(e) {
  let ok = true;
  document.getElementById('passErr').textContent = '';
  document.getElementById('confErr').textContent = '';
  const pass = document.getElementById('password').value;
  const conf = document.getElementById('confirm_password').value;
  
  if (pass.length < 8) { document.getElementById('passErr').textContent = 'Min. 8 characters required.'; ok = false; }
  if (pass !== conf) { document.getElementById('confErr').textContent = 'Passwords do not match.'; ok = false; }
  
  if (!ok) { e.preventDefault(); return; }
  const btn = document.getElementById('submitBtn');
  btn.innerHTML = 'Updating...';
  btn.disabled = true;
});
<?php endif; ?>
</script>
</body>
</html>
