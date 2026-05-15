<?php
// user/dashboard.php
require_once '../includes/config.php';
requireUserLogin();

$userId = $_SESSION['user_id'];
$db     = getDB();

// Stats
$stats = $db->query("
  SELECT
    (SELECT COUNT(DISTINCT resume_id) FROM analyses WHERE user_id = $userId) AS total_resumes,
    (SELECT COUNT(*) FROM analyses WHERE user_id = $userId) AS total_analyses,
    (SELECT ROUND(AVG(ats_score),1) FROM analyses WHERE user_id = $userId) AS avg_score,
    (SELECT MAX(ats_score) FROM analyses WHERE user_id = $userId) AS best_score
")->fetch_assoc();

// Recent analyses (last 5)
$recentStmt = $db->prepare("
  SELECT a.id, a.ats_score, a.resume_strength, a.created_at,
         r.original_name, j.title AS job_role
  FROM analyses a
  LEFT JOIN resumes r ON r.id = a.resume_id
  LEFT JOIN job_roles j ON j.id = a.job_role_id
  WHERE a.user_id = ?
  ORDER BY a.created_at DESC LIMIT 5
");
$recentStmt->bind_param('i', $userId);
$recentStmt->execute();
$recentAnalyses = $recentStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$recentStmt->close();

// Score distribution for chart (last 10)
$chartStmt = $db->prepare("
  SELECT ats_score, DATE_FORMAT(created_at,'%d %b') AS date
  FROM analyses WHERE user_id = ? ORDER BY created_at ASC LIMIT 10
");
$chartStmt->bind_param('i', $userId);
$chartStmt->execute();
$chartData = $chartStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$chartStmt->close();

$activePage = 'dashboard';
define('BASE_URL', '..');
$pageTitle = 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — ResumeAI</title>
  <link rel="stylesheet" href="../assets/css/main.css">
  <script src="../assets/js/theme.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="app-layout">

  <?php include '../includes/sidebar.php'; ?>

  <div class="main-content">
    <!-- Topbar -->
    <div class="topbar">
      <div class="topbar-left">
        <a href="#">Dashboard</a>
        <span>/</span>
        <span class="topbar-page">Dashboard</span>
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

      <!-- Welcome Banner -->
      <div class="welcome-banner mb-3">
        <div class="welcome-text">
          <h2>Welcome back, <?= htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]) ?>! 👋</h2>
          <p>Here's an overview of your resume analysis activity</p>
        </div>
        <a href="upload.php" class="btn btn-dark btn-lg">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
          Upload & Analyze
        </a>
      </div>

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon blue">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          </div>
          <div>
            <div class="stat-value"><?= $stats['total_resumes'] ?? 0 ?></div>
            <div class="stat-label">Total Resumes</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
          </div>
          <div>
            <div class="stat-value"><?= $stats['total_analyses'] ?? 0 ?></div>
            <div class="stat-label">Analyses Done</div>
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

      <!-- Charts Row -->
      <div class="grid-2 mb-3">
        <div class="card">
          <div class="card-header">
            <div class="card-title">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
              Score Distribution
            </div>
          </div>
          <?php if (empty($chartData)): ?>
          <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/></svg>
            <h3>No data yet</h3>
            <p>Upload a resume to see your score distribution</p>
          </div>
          <?php else: ?>
          <canvas id="scoreChart" height="200"></canvas>
          <?php endif; ?>
        </div>

        <div class="card">
          <div class="card-header">
            <div class="card-title">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
              Top Skills Detected
            </div>
          </div>
          <?php
          // Get top skills from recent analyses
          $skillsStmt = $db->prepare("SELECT matched_skills FROM analyses WHERE user_id = ? AND matched_skills IS NOT NULL ORDER BY created_at DESC LIMIT 5");
          $skillsStmt->bind_param('i', $userId);
          $skillsStmt->execute();
          $skillRows = $skillsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
          $skillsStmt->close();
          $skillCounts = [];
          foreach ($skillRows as $row) {
            $skills = json_decode($row['matched_skills'], true) ?? [];
            foreach ($skills as $s) { $skillCounts[trim($s)] = ($skillCounts[trim($s)] ?? 0) + 1; }
          }
          arsort($skillCounts);
          $topSkills = array_slice($skillCounts, 0, 10, true);
          ?>
          <?php if (empty($topSkills)): ?>
          <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            <h3>No skills detected yet</h3>
            <p>Analyze a resume to see your top skills</p>
          </div>
          <?php else: ?>
          <div style="display:flex;flex-wrap:wrap;gap:8px;padding:8px 0;">
            <?php foreach ($topSkills as $skill => $count): ?>
            <span class="skill-chip matched">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
              <?= htmlspecialchars($skill) ?>
            </span>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Recent Analyses -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Recent Analyses
          </div>
          <a href="history.php" class="btn btn-ghost btn-sm">View All</a>
        </div>

        <?php if (empty($recentAnalyses)): ?>
        <div class="empty-state">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
          <h3>No analyses yet</h3>
          <p>Upload your first resume to get started</p>
          <a href="upload.php" class="btn btn-primary">Upload Resume</a>
        </div>
        <?php else: ?>
        <table class="data-table">
          <thead>
            <tr>
              <th>Resume</th>
              <th>Job Role</th>
              <th>ATS Score</th>
              <th>Strength</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentAnalyses as $a):
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
              <td class="text-muted"><?= date('d M Y', strtotime($a['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>

    </div><!-- /page-body -->
  </div><!-- /main-content -->
</div><!-- /app-layout -->

<?php if (!empty($chartData)): ?>
<script>
const labels = <?= json_encode(array_column($chartData, 'date')) ?>;
const scores = <?= json_encode(array_column($chartData, 'ats_score')) ?>;
new Chart(document.getElementById('scoreChart'), {
  type: 'line',
  data: {
    labels,
    datasets: [{
      label: 'ATS Score',
      data: scores,
      borderColor: '#2563eb',
      backgroundColor: 'rgba(37,99,235,0.08)',
      borderWidth: 2.5,
      pointBackgroundColor: '#2563eb',
      pointRadius: 5,
      tension: 0.4,
      fill: true
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      y: { min: 0, max: 100, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', font: { size: 12 } } },
      x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 12 } } }
    }
  }
});
</script>
<?php endif; ?>
</body>
</html>
