<?php
// user/settings.php
require_once '../includes/config.php';
requireUserLogin();

$userId = $_SESSION['user_id'];
$db     = getDB();
$error  = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request token.";
    } elseif (isset($_POST['update_password'])) {
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = "All password fields are required.";
        } elseif ($newPassword !== $confirmPassword) {
            $error = "New passwords do not match.";
        } elseif (strlen($newPassword) < 8) {
            $error = "New password must be at least 8 characters long.";
        } else {
            $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $userRow = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (password_verify($oldPassword, $userRow['password'])) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $upd = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upd->bind_param('si', $hashedPassword, $userId);
                if ($upd->execute()) {
                    $success = "Password changed successfully.";
                } else {
                    $error = "An error occurred while updating your password.";
                }
                $upd->close();
            } else {
                $error = "Incorrect old password.";
            }
        }
    } elseif (isset($_POST['delete_account'])) {
        $password = $_POST['password'] ?? '';
        
        // Verify user's current password before deleting
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $userRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (password_verify($password, $userRow['password'])) {
            // Delete user account
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->close();
            
            // Log out and redirect
            session_destroy();
            header("Location: ../auth/register.php?deleted=1");
            exit;
        } else {
            $error = "Incorrect password. Account deletion failed.";
        }
    }
}

$activePage = 'settings';
define('BASE_URL', '..');
$pageTitle = 'Settings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?> — ResumeAI</title>
  <link rel="stylesheet" href="../assets/css/main.css">
  <script src="../assets/js/theme.js"></script>
</head>
<body>
<div class="app-layout">

  <?php include '../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <div class="topbar-left">
        <a href="dashboard.php">Dashboard</a>
        <span>/</span>
        <span class="topbar-page"><?= $pageTitle ?></span>
      </div>
      <div class="topbar-right">
        <span class="topbar-date"><?= date('l, F j, Y') ?></span>
      </div>
    </div>

    <div class="page-body">
      <div class="page-header">
        <div>
          <div class="page-title">Settings</div>
          <div class="page-subtitle">Manage your account settings and application preferences</div>
        </div>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-error mb-3" id="errorAlert"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="alert alert-success mb-3" id="successAlert"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <div style="max-width: 800px;">
        
        <!-- About Card -->
        <div class="card" style="margin-bottom: 24px;">
          <div class="card-header">
            <div class="card-title">About ResumeAI</div>
          </div>
          <div style="padding: 24px;">
            <div style="display: flex; flex-direction: column; gap: 16px;">
              <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--card-border); padding-bottom: 16px;">
                <span style="color: var(--text-2); font-size: 14px;">Version</span>
                <span style="color: var(--text-1); font-weight: 500;">v1.0.0 (Beta)</span>
              </div>
              <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--card-border); padding-bottom: 16px;">
                <span style="color: var(--text-2); font-size: 14px;">AI Engine</span>
                <span style="color: var(--text-1); font-weight: 500;">Groq API (Llama-3-8b)</span>
              </div>
              <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--text-2); font-size: 14px;">Quick Links</span>
                <a href="dashboard.php" style="color: var(--blue); text-decoration: none; font-weight: 500; font-size: 14px;">Back to Dashboard →</a>
              </div>
            </div>
          </div>
        </div>

        <!-- Change Password Card -->
        <div class="card" style="margin-bottom: 24px;">
          <div class="card-header">
            <div class="card-title">Change Password</div>
          </div>
          <div style="padding: 24px;">
            <form method="POST">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="update_password" value="1">
              
              <div class="form-group mb-4">
                <label class="form-label">Old Password</label>
                <div class="input-wrap">
                  <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                  <input type="password" name="old_password" id="old_password" class="form-control" required placeholder="Enter old password">
                  <button type="button" class="toggle-pass" onclick="togglePass('old_password', this)">
                    <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                  </button>
                </div>
              </div>

              <div class="form-group mb-4">
                <label class="form-label">New Password</label>
                <div class="input-wrap">
                  <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                  <input type="password" name="new_password" id="new_password" class="form-control" required placeholder="Enter new password">
                  <button type="button" class="toggle-pass" onclick="togglePass('new_password', this)">
                    <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                  </button>
                </div>
              </div>

              <div class="form-group mb-4">
                <label class="form-label">Re-write New Password</label>
                <div class="input-wrap">
                  <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                  <input type="password" name="confirm_password" id="confirm_password" class="form-control" required placeholder="Re-write new password">
                  <button type="button" class="toggle-pass" onclick="togglePass('confirm_password', this)">
                    <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                  </button>
                </div>
              </div>

              <button type="submit" class="btn btn-primary">Update Password</button>
            </form>
          </div>
        </div>
        
        <!-- Danger Zone (Delete Account) -->
        <div class="card" style="border-left: 4px solid #ef4444;">
          <div class="card-header">
            <div class="card-title" style="color: #ef4444;">Danger Zone</div>
          </div>
          <div style="padding: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
              <div>
                <h4 style="color: var(--text-1); margin: 0 0 8px 0; font-size: 16px;">Delete Account</h4>
                <p style="color: var(--text-2); margin: 0; font-size: 14px; max-width: 450px;">
                  Once you delete your account, there is no going back. All your uploaded resumes and analysis data will be permanently erased.
                </p>
              </div>
              <button type="button" class="btn btn-danger" onclick="document.getElementById('delete-modal').style.display='flex'">Delete Account</button>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- Password Confirmation Modal for Deletion -->
<div id="delete-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center;">
  <div class="card" style="width: 400px; max-width: 90%; padding: 32px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);">
    <h3 style="color: var(--text-1); margin-top: 0; margin-bottom: 12px; font-size: 20px;">Confirm Deletion</h3>
    <p style="color: var(--text-2); margin-bottom: 24px; font-size: 14px; line-height: 1.5;">
      Please enter your password to confirm you want to permanently delete your account. This action cannot be undone.
    </p>
    
    <form method="POST" style="margin: 0;">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="delete_account" value="1">
      
      <div class="form-group mb-4">
        <label class="form-label">Password</label>
        <div class="input-wrap">
          <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          <input type="password" name="password" id="delete_password" class="form-control" required placeholder="Enter your current password">
          <button type="button" class="toggle-pass" onclick="togglePass('delete_password', this)">
            <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
          </button>
        </div>
      </div>
      
      <div style="display: flex; gap: 12px; justify-content: flex-end;">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('delete-modal').style.display='none'">Cancel</button>
        <button type="submit" class="btn btn-danger">Permanently Delete</button>
      </div>
    </form>
  </div>
</div>

<script>
  function togglePass(id, btn) {
    const inp = document.getElementById(id);
    const showing = inp.type === 'text';
    inp.type = showing ? 'password' : 'text';
    if (!btn) return;
    if (showing) {
      btn.innerHTML = `<svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>`;
    } else {
      btn.innerHTML = `<svg class="icon-eye-off" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18"/><path stroke-linecap="round" stroke-linejoin="round" d="M9.88 9.88A3 3 0 0114.12 14.12"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.46 12.27A10.94 10.94 0 0112 7c4.48 0 8.27 2.94 9.54 7-.18.56-.43 1.09-.73 1.58"/></svg>`;
    }
  }

  setTimeout(() => {
    const successAlert = document.getElementById('successAlert');
    const errorAlert = document.getElementById('errorAlert');
    if (successAlert) successAlert.style.display = 'none';
    if (errorAlert) errorAlert.style.display = 'none';
  }, 5000);
</script>
</body>
</html>
