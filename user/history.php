<?php
// user/history.php
require_once '../includes/config.php';
requireUserLogin();

$userId = $_SESSION['user_id'];
$db     = getDB();

$error = '';
$success = '';

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_analysis_id'])) {
    if (verifyCsrf($_POST['csrf_token'] ?? '')) {
        $delId = intval($_POST['delete_analysis_id']);
        
        // Verify ownership
        $checkStmt = $db->prepare("SELECT id FROM analyses WHERE id = ? AND user_id = ?");
        $checkStmt->bind_param('ii', $delId, $userId);
        $checkStmt->execute();
        $res = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();
        
        if ($res) {
            $delStmt = $db->prepare("DELETE FROM analyses WHERE id = ?");
            $delStmt->bind_param('i', $delId);
            $delStmt->execute();
            $delStmt->close();
            $success = "Analysis deleted successfully.";
        } else {
            $error = "Analysis not found or permission denied.";
        }
    } else {
        $error = "Invalid request token.";
    }
}

// Pagination & Search
$page   = max(1, intval($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;
$search = clean($_GET['q'] ?? '');

// Stats
$statsStmt = $db->prepare("
    SELECT COUNT(*) as total_analyses,
           ROUND(AVG(ats_score), 1) as avg_score,
           MAX(ats_score) as best_score
    FROM analyses
    WHERE user_id = ?
");
$statsStmt->bind_param('i', $userId);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();
$statsStmt->close();

// Query construction
$whereClause = "a.user_id = ?";
$params = [$userId];
$types = "i";

if ($search !== '') {
    $whereClause .= " AND (r.original_name LIKE ? OR j.title LIKE ?)";
    $searchWildcard = "%{$search}%";
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $types .= "ss";
}

// Total rows for pagination
$countQuery = "SELECT COUNT(*) as total FROM analyses a LEFT JOIN resumes r ON r.id = a.resume_id LEFT JOIN job_roles j ON j.id = a.job_role_id WHERE " . $whereClause;
$countStmt = $db->prepare($countQuery);
$countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = ceil($totalRows / $limit);

// Fetch records
$dataQuery = "
    SELECT a.id, a.ats_score, a.resume_strength, a.created_at,
           r.original_name, j.title AS job_role
    FROM analyses a
    LEFT JOIN resumes r ON r.id = a.resume_id
    LEFT JOIN job_roles j ON j.id = a.job_role_id
    WHERE " . $whereClause . "
    ORDER BY a.created_at DESC
    LIMIT ? OFFSET ?
";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$dataStmt = $db->prepare($dataQuery);
$dataStmt->bind_param($types, ...$params);
$dataStmt->execute();
$analyses = $dataStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$dataStmt->close();

$activePage = 'history';
define('BASE_URL', '..');
$pageTitle = 'Analysis History';
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
        <a href="upload.php" class="btn btn-primary btn-sm">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
          New Analysis
        </a>
      </div>
    </div>

    <div class="page-body">
      
      <?php if ($error): ?>
      <div class="alert alert-error mb-2" id="errorAlert">
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>
      <?php if ($success): ?>
      <div class="alert alert-success mb-2" id="successAlert">
        <?= htmlspecialchars($success) ?>
      </div>
      <?php endif; ?>

      <div class="page-header">
        <div>
          <div class="page-title">Analysis History</div>
          <div class="page-subtitle">Review and manage your past resume analyses</div>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="stats-grid mb-3" style="grid-template-columns: repeat(3, 1fr);">
        <div class="stat-card">
          <div class="stat-icon blue">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          </div>
          <div>
            <div class="stat-value"><?= $stats['total_analyses'] ?? 0 ?></div>
            <div class="stat-label">Total Analyses</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon yellow">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
          </div>
          <div>
            <div class="stat-value"><?= $stats['avg_score'] ?? 0 ?>%</div>
            <div class="stat-label">Avg. ATS Score</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon purple">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
          </div>
          <div>
            <div class="stat-value"><?= $stats['best_score'] ?? 0 ?>%</div>
            <div class="stat-label">Best ATS Score</div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
          <div class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            All Analyses
          </div>
          <!-- Search Bar -->
          <form method="GET" class="search-bar" style="display: flex; gap: 8px;">
            <div class="input-wrap">
              <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
              <input type="text" name="q" class="form-control" placeholder="Search by name or role" value="<?= htmlspecialchars($search) ?>" style="padding-top: 8px; padding-bottom: 8px; font-size: 13px; width: 220px;">
            </div>
            <button type="submit" class="btn btn-outline btn-sm">Search</button>
            <?php if ($search !== ''): ?>
            <a href="history.php" class="btn btn-ghost btn-sm">Clear</a>
            <?php endif; ?>
          </form>
        </div>

        <?php if (empty($analyses)): ?>
        <div class="empty-state">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
          <h3>No records found</h3>
          <p>We couldn't find any analyses matching your criteria.</p>
        </div>
        <?php else: ?>
        <div class="data-table-wrap" style="overflow-x: auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Resume Name</th>
                <th>Job Role</th>
                <th>ATS Score</th>
                <th>Strength</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($analyses as $a):
                $score = $a['ats_score'];
                $scoreClass = $score >= 80 ? 'green' : ($score >= 50 ? 'yellow' : 'red');
                $strength = strtolower($a['resume_strength'] ?? 'average');
              ?>
              <tr>
                <td>
                  <div class="file-cell">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <?= htmlspecialchars($a['original_name']) ?>
                  </div>
                </td>
                <td><?= htmlspecialchars($a['job_role'] ?? '—') ?></td>
                <td><span class="score-badge <?= $scoreClass ?>"><?= $score ?>%</span></td>
                <td><span class="strength-badge <?= $strength ?>"><?= ucfirst($strength) ?></span></td>
                <td class="text-muted"><?= date('d M Y, h:i A', strtotime($a['created_at'])) ?></td>
                <td>
                  <div style="display: flex; gap: 8px;">
                    <a href="results.php?id=<?= $a['id'] ?>" class="btn btn-ghost btn-sm">View</a>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this analysis?');" style="margin:0;">
                      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                      <input type="hidden" name="delete_analysis_id" value="<?= $a['id'] ?>">
                      <button type="submit" class="btn btn-danger btn-sm" style="padding: 7px;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <div class="pagination" style="display: flex; justify-content: center; gap: 6px; padding: 16px 0;">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?><?= $search !== '' ? '&q='.urlencode($search) : '' ?>" class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-ghost' ?>">
              <?= $i ?>
            </a>
          <?php endfor; ?>
        </div>
        <?php endif; ?>

        <?php endif; ?>
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
