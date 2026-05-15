<?php
// index.php
require_once 'includes/config.php';
session_start();

$isLoggedIn = !empty($_SESSION['user_id']);
$hasAccount = isset($_COOKIE['has_account']);

$getStartedHref = 'auth/register.php';
$getStartedText = 'Get Started Free';

if ($isLoggedIn) {
    $getStartedHref = 'user/dashboard.php';
    $getStartedText = 'Go to Dashboard';
} elseif ($hasAccount) {
    $getStartedHref = 'auth/login.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ResumeAI - Professional Resume Analysis</title>
  <link rel="stylesheet" href="assets/css/main.css">
  <style>
    /* Prevent horizontal scroll */
    body { overflow-x: hidden; }
  </style>
  <script src="assets/js/theme.js"></script>
</head>
<body class="home-page">

  <!-- Navbar -->
  <header class="home-navbar">
    <div class="home-container nav-container">
      <a href="index.php" class="nav-logo" style="display: flex; align-items: center; text-decoration: none; margin-left: -50px;">
        <img src="assets/images/logo.png" alt="ResumeAI" style="height: 180px; min-width: 150px; object-fit: contain; display: block; margin: -60px 0;">
      </a>
      <nav class="nav-links">
        <a href="#features">Features</a>
        <a href="#how-it-works">Workflow</a>
        <a href="#contact">Contact Us</a>
      </nav>
      <div class="nav-actions">
        <?php if ($isLoggedIn): ?>
          <a href="user/dashboard.php" class="btn btn-primary">Dashboard</a>
        <?php else: ?>
          <a href="auth/login.php" class="btn btn-outline">Log in</a>
          <a href="auth/register.php" class="btn btn-primary">Sign up</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <!-- Hero Section -->
  <section class="hero-section">
    <div class="home-container">
      <h1>Optimize your resume.<br>Accelerate your career.</h1>
      <p>Analyze your resume against industry-standard ATS algorithms, identify skill gaps, and receive actionable insights to improve your job application success rate.</p>
      <div style="display: flex; gap: 16px; justify-content: center; margin-top: 32px;">
        <a href="<?= htmlspecialchars($getStartedHref) ?>" class="btn btn-primary btn-lg"><?= htmlspecialchars($getStartedText) ?></a>
        <a href="#how-it-works" class="btn btn-outline btn-lg" style="display: flex; align-items: center; gap: 8px;">
          How to Use
        </a>
      </div>
    </div>
  </section>

  <!-- Features Section -->
  <section id="features" class="features-section">
    <div class="home-container">
      <h2 class="section-title">Platform Features</h2>
      <div class="feature-grid">
        <!-- Feature 1 -->
        <div class="feature-card">
          <div class="feature-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <h3>ATS Compatibility Scoring</h3>
          <p>Get a precise score indicating how well your resume performs in modern Applicant Tracking Systems.</p>
        </div>
        <!-- Feature 2 -->
        <div class="feature-card">
          <div class="feature-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10 21h7a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v11m0 5l4.879-4.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242z" />
            </svg>
          </div>
          <h3>Skill Gap Analysis</h3>
          <p>Automatically match your current qualifications against targeted job descriptions to find missing keywords.</p>
        </div>
        <!-- Feature 3 -->
        <div class="feature-card">
          <div class="feature-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <h3>Actionable Insights</h3>
          <p>Receive step-by-step recommendations on formatting, terminology, and structure to improve your profile.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- How It Works Section -->
  <section id="how-it-works" class="how-it-works-section">
    <div class="home-container">
      <h2 class="section-title">How to Use ResumeAI</h2>
      <div class="step-grid">
        <div class="step-card">
          <div class="step-num">1</div>
          <h3>Create an Account</h3>
          <p style="color: var(--text-3); margin-top: 10px;">Sign up securely to access your personal dashboard and document history.</p>
        </div>
        <div class="step-card">
          <div class="step-num">2</div>
          <h3>Upload Resume</h3>
          <p style="color: var(--text-3); margin-top: 10px;">Submit your resume PDF and specify the target job role or keywords.</p>
        </div>
        <div class="step-card">
          <div class="step-num">3</div>
          <h3>Review Analysis</h3>
          <p style="color: var(--text-3); margin-top: 10px;">Examine your detailed score report and apply the recommended improvements.</p>
        </div>
      </div>
    </div>
  </section>

  <footer class="home-footer" id="contact">
    <div class="home-container footer-grid">
      <div class="footer-brand">
        <a href="index.php" style="display: block; text-decoration: none;">
          <img src="assets/images/logo.png" alt="ResumeAI" style="height: 160px; min-width: 150px; object-fit: contain; margin: -50px 0 -40px -20px; display: block;">
        </a>
        <p>Optimize your resume and accelerate your career with our industry-leading AI analysis tools.</p>
        <div style="margin-top: 20px;">
          <a href="mailto:your_mail@gmail.com" class="btn btn-footer-email" style="display: inline-flex; align-items: center; gap: 8px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            Email Me
          </a>
        </div>
      </div>
      
      <div class="footer-links">
        <h4>Navigation</h4>
        <a href="index.php">Home</a>
        <a href="#features">Platform Features</a>
        <a href="#how-it-works">How to Use</a>
        <?php if ($isLoggedIn): ?>
        <a href="user/dashboard.php">Dashboard</a>
        <?php else: ?>
        <a href="auth/login.php">Login</a>
        <a href="auth/register.php">Create Account</a>
        <?php endif; ?>
      </div>

      <div class="footer-contact">
        <h4>Connect & Socials</h4>
        <div class="social-buttons">
          <a href="https://github.com/adityabokde2007" target="_blank">GitHub</a>
          <a href="https://www.linkedin.com/in/aditya-bokde-ab0b59341/" target="_blank">LinkedIn</a>
          <a href="https://www.instagram.com/adi_tya_2486/" target="_blank">Instagram</a>
        </div>
      </div>
    </div>
    <div class="home-container footer-bottom">
      <p>&copy; <?php echo date('Y'); ?> ResumeAI. All rights reserved.</p>
    </div>
  </footer>

</body>
</html>
