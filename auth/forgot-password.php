<?php
require_once '../includes/config.php';
require_once '../includes/mailer.php';
initSession();

if (!empty($_SESSION['user_id'])) {
    header('Location: ../user/dashboard.php'); exit;
}

$error = '';
$success = '';
$email = '';
$reset_link = ''; // For local development testing

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $db = getDB();
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                // Securely generate token
                $token = bin2hex(random_bytes(32));
                // Update DB with token and expiry (1 hour)
                $upd = $db->prepare("UPDATE users SET reset_token=?, reset_token_expiry=DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE email=?");
                $upd->bind_param('ss', $token, $email);
                $upd->execute();
                $upd->close();

                $reset_link = APP_URL . '/auth/reset-password.php?token=' . $token;
                
                $subject = "Password Reset Request - ResumeAI";
                $bodyHtml = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 10px;'>
                        <h2 style='color: #0f172a;'>Reset Your Password</h2>
                        <p style='color: #475569; line-height: 1.6;'>You recently requested to reset your password for your ResumeAI account. Click the button below to reset it. <strong>This link is only valid for 1 hour.</strong></p>
                        <div style='text-align: center; margin: 30px 0;'>
                            <a href='{$reset_link}' style='background-color: #0ea5e9; color: white; padding: 12px 24px; text-decoration: none; border-radius: 50px; font-weight: bold; display: inline-block;'>Reset Password</a>
                        </div>
                        <p style='color: #475569; font-size: 14px;'>If you're having trouble clicking the password reset button, copy and paste the URL below into your web browser:</p>
                        <p style='color: #0ea5e9; font-size: 14px; word-break: break-all;'><a href='{$reset_link}'>{$reset_link}</a></p>
                        <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 30px 0;'>
                        <p style='color: #64748b; font-size: 12px;'>If you did not request a password reset, please ignore this email or contact support if you have questions.</p>
                    </div>
                ";
                
                if (sendEmail($email, '', $subject, $bodyHtml)) {
                    $success = 'We have sent you an email with password reset instructions.';
                    $reset_link = ''; // Hide link from UI since we sent it via email
                } else {
                    $error = 'Failed to send the password reset email. Please ensure your SMTP configuration is correct in includes/config.php.';
                }
            } else {
                $success = 'If that email is registered, you will receive password reset instructions.';
            }
            $stmt->close();
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
  <title>Forgot Password — ResumeAI</title>
  <link rel="stylesheet" href="../assets/css/main.css">
  <script src="../assets/js/theme.js"></script>
</head>
<body>

<div class="auth-page">
  <div class="auth-left">
    <div class="auth-logo">
      <img src="../assets/images/logo.png" alt="ResumeAI" style="height: 160px; min-width: 150px; object-fit: contain; margin: -50px 0 -40px -20px; display: block;">
    </div>
    <h1 class="auth-headline">Reset your<br><span>Password</span></h1>
    <p class="auth-sub">Enter your email address and we'll securely verify your identity so you can regain access.</p>
  </div>

  <div class="auth-right">
    <div class="auth-box">
      <h2>Forgot Password</h2>

      <?php if ($error): ?>
      <div class="alert alert-error">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>
      
      <?php if ($success): ?>
      <div class="alert alert-success" style="display: flex; flex-direction: column; align-items: center; text-align: center; gap: 12px; padding: 24px;">
        <div style="width: 48px; height: 48px; background: rgba(22, 163, 74, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #16a34a;">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width: 24px; height: 24px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <p style="font-weight: 600; color: var(--text-1); margin: 0;"><?= htmlspecialchars($success) ?></p>
        <p style="font-size: 13px; color: var(--text-3); margin: 0;">Please check your inbox (and spam folder).</p>
        
        <?php if ($reset_link): ?>
          <a href="<?= $reset_link ?>" class="btn btn-primary" style="margin-top: 10px; padding: 10px 24px; border-radius: 50px;">Proceed to Reset Password</a>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <?php if (!$success): ?>
      <form method="POST" id="forgotForm" novalidate>
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

        <div class="form-group">
          <label for="email">Email Address</label>
          <div class="input-wrap">
            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
            </svg>
            <input type="email" class="form-control" id="email" name="email"
              placeholder="Enter your registered email" value="<?= htmlspecialchars($email) ?>" required>
          </div>
          <div class="form-hint" id="emailErr"></div>
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg" id="submitBtn">
          Send Reset Link
        </button>

        <p class="auth-after" style="text-align:center; margin-top:18px; color:var(--text-3);">
          Remember your password? <a href="login.php" class="link-underline">Sign in here</a>
        </p>
      </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
document.getElementById('forgotForm').addEventListener('submit', function(e) {
  let ok = true;
  const email = document.getElementById('email');
  document.getElementById('emailErr').textContent = '';
  
  if (!email.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
    document.getElementById('emailErr').textContent = 'Please enter a valid email address.';
    email.classList.add('error'); ok = false;
  } else { email.classList.remove('error'); }
  
  if (!ok) { e.preventDefault(); return; }
  const btn = document.getElementById('submitBtn');
  btn.innerHTML = 'Sending...';
  btn.disabled = true;
});
</script>
</body>
</html>
