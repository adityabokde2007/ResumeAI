<?php
// user/upload.php
require_once '../includes/config.php';
requireUserLogin();

$userId = $_SESSION['user_id'];
$db     = getDB();

// Fetch job roles
$roles = $db->query("SELECT id, title, keywords FROM job_roles WHERE is_active = 1 ORDER BY title")->fetch_all(MYSQLI_ASSOC);

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isAjax = !empty($_POST['ajax_upload']);
    
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please refresh and try again.';
    } elseif (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please select a PDF file to upload.';
    } else {
        $file     = $_FILES['resume'];
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if ($mimeType !== 'application/pdf') {
            $error = 'Only PDF files are accepted.';
        } elseif ($file['size'] > MAX_FILE_SIZE) {
            $error = 'File size must not exceed 2MB.';
        } else {
            $originalName = basename($file['name']);
            $storedName   = 'resume_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.pdf';
            $destPath     = UPLOAD_DIR . $storedName;

            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

            if (move_uploaded_file($file['tmp_name'], $destPath)) {
                // Save resume record
                $ins = $db->prepare("INSERT INTO resumes (user_id, original_name, stored_name, file_path, file_size) VALUES (?,?,?,?,?)");
                $ins->bind_param('isssi', $userId, $originalName, $storedName, $destPath, $file['size']);
                $ins->execute();
                $resumeId = $ins->insert_id;
                $ins->close();

                if ($isAjax) {
                    echo json_encode(['success' => true, 'resume_id' => $resumeId]);
                    exit;
                } else {
                    $jobRoleId = intval($_POST['job_role_id'] ?? 0);
                    header("Location: analyze.php?resume_id={$resumeId}&job_role_id={$jobRoleId}");
                    exit;
                }
            } else {
                $error = 'Failed to save the file. Please try again.';
            }
        }
    }
    
    if ($isAjax && $error) {
        echo json_encode(['success' => false, 'error' => $error]);
        exit;
    }
}

$activePage = 'upload';
define('BASE_URL', '..');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Upload Resume — ResumeAI</title>
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
        <span class="topbar-page">Upload Resume</span>
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
      <div class="page-header">
        <div>
          <div class="page-title">Upload Your Resume</div>
          <div class="page-subtitle">Upload in PDF format and let our AI provide detailed feedback, ATS scoring, and improvement suggestions</div>
        </div>
      </div>

      <div class="upload-wrapper">

        <?php if ($error): ?>
        <div class="alert alert-error mb-2">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <div id="jsErrorAlert" class="alert alert-error mb-2 d-none">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;display:inline-block;vertical-align:middle;margin-right:8px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span id="jsErrorText" style="vertical-align:middle;"></span>
        </div>

        <form method="POST" enctype="multipart/form-data" id="uploadForm">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

          <div class="upload-card">

            <!-- Drop Zone -->
            <div class="drop-zone" id="dropZone">
              <input type="file" name="resume" id="fileInput" accept=".pdf,application/pdf">
              <div class="drop-zone-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
              </div>
              <h3>Drag & drop your resume here</h3>
              <p>or click anywhere to browse files</p>
              <div class="drop-badges">
                <span class="drop-badge">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                  PDF Only
                </span>
                <span class="drop-badge">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
                  Max 2MB
                </span>
                <span class="drop-badge">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                  Secure Upload
                </span>
              </div>
            </div>

            <!-- File Preview -->
            <div class="file-preview-bar d-none" id="filePreview">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
              <div class="file-preview-info">
                <div class="file-preview-name" id="fileName">—</div>
                <div class="file-preview-size" id="fileSize">—</div>
              </div>
              <button type="button" class="file-remove" id="removeFile" title="Remove file">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>

            <!-- Job Role -->
            <div class="form-group mt-3">
              <label for="job_role_id">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;display:inline;margin-right:5px;vertical-align:text-bottom"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Target Job Role <span style="color:var(--text-3);font-weight:400">(Recommended)</span>
              </label>
              <div class="select-wrap">
                <select name="job_role_id" id="job_role_id">
                  <option value="">— Select a target role —</option>
                  <?php foreach ($roles as $role): ?>
                  <option value="<?= $role['id'] ?>" data-keywords="<?= htmlspecialchars($role['keywords']) ?>">
                    <?= htmlspecialchars($role['title']) ?>
                  </option>
                  <?php endforeach; ?>
                </select>
                <div class="select-arrow">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </div>
              </div>
            </div>

            <!-- Keywords Preview -->
            <div class="keywords-preview d-none" id="keywordsPreview">
              <div class="keywords-label">ATS Keywords for this role</div>
              <div class="keywords-tags" id="keywordsTags"></div>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn btn-primary btn-full btn-lg mt-3" id="analyzeBtn" disabled>
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:18px;height:18px"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
              Analyze Resume
            </button>

          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay d-none" id="loadingOverlay">
  <div class="spinner spinner-dark" style="width:40px;height:40px;border-width:4px"></div>
  <p>Analyzing your resume with AI... This may take 15–30 seconds</p>
</div>

<script>
const dropZone   = document.getElementById('dropZone');
const fileInput  = document.getElementById('fileInput');
const filePreview= document.getElementById('filePreview');
const fileName   = document.getElementById('fileName');
const fileSize   = document.getElementById('fileSize');
const removeBtn  = document.getElementById('removeFile');
const analyzeBtn = document.getElementById('analyzeBtn');
const roleSelect = document.getElementById('job_role_id');
const kwPreview  = document.getElementById('keywordsPreview');
const kwTags     = document.getElementById('keywordsTags');

function formatSize(bytes) {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024*1024) return (bytes/1024).toFixed(1) + ' KB';
  return (bytes/(1024*1024)).toFixed(1) + ' MB';
}

function showFile(file) {
  fileName.textContent = file.name;
  fileSize.textContent = formatSize(file.size);
  filePreview.classList.remove('d-none');
  analyzeBtn.disabled = false;
}

function clearFile() {
  fileInput.value = '';
  filePreview.classList.add('d-none');
  analyzeBtn.disabled = true;
}

let errorTimeout;
function showFrontendError(msg) {
  const alertBox = document.getElementById('jsErrorAlert');
  document.getElementById('jsErrorText').textContent = msg;
  alertBox.classList.remove('d-none');
  
  clearTimeout(errorTimeout);
  errorTimeout = setTimeout(() => {
    alertBox.classList.add('d-none');
  }, 5000);
}

fileInput.addEventListener('click', function(e) {
  // Prevent opening file dialog if a file is already loaded
  if (!filePreview.classList.contains('d-none')) {
    e.preventDefault();
    showFrontendError('Please upload only one resume document at a time.');
  }
});

fileInput.addEventListener('change', function() {
  if (this.files.length > 1) {
    showFrontendError('Please upload only one resume document at a time.');
    this.value = '';
    return;
  }
  const file = this.files[0];
  if (file) {
    if (file.type !== 'application/pdf') {
      showFrontendError('Invalid file format. Please upload a standard PDF file.');
      this.value = '';
      return;
    }
    showFile(file);
  }
});

removeBtn.addEventListener('click', clearFile);

// Drag & drop
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop', e => {
  e.preventDefault();
  dropZone.classList.remove('dragover');
  
  if (!filePreview.classList.contains('d-none')) {
    showFrontendError('Please upload only one resume document at a time.');
    return;
  }
  
  if (e.dataTransfer.files.length > 1) {
    showFrontendError('Please upload only one resume document at a time.');
    return;
  }
  
  const file = e.dataTransfer.files[0];
  if (file && file.type === 'application/pdf') {
    const dt = new DataTransfer();
    dt.items.add(file);
    fileInput.files = dt.files;
    showFile(file);
  } else {
    showFrontendError('Invalid file format. Please upload a standard PDF file.');
  }
});

// Keywords preview
roleSelect.addEventListener('change', function() {
  const kw = this.options[this.selectedIndex].dataset.keywords;
  if (kw) {
    kwTags.innerHTML = kw.split(',').map(k =>
      `<span class="keyword-tag">${k.trim()}</span>`
    ).join('');
    kwPreview.classList.remove('d-none');
  } else {
    kwPreview.classList.add('d-none');
  }
});

document.getElementById('uploadForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  const loading = document.getElementById('loadingOverlay');
  const loadingText = loading.querySelector('p');
  loading.classList.remove('d-none');
  analyzeBtn.innerHTML = '<div class="spinner"></div> Processing...';
  analyzeBtn.disabled = true;

  try {
    // 1. Upload File
    loadingText.textContent = 'Uploading your resume...';
    const fd = new FormData(this);
    fd.append('ajax_upload', '1');
    
    const uploadRes = await fetch('upload.php', { method: 'POST', body: fd }).then(r => r.json());
    
    if (!uploadRes.success) {
      throw new Error(uploadRes.error || 'Upload failed.');
    }

    // 2. Analyze
    loadingText.textContent = 'Analyzing your resume with AI... This may take 15–30 seconds';
    const analyzeFd = new FormData();
    analyzeFd.append('resume_id', uploadRes.resume_id);
    analyzeFd.append('job_role_id', document.getElementById('job_role_id').value || 0);

    const analyzeRes = await fetch('analyze.php', { method: 'POST', body: analyzeFd }).then(r => r.json());

    if (!analyzeRes.success) {
      throw new Error(analyzeRes.error || 'Analysis failed.');
    }

    // 3. Redirect
    window.location.href = analyzeRes.redirect;

  } catch (err) {
    loading.classList.add('d-none');
    analyzeBtn.innerHTML = 'Analyze Resume';
    analyzeBtn.disabled = false;
    showFrontendError(err.message);
  }
});
</script>
</body>
</html>
