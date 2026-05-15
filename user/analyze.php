<?php
// user/analyze.php
require_once '../includes/config.php';
requireUserLogin();
require_once '../vendor/autoload.php';

$db = getDB();
$userId = $_SESSION['user_id'];

$resumeId = intval($_GET['resume_id'] ?? ($_POST['resume_id'] ?? 0));
$jobRoleId = intval($_GET['job_role_id'] ?? ($_POST['job_role_id'] ?? 0));

// Verify user owns resume
$stmt = $db->prepare("SELECT id, file_path FROM resumes WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $resumeId, $userId);
$stmt->execute();
$resume = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$resume) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo json_encode(['success' => false, 'error' => 'Resume not found or access denied.']);
        exit;
    } else {
        die('Resume not found or access denied.');
    }
}

// POST logic (AJAX handler)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // Extract text
    $text = '';
    try {
        $parser = new \Smalot\PdfParser\Parser();
        $pdf    = $parser->parseFile($resume['file_path']);
        $text   = $pdf->getText();
    } catch (Exception $e) {
        if (file_exists($resume['file_path'])) @unlink($resume['file_path']);
        echo json_encode(['success' => false, 'error' => 'Failed to parse PDF: ' . $e->getMessage()]);
        exit;
    }

    // Securely delete the file immediately after extracting text to save server space
    if (file_exists($resume['file_path'])) {
        @unlink($resume['file_path']);
    }

    if (empty(trim($text))) {
        echo json_encode(['success' => false, 'error' => 'No readable text could be extracted from this PDF. Please ensure it is a standard text-based PDF and not an image or scan.']);
        exit;
    }
    
    // Clean text for JSON encoding (prevent invalid UTF-8)
    $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
    $text = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', ' ', $text);
    
    // Limit text length to avoid token limits
    $text = mb_substr($text, 0, 15000);

    // Get keywords
    $stmt = $db->prepare("SELECT title, keywords FROM job_roles WHERE id = ?");
    $stmt->bind_param('i', $jobRoleId);
    $stmt->execute();
    $jobRole = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $keywords = $jobRole ? $jobRole['keywords'] : 'General professional skills';

    // Call Groq API
    $prompt = "You are a professional ATS resume analyzer. Analyze the resume against job role keywords and respond ONLY with valid JSON.\n\nResume text: " . $text . "\n\nJob keywords: " . $keywords . "\n\nReturn JSON with exactly these keys: score (0-100 integer), matched_skills (array of strings), missing_skills (array of strings), suggestions (array of strings, max 5), strength (one of: Weak, Average, Good, Excellent). DO NOT output markdown blocks or any other text.";

    $ch = curl_init(GROQ_API_URL);
    $payloadData = [
        'model' => GROQ_MODEL,
        'messages' => [
            ['role' => 'system', 'content' => 'You are a professional ATS resume analyzer. Respond ONLY with raw valid JSON.'],
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.1
    ];
    $payload = json_encode($payloadData);
    
    if (!$payload) {
        echo json_encode(['success' => false, 'error' => 'Internal error: Failed to encode payload. ' . json_last_error_msg()]);
        exit;
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . GROQ_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) {
        $errorMsg = 'AI Analysis failed: HTTP ' . $httpCode;
        if ($response) {
            $respObj = json_decode($response, true);
            if ($respObj && isset($respObj['error']['message'])) {
                $errorMsg .= ' - ' . $respObj['error']['message'];
            } else {
                $errorMsg .= ' - ' . substr($response, 0, 150);
            }
        } elseif ($curlErr) {
            $errorMsg .= ' - ' . $curlErr;
        }
        echo json_encode(['success' => false, 'error' => $errorMsg]);
        exit;
    }

    $resData = json_decode($response, true);
    $aiContent = $resData['choices'][0]['message']['content'] ?? '';
    
    $aiJson = json_decode($aiContent, true);
    if (!$aiJson) {
        echo json_encode(['success' => false, 'error' => 'Failed to parse AI response.']);
        exit;
    }

    // Save to DB
    $score = intval($aiJson['score'] ?? 0);
    $matched = json_encode($aiJson['matched_skills'] ?? []);
    $missing = json_encode($aiJson['missing_skills'] ?? []);
    $suggestions = json_encode($aiJson['suggestions'] ?? []);
    $strength = clean($aiJson['strength'] ?? 'Average');

    $ins = $db->prepare("INSERT INTO analyses (user_id, resume_id, job_role_id, ats_score, matched_skills, missing_skills, suggestions, resume_strength) VALUES (?,?,?,?,?,?,?,?)");
    $ins->bind_param('iiiissss', $userId, $resumeId, $jobRoleId, $score, $matched, $missing, $suggestions, $strength);
    if ($ins->execute()) {
        $analysisId = $ins->insert_id;
        echo json_encode(['success' => true, 'redirect' => "results.php?id={$analysisId}"]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save analysis to database.']);
    }
    $ins->close();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Analyzing... — ResumeAI</title>
  <link rel="stylesheet" href="../assets/css/main.css">
  <script src="../assets/js/theme.js"></script>
  <style>
    body { background: var(--bg-1); color: var(--text-1); font-family: 'Inter', sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
    .loading-box { text-align: center; background: var(--bg-2); padding: 40px; border-radius: 16px; border: 1px solid var(--card-border); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.3); max-width: 400px; width: 100%; }
    .spinner { margin: 0 auto 24px auto; width: 48px; height: 48px; border: 4px solid var(--border); border-top-color: var(--blue); border-radius: 50%; animation: spin 1s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
  </style>
</head>
<body>
  <div class="loading-box" id="loadingBox">
    <div class="spinner"></div>
    <h2 style="margin:0 0 8px 0;font-size:20px;">Analyzing Resume</h2>
    <p style="color:var(--text-2);font-size:14px;margin:0;">Please wait while our AI extracts your skills and calculates your ATS score...</p>
    
    <div id="errorBox" class="alert alert-error" style="display:none; margin-top: 20px; text-align:left;">
       <span id="errorMsg"></span>
       <br><br>
       <a href="upload.php" class="btn btn-ghost" style="padding:4px 12px; display:inline-block; margin-top:10px;">Go Back</a>
    </div>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const fd = new FormData();
      fd.append('resume_id', <?= $resumeId ?>);
      fd.append('job_role_id', <?= $jobRoleId ?>);
      
      fetch('analyze.php', {
        method: 'POST',
        body: fd
      })
      .then(r => r.json())
      .then(res => {
        if(res.success) {
          window.location.replace(res.redirect);
        } else {
          document.querySelector('.spinner').style.display = 'none';
          document.getElementById('errorBox').style.display = 'block';
          document.getElementById('errorMsg').textContent = res.error || 'Unknown error occurred.';
        }
      })
      .catch(err => {
        document.querySelector('.spinner').style.display = 'none';
        document.getElementById('errorBox').style.display = 'block';
        document.getElementById('errorMsg').textContent = 'Network error while analyzing resume.';
      });
    });
  </script>
</body>
</html>
