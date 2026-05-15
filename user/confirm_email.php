<?php
// user/confirm_email.php
require_once '../includes/config.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (empty($token)) {
    $error = "Invalid or missing token.";
} else {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, pending_email, profile_changes_count, profile_change_window FROM users WHERE email_change_token = ?");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $newEmail = $user['pending_email'];
        $userId = $user['id'];
        
        $changesCount = (int)$user['profile_changes_count'];
        $windowStart = $user['profile_change_window'];
        if (!empty($windowStart) && strtotime($windowStart) <= strtotime('-7 days')) {
            $changesCount = 0;
            $windowStart = NULL;
        }
        $newCount = $changesCount + 1;
        $newWindow = empty($windowStart) ? date('Y-m-d H:i:s') : $windowStart;
        
        // Update the email
        $upd = $db->prepare("UPDATE users SET email = ?, pending_email = NULL, email_change_token = NULL, profile_changes_count = ?, profile_change_window = ? WHERE id = ?");
        $upd->bind_param('sisi', $newEmail, $newCount, $newWindow, $userId);
        if ($upd->execute()) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
                $_SESSION['user_email'] = $newEmail;
            }
            $success = "Your email address has been successfully updated to <strong>" . htmlspecialchars($newEmail) . "</strong>.";
        } else {
            $error = "An error occurred while updating your email.";
        }
        $upd->close();
    } else {
        $error = "The confirmation link is invalid or has expired.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Email Confirmation — ResumeAI</title>
  <link rel="stylesheet" href="../assets/css/main.css">
  <script src="../assets/js/theme.js"></script>
</head>
<body style="display:flex; align-items:center; justify-content:center; min-height:100vh; background:var(--content-bg);">
  <div class="card" style="width:100%; max-width:480px; padding:32px; text-align:center;">
    <?php if ($success): ?>
      <div style="color:var(--green); margin-bottom:16px;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:64px;height:64px;margin:0 auto;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <h2 style="margin-bottom:16px; color:var(--text-1);">Success!</h2>
      <p style="color:var(--text-2); margin-bottom:24px;"><?= $success ?></p>
      <a href="profile.php" class="btn btn-primary btn-full">Return to Profile</a>
    <?php else: ?>
      <div style="color:var(--red); margin-bottom:16px;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:64px;height:64px;margin:0 auto;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <h2 style="margin-bottom:16px; color:var(--text-1);">Verification Failed</h2>
      <p style="color:var(--text-2); margin-bottom:24px;"><?= htmlspecialchars($error) ?></p>
      <a href="profile.php" class="btn btn-outline btn-full">Return to Profile</a>
    <?php endif; ?>
  </div>
</body>
</html>
