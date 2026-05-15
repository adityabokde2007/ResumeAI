<?php
// includes/sidebar.php
// Usage: $activePage = 'dashboard'; include '../includes/sidebar.php';
if (!isset($activePage)) $activePage = '';
$userName  = $_SESSION['user_name']  ?? 'User';
$userEmail = $_SESSION['user_email'] ?? '';
$initial   = strtoupper(substr($userName, 0, 1));
?>
<aside class="sidebar" id="sidebar">

  <!-- Logo -->
  <div class="sidebar-logo">
    <div class="sidebar-logo-icon">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
      </svg>
    </div>
    <span class="sidebar-logo-text">Resume<span>AI</span></span>
  </div>

  <!-- Navigation -->
  <nav class="sidebar-nav">
    <div class="nav-section-label">Main</div>

    <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/user/dashboard.php"
       class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
      </svg>
      Dashboard
    </a>

    <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/user/upload.php"
       class="nav-item <?= $activePage === 'upload' ? 'active' : '' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
      </svg>
      Upload Resume
    </a>

    <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/user/history.php"
       class="nav-item <?= $activePage === 'history' ? 'active' : '' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      Analysis History
    </a>

    <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/user/results.php"
       class="nav-item <?= $activePage === 'results' ? 'active' : '' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
      </svg>
      Results
    </a>

    <div class="nav-section-label">Account</div>

    <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/user/profile.php"
       class="nav-item <?= $activePage === 'profile' ? 'active' : '' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
      </svg>
      My Profile
    </a>

    <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/user/settings.php"
       class="nav-item <?= $activePage === 'settings' ? 'active' : '' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
      </svg>
      Settings
    </a>
  </nav>

  <!-- Footer: User + Logout -->
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="sidebar-avatar"><?= $initial ?></div>
      <div class="sidebar-user-info">
        <div class="sidebar-user-name"><?= htmlspecialchars($userName) ?></div>
        <div class="sidebar-user-email"><?= htmlspecialchars($userEmail) ?></div>
      </div>
    </div>
    <a href="#" onclick="document.getElementById('logout-modal').style.display='flex'; return false;" class="logout-btn">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
      </svg>
      Log Out
    </a>
  </div>

  <!-- Logout Confirmation Modal -->
  <div id="logout-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center;">
    <div class="card" style="width: 400px; max-width: 90%; padding: 32px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);">
      <h3 style="color: var(--text-1); margin-top: 0; margin-bottom: 12px; font-size: 20px;">Confirm Logout</h3>
      <p style="color: var(--text-2); margin-bottom: 24px; font-size: 14px; line-height: 1.5;">
        Are you sure you want to log out of your account?
      </p>
      <div style="display: flex; gap: 12px; justify-content: flex-end;">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('logout-modal').style.display='none'">Cancel</button>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/auth/logout.php" class="btn btn-danger" style="text-decoration: none;">Yes, Log Out</a>
      </div>
    </div>
  </div>

</aside>
