<?php
// user/profile.php
require_once '../includes/config.php';
requireUserLogin();

$userId = $_SESSION['user_id'];
$db     = getDB();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } else {
        $stmt_curr = $db->prepare("SELECT name, email, profile_changes_count, profile_change_window FROM users WHERE id = ?");
        $stmt_curr->bind_param('i', $userId);
        $stmt_curr->execute();
        $currData = $stmt_curr->get_result()->fetch_assoc();
        $currentName = $currData['name'];
        $currentEmail = $currData['email'];
        $changesCount = (int)$currData['profile_changes_count'];
        $windowStart = $currData['profile_change_window'];
        $stmt_curr->close();

        if (!empty($windowStart)) {
            $windowStartTime = strtotime($windowStart);
            if ($windowStartTime <= strtotime('-7 days')) {
                $changesCount = 0;
                $windowStart = NULL;
            }
        }

        if (isset($_POST['update_name'])) {
            $newName = trim($_POST['full_name'] ?? '');
            if (empty($newName)) {
                $error = "Name cannot be empty.";
            } elseif ($newName === $currentName) {
                $success = "Name updated successfully.";
            } elseif ($changesCount >= 2) {
                $error = "You have reached the limit of 2 profile changes per week.";
            } else {
                $newCount = $changesCount + 1;
                $newWindow = empty($windowStart) ? date('Y-m-d H:i:s') : $windowStart;
                $upd = $db->prepare("UPDATE users SET name = ?, profile_changes_count = ?, profile_change_window = ? WHERE id = ?");
                $upd->bind_param('sisi', $newName, $newCount, $newWindow, $userId);
                $upd->execute();
                $_SESSION['user_name'] = $newName;
                $success = "Name updated successfully.";
            }
        } elseif (isset($_POST['update_email'])) {
            $newEmail = trim($_POST['email'] ?? '');
            
            if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                $error = "Invalid email format.";
            } elseif ($newEmail === $currentEmail) {
                $error = "This is already your current email address.";
                $db->query("UPDATE users SET pending_email = NULL, email_change_token = NULL WHERE id = $userId");
            } elseif ($changesCount >= 2) {
                $error = "You have reached the limit of 2 profile changes per week.";
            } else {
            $check = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check->bind_param('si', $newEmail, $userId);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $error = "Email is already in use by another account.";
            } else {
                $token = bin2hex(random_bytes(32));
                $upd = $db->prepare("UPDATE users SET pending_email = ?, email_change_token = ? WHERE id = ?");
                $upd->bind_param('ssi', $newEmail, $token, $userId);
                if ($upd->execute()) {
                    require_once '../includes/mailer.php';
                    $stmt = $db->prepare("SELECT email, name FROM users WHERE id = ?");
                    $stmt->bind_param('i', $userId);
                    $stmt->execute();
                    $u = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    
                    $link = "http://localhost/resumeai_project/resumeai/user/confirm_email.php?token=" . $token;
                    $html = "<h3>Email Change Request</h3><p>Hello {$u['name']},</p><p>A request was made to change your ResumeAI account email to <strong>{$newEmail}</strong>.</p><p>If this was you, please click the link below to confirm:</p><p><a href='{$link}'>Confirm Email Change</a></p><p>If this wasn't you, ignore this email and your account remains secure.</p>";
                    
                    if (sendEmail($u['email'], $u['name'], "Confirm your new email address", $html)) {
                        $success = "A confirmation link has been sent to your CURRENT email address.";
                    } else {
                        $error = "Failed to send confirmation email. Please try again later.";
                    }
                }
            }
        }
    }
}
}

// Fetch user data
$stmt = $db->prepare("SELECT name, email, pending_email, created_at FROM users WHERE id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Sync session if email was changed via confirm_email link
if (isset($_SESSION['user_email']) && $_SESSION['user_email'] !== $user['email']) {
    $_SESSION['user_email'] = $user['email'];
}

// Fetch stats
$statsStmt = $db->prepare("
    SELECT 
        (SELECT COUNT(DISTINCT resume_id) FROM analyses WHERE user_id = ?) as total_resumes,
        ROUND(AVG(ats_score), 1) as avg_score,
        MAX(ats_score) as best_score
    FROM analyses
    WHERE user_id = ?
");
$statsStmt->bind_param('ii', $userId, $userId);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();
$statsStmt->close();

$initial     = strtoupper(substr($user['name'], 0, 1));
$memberSince = date('M Y', strtotime($user['created_at']));

$activePage = 'profile';
define('BASE_URL', '..');
$pageTitle = 'My Profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?> — ResumeAI</title>
  <link rel="stylesheet" href="../assets/css/main.css">
  <script src="../assets/js/theme.js"></script>
  <style>
    .profile-grid {
      display: grid;
      grid-template-columns: 280px 1fr;
      gap: 24px;
      align-items: start;
    }
    @media (max-width: 900px) {
      .profile-grid { grid-template-columns: 1fr; }
    }
    
    .profile-avatar-card {
      text-align: center;
      padding: 32px 24px;
    }
    .profile-avatar {
      width: 96px;
      height: 96px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--blue), var(--blue-dark));
      color: white;
      font-size: 36px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 16px;
      box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
    }
    .profile-name {
      font-size: 20px;
      font-weight: 600;
      color: var(--text-1);
      margin-bottom: 4px;
    }
    .profile-email {
      color: var(--text-3);
      font-size: 14px;
      margin-bottom: 16px;
    }
    .member-badge {
      display: inline-block;
      padding: 4px 12px;
      background: var(--bg-body);
      color: var(--text-2);
      border-radius: 99px;
      font-size: 12px;
      font-weight: 500;
      border: 1px solid var(--card-border);
      margin-bottom: 24px;
    }
    
    .profile-stats {
      border-top: 1px solid var(--card-border);
      padding-top: 20px;
      display: grid;
      gap: 16px;
      text-align: left;
    }
    .pstat {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .pstat-label {
      font-size: 13px;
      color: var(--text-2);
    }
    .pstat-val {
      font-size: 15px;
      font-weight: 600;
      color: var(--text-1);
    }
    
    .info-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 20px;
    }
    @media (max-width: 600px) {
      .info-row { grid-template-columns: 1fr; }
    }
    .info-group {
      background: var(--bg-body);
      padding: 16px 20px;
      border-radius: 12px;
      border: 1px solid var(--card-border);
    }
    .info-label {
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--text-3);
      margin-bottom: 6px;
      font-weight: 600;
    }
    .info-val {
      font-size: 16px;
      color: var(--text-1);
      font-weight: 500;
    }
    .info-val.hidden-pwd {
      letter-spacing: 2px;
      font-family: monospace;
      font-size: 18px;
    }
  </style>
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
          <div class="page-title">My Profile</div>
          <div class="page-subtitle">View your account information and preferences</div>
        </div>
      </div>

      <div class="profile-grid">
        
        <!-- LEFT: Avatar Card -->
        <div class="card profile-avatar-card">
          <div class="profile-avatar"><?= $initial ?></div>
          <div class="profile-name"><?= htmlspecialchars($user['name']) ?></div>
          <div class="profile-email"><?= htmlspecialchars($user['email']) ?></div>
          <div class="member-badge">Member since <?= $memberSince ?></div>
          
          <div class="profile-stats">
            <div class="pstat">
              <span class="pstat-label">Resumes Uploaded</span>
              <span class="pstat-val"><?= $stats['total_resumes'] ?? 0 ?></span>
            </div>
            <div class="pstat">
              <span class="pstat-label">Avg ATS Score</span>
              <span class="pstat-val"><?= $stats['avg_score'] ?? 0 ?>%</span>
            </div>
            <div class="pstat">
              <span class="pstat-label">Best ATS Score</span>
              <span class="pstat-val"><?= $stats['best_score'] ?? 0 ?>%</span>
            </div>
          </div>
        </div>

        <!-- RIGHT: Forms -->
        <div>
          
          <?php if ($error): ?>
            <div class="alert alert-error mb-3" id="errorAlert"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>
          <?php if ($success): ?>
            <div class="alert alert-success mb-3" id="successAlert"><?= htmlspecialchars($success) ?></div>
          <?php endif; ?>

          <!-- Personal Info Display -->
          <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
              <div class="card-title">Personal Information</div>
            </div>
            <div style="padding: 24px;">
              
              <!-- Name Form -->
              <form method="POST" class="mb-4">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="update_name" value="1">
                <div class="form-group mb-0">
                  <label class="form-label" style="display:block; margin-bottom:8px; font-weight:600;">Full Name</label>
                  <div style="display: flex; gap: 12px;">
                    <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                    <button type="submit" class="btn btn-primary" style="white-space: nowrap;">Save Name</button>
                  </div>
                </div>
              </form>

              <hr style="border: 0; border-top: 1px solid var(--card-border); margin: 24px 0;">

              <!-- Email Form -->
              <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="update_email" value="1">
                <div class="form-group mb-0">
                  <label class="form-label" style="display:block; margin-bottom:8px; font-weight:600;">Email Address</label>
                  <div style="display: flex; gap: 12px;">
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                    <button type="submit" class="btn btn-outline" style="white-space: nowrap;">Change Email</button>
                  </div>
                  <?php if (!empty($user['pending_email'])): ?>
                    <div style="font-size: 13px; color: var(--text-3); margin-top: 8px;">
                      Pending change to: <strong><?= htmlspecialchars($user['pending_email']) ?></strong> (check current email to confirm)
                    </div>
                  <?php endif; ?>
                </div>
              </form>
              
              <div style="margin-top: 16px; font-size: 13px; color: var(--red);">
                Note : you can change your name or email only two time a week
              </div>
            </div>
          </div>
          
          <!-- Security Details Display -->
          <div class="card">
            <div class="card-header">
              <div class="card-title">Security Details</div>
            </div>
            <div style="padding: 24px;">
              <div class="info-group" style="max-width: 50%;">
                <div class="info-label">Password</div>
                <div class="info-val hidden-pwd">*********</div>
              </div>
            </div>
          </div>
          
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  setTimeout(() => {
    const successAlert = document.getElementById('successAlert');
    const errorAlert = document.getElementById('errorAlert');
    if (successAlert) successAlert.style.display = 'none';
    if (errorAlert) errorAlert.style.display = 'none';
  }, 5000);
</script>
</body>
</html>
