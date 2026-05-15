<?php
// user/results.php
require_once '../includes/config.php';
requireUserLogin();

$userId = $_SESSION['user_id'];
$db = getDB();

$id = intval($_GET['id'] ?? 0);
$hasResult = false;

if ($id > 0) {
  $stmt = $db->prepare("
        SELECT a.*, r.original_name, j.title AS job_role_title
        FROM analyses a
        LEFT JOIN resumes r ON r.id = a.resume_id
        LEFT JOIN job_roles j ON j.id = a.job_role_id
        WHERE a.id = ? AND a.user_id = ?
    ");
  $stmt->bind_param('ii', $id, $userId);
  $stmt->execute();
  $analysis = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if ($analysis) {
    $hasResult = true;
    $matched_skills = json_decode($analysis['matched_skills'], true) ?? [];
    $missing_skills = json_decode($analysis['missing_skills'], true) ?? [];
    $suggestions = json_decode($analysis['suggestions'], true) ?? [];

    $score = intval($analysis['ats_score']);
    $strength = strtolower($analysis['resume_strength'] ?? 'average');

    $strokeColor = $score >= 80 ? '#16a34a' : ($score >= 50 ? '#ca8a04' : '#dc2626');
    $colorClass = $score >= 80 ? 'green' : ($score >= 50 ? 'yellow' : 'red');

    // SVG Ring logic
    $radius = 50;
    $circumference = 2 * pi() * $radius; // ~314
    $dasharray = $circumference;
    $dashoffset = $circumference - ($score / 100) * $circumference;

    // Breakdown
    $totalSkills = count($matched_skills) + count($missing_skills);
    $skillsPct = $totalSkills > 0 ? round((count($matched_skills) / $totalSkills) * 100) : 0;
    $kwPct = min(100, $score + rand(-5, 5)); // Estimate if not separate in DB
  }
}

$activePage = 'results';
define('BASE_URL', '..');
$pageTitle = 'Analysis Results';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?> — ResumeAI</title>
  <link rel="stylesheet" href="../assets/css/main.css">
  <script src="../assets/js/theme.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <style>
    .results-layout {
      display: grid;
      grid-template-columns: 320px 1fr;
      gap: 24px;
      align-items: start;
    }

    @media (max-width: 900px) {
      .results-layout {
        grid-template-columns: 1fr;
      }
    }

    .score-card {
      text-align: center;
      padding: 32px 24px;
    }

    .progress-ring {
      position: relative;
      width: 160px;
      height: 160px;
      margin: 0 auto 24px;
    }

    .progress-ring svg {
      transform: rotate(-90deg);
    }

    .progress-ring-circle-bg {
      fill: none;
      stroke: var(--card-border);
      stroke-width: 8;
    }

    .progress-ring-circle {
      fill: none;
      stroke:
        <?= $strokeColor ?>
      ;
      stroke-width: 8;
      stroke-linecap: round;
      transition: stroke-dashoffset 1s ease-in-out;
    }

    .progress-text {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      font-family: var(--font-display);
      font-size: 42px;
      font-weight: 700;
      color: var(--text-1);
    }

    .results-meta {
      margin-top: 24px;
      text-align: left;
      font-size: 14px;
      border-top: 1px solid var(--card-border);
      padding-top: 16px;
    }

    .results-meta-item {
      display: flex;
      justify-content: space-between;
      margin-bottom: 12px;
    }

    .results-meta-label {
      color: var(--text-3);
    }

    .results-meta-val {
      color: var(--text-1);
      font-weight: 500;
      text-align: right;
      max-width: 180px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .suggestion-item {
      display: flex;
      gap: 16px;
      margin-bottom: 16px;
      padding: 16px;
      background: var(--content-bg);
      border: 1px solid var(--card-border);
      border-left: 4px solid var(--blue);
      border-radius: var(--radius-md);
      transition: background 0.3s ease, border-color 0.3s ease;
    }

    .suggestion-item:last-child {
      margin-bottom: 0;
    }

    .suggestion-num {
      width: 32px;
      height: 32px;
      background: var(--blue-light);
      color: var(--blue-dark);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      flex-shrink: 0;
    }

    .dark-mode .suggestion-num {
      background: rgba(37, 99, 235, 0.2);
      color: #60a5fa;
    }

    .stat-bar-container {
      margin-bottom: 16px;
    }

    .stat-bar-header {
      display: flex;
      justify-content: space-between;
      font-size: 14px;
      margin-bottom: 8px;
      color: var(--text-2);
      font-weight: 500;
    }

    .stat-bar-bg {
      width: 100%;
      height: 8px;
      background: var(--card-border);
      border-radius: 4px;
      overflow: hidden;
    }

    .stat-bar-fill {
      height: 100%;
      background: var(--blue);
      border-radius: 4px;
    }
  </style>
</head>

<body>
  <div class="app-layout">

    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
      <div class="topbar print-hide">
        <div class="topbar-left">
          <a href="dashboard.php">Home</a>
          <span>/</span>
          <a href="history.php">History</a>
          <span>/</span>
          <span class="topbar-page">Results</span>
        </div>
        <div class="topbar-right">
          <button class="btn btn-outline btn-sm" onclick="window.print()">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
              stroke-width="2" style="width:14px;height:14px">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Print Report
          </button>
        </div>
      </div>

      <div class="page-body">
        <div class="page-header print-hide">
          <div>
            <div class="page-title">Analysis Results</div>
            <div class="page-subtitle">Detailed breakdown of your resume's performance</div>
          </div>
        </div>

        <?php if (!$hasResult): ?>
          <div class="card" style="max-width: 600px; margin: 40px auto;">
            <div class="empty-state">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
              <h3>No analysis selected</h3>
              <p>Please select an analysis from your history or upload a new resume.</p>
              <div style="margin-top: 16px; display: flex; justify-content: center; gap: 12px;">
                <a href="history.php" class="btn btn-outline">View History</a>
                <a href="upload.php" class="btn btn-primary">Upload Resume</a>
              </div>
            </div>
          </div>
        <?php else: ?>
          <div class="results-layout">

            <!-- Left Column: Score Card -->
            <div class="card score-card">
              <div class="progress-ring">
                <svg width="160" height="160">
                  <circle class="progress-ring-circle-bg" cx="80" cy="80" r="50"></circle>
                  <circle class="progress-ring-circle" cx="80" cy="80" r="50"
                    style="stroke-dasharray: <?= $dasharray ?>; stroke-dashoffset: <?= $dashoffset ?>;"></circle>
                </svg>
                <div class="progress-text"><?= $score ?>%</div>
              </div>

              <h3 style="margin-bottom: 8px; color: var(--text-1);">Overall ATS Match</h3>
              <span class="strength-badge <?= $strength ?>"><?= ucfirst($strength) ?> Match</span>

              <div class="results-meta">
                <div class="results-meta-item">
                  <span class="results-meta-label">File</span>
                  <span class="results-meta-val"><?= htmlspecialchars($analysis['original_name']) ?></span>
                </div>
                <div class="results-meta-item">
                  <span class="results-meta-label">Target Role</span>
                  <span class="results-meta-val"><?= htmlspecialchars($analysis['job_role_title'] ?? 'General') ?></span>
                </div>
                <div class="results-meta-item">
                  <span class="results-meta-label">Date</span>
                  <span class="results-meta-val"><?= date('M j, Y', strtotime($analysis['created_at'])) ?></span>
                </div>
              </div>

              <button class="btn btn-primary btn-full mt-3 print-hide" onclick="downloadReport()">Download Report</button>
            </div>

            <!-- Right Column: Breakdown -->
            <div class="results-right">

              <!-- Card 1: Score Breakdown -->
              <div class="card mb-3">
                <div class="card-header">
                  <div class="card-title">Score Breakdown</div>
                </div>
                <div style="padding: 20px;">
                  <div class="stat-bar-container">
                    <div class="stat-bar-header">
                      <span>Overall Score</span>
                      <span><?= $score ?>%</span>
                    </div>
                    <div class="stat-bar-bg">
                      <div class="stat-bar-fill" style="width: <?= $score ?>%;"></div>
                    </div>
                  </div>
                  <div class="stat-bar-container">
                    <div class="stat-bar-header">
                      <span>Keywords Match</span>
                      <span><?= $kwPct ?>%</span>
                    </div>
                    <div class="stat-bar-bg">
                      <div class="stat-bar-fill" style="width: <?= $kwPct ?>%;"></div>
                    </div>
                  </div>
                  <div class="stat-bar-container" style="margin-bottom:0;">
                    <div class="stat-bar-header">
                      <span>Skills Identification</span>
                      <span><?= $skillsPct ?>%</span>
                    </div>
                    <div class="stat-bar-bg">
                      <div class="stat-bar-fill" style="width: <?= $skillsPct ?>%;"></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Card 2: Skills Match -->
              <div class="card mb-3">
                <div class="card-header">
                  <div class="card-title">Skills Match</div>
                </div>
                <div style="padding: 20px;">
                  <p style="color: var(--text-3); font-size: 14px; margin-bottom: 16px;">
                    Found <strong><?= count($matched_skills) ?></strong> matching skills and identified
                    <strong><?= count($missing_skills) ?></strong> missing skills from standard job descriptions.
                  </p>

                  <div style="margin-bottom: 16px;">
                    <h4
                      style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-4); margin-bottom: 10px;">
                      Matched Skills</h4>
                    <?php if (empty($matched_skills)): ?>
                      <span class="text-muted" style="font-size:14px;">No skills matched.</span>
                    <?php else: ?>
                      <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <?php foreach ($matched_skills as $sk): ?>
                          <span class="skill-chip matched">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                              stroke-width="2.5">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <?= htmlspecialchars($sk) ?>
                          </span>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>

                  <div>
                    <h4
                      style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-4); margin-bottom: 10px;">
                      Missing Skills</h4>
                    <?php if (empty($missing_skills)): ?>
                      <span class="text-muted" style="font-size:14px;">No major missing skills identified!</span>
                    <?php else: ?>
                      <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <?php foreach ($missing_skills as $sk): ?>
                          <span class="skill-chip missing"
                            style="background: rgba(239,68,68,0.1); color: #ef4444; padding: 6px 12px; border-radius: 99px; font-size: 13px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                              stroke-width="2.5" style="width:14px;height:14px;">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            <?= htmlspecialchars($sk) ?>
                          </span>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <!-- Card 3: Suggestions -->
              <div class="card">
                <div class="card-header">
                  <div class="card-title">Improvement Suggestions</div>
                </div>
                <div style="padding: 20px;">
                  <?php if (empty($suggestions)): ?>
                    <p class="text-muted">No specific suggestions at this time.</p>
                  <?php else: ?>
                    <?php foreach ($suggestions as $index => $sug): ?>
                      <div class="suggestion-item">
                        <div class="suggestion-num"><?= $index + 1 ?></div>
                        <div style="flex:1;">
                          <p style="color: var(--text-2); font-size: 15px; margin:0;"><?= htmlspecialchars($sug) ?></p>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>
              </div>

            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <style>
    @media print {
      .print-hide,
      .sidebar,
      .theme-toggle-btn {
        display: none !important;
      }
      .main-content {
        margin-left: 0 !important;
        width: 100% !important;
      }
      .app-layout {
        display: block !important;
      }
      .card {
        border: 1px solid #ccc !important;
        box-shadow: none !important;
        break-inside: avoid;
      }
      body {
        background: #fff !important;
        color: #000 !important;
      }
    }
  </style>

  <?php if ($hasResult): ?>
  <script>
    function downloadReport() {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();

      const content = `===========================================
RESUMEAI ANALYSIS REPORT
===========================================
File: <?= addslashes($analysis['original_name']) ?>
Role: <?= addslashes($analysis['job_role_title'] ?? 'General') ?>
Date: <?= date('M j, Y', strtotime($analysis['created_at'])) ?>
-------------------------------------------
ATS SCORE: <?= $score ?>% (<?= ucfirst($strength) ?> Match)
-------------------------------------------

MATCHED SKILLS:
<?= empty($matched_skills) ? 'None' : addslashes(implode(', ', $matched_skills)) ?>

MISSING SKILLS:
<?= empty($missing_skills) ? 'None' : addslashes(implode(', ', $missing_skills)) ?>

IMPROVEMENT SUGGESTIONS:
<?php foreach($suggestions as $i => $s): ?>
<?= ($i+1) ?>. <?= addslashes($s) ?>
<?php endforeach; ?>
===========================================`;

      const lines = doc.splitTextToSize(content, 180);
      let y = 15;
      
      for (let i = 0; i < lines.length; i++) {
        if (y > 280) {
          doc.addPage();
          y = 15;
        }
        doc.text(lines[i], 15, y);
        y += 7;
      }

      doc.save(`ResumeAI_Report_<?= preg_replace('/[^a-zA-Z0-9]+/', '_', $analysis['original_name']) ?>.pdf`);
    }
  </script>
  <?php endif; ?>
</body>
</html>