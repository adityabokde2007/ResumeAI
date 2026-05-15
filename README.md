<div align="center">
  <img src="https://raw.githubusercontent.com/github/explore/80688e429a7d4ef2fca1e82350fe8e3517d3494d/topics/php/php.png" width="100" alt="PHP Logo">

  <h1>📄 ResumeAI</h1>
  <p><strong>Next-Generation AI-Powered ATS Resume Analyzer</strong></p>

  <p>
    <a href="https://php.net"><img src="https://img.shields.io/badge/Language-PHP-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP" /></a>
    <a href="https://mysql.com"><img src="https://img.shields.io/badge/Database-MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL" /></a>
    <a href="https://groq.com"><img src="https://img.shields.io/badge/AI_Engine-Groq_API-F55036?style=for-the-badge&logo=artificial-intelligence&logoColor=white" alt="Groq" /></a>
  </p>
</div>

---

## 📖 About ResumeAI
**ResumeAI** is a cutting-edge web application designed to help job seekers optimize their resumes for Applicant Tracking Systems (ATS). Developed as a comprehensive portfolio project, it bridges the gap between raw PDF resumes and intelligent, actionable career feedback. 

Powered by the lightning-fast **Groq API (Llama 3.1)** and engineered with a stunning **glassmorphism Dark Mode UI**, ResumeAI guarantees deep, context-aware resume analysis while delivering a premium, seamless user experience.

---

## 🌟 Key Features & Workflow

ResumeAI employs advanced text extraction and AI prompting to deliver highly accurate resume feedback:

### 🧠 1. Intelligent ATS Analysis
The core engine of the platform, handling the complex PDF-to-AI pipeline.
- **Smart Parsing:** Extracts raw text from uploaded PDF resumes using the robust `smalot/pdfparser`.
- **Role-Based Analysis:** Analyzes the extracted text against specific job roles (e.g., Data Scientist, Web Developer).
- **Comprehensive Feedback:** Returns an accurate ATS Score (0-100), matched skills, missing skills, and a calculated "Resume Strength" rating.

### 🛡️ 2. Secure User Ecosystem
A fully robust authentication and tracking system.
- **Session Management:** Secure login and registration with hashed passwords and anti-CSRF tokens.
- **Smart Routing:** Persistent cookie tracking intelligently redirects returning users to their dashboard or the login screen.
- **Communication:** SMTP-driven email logic via PHPMailer for potential account verification.

### 📊 3. Interactive Dashboard & History
Empowering users with their personal data.
- **Analytics Dashboard:** Real-time metrics showing total resumes analyzed and average ATS score.
- **History Tracker:** A dedicated module to view past analyses, re-read AI feedback, or delete old records.
- **Polished UI Interactions:** 5-second auto-fading success/error alerts and strictly validated drag-and-drop file inputs.

### 🔒 4. Privacy-First Architecture
Ensuring absolute data protection for user documents.
- **Auto-Destruction Protocol:** Uploaded PDF files are permanently and automatically deleted (`unlink()`) from the server the exact millisecond the text extraction is complete.

---

## 🛠️ Technical Architecture & Stack

### Frontend Application
- **Language:** HTML5, Vanilla JavaScript, CSS3
- **UI/UX:** Custom Glassmorphism Aesthetics, Deep Dark Mode, CSS Variables System.
- **Animations:** Fluid CSS transitions, hover effects, and custom loading spinners.
- **Validation:** Strict client-side file type and concurrent-upload prevention.

### Backend Infrastructure
- **Core:** Core PHP 8+ (Procedural & Object-Oriented blend)
- **Database:** MySQL relational database utilizing Prepared Statements.
- **AI Integration:** PHP cURL requests to the Groq LLM API.
- **Dependencies:** Managed via Composer.

---

## 🔒 Security Posture
Data integrity and privacy are paramount in ResumeAI:
- **Zero-Footprint Storage:** Server completely wipes PDF files post-analysis to prevent data hoarding.
- **Encrypted Credentials:** `password_hash()` (Bcrypt) ensures all user passwords are mathematically secure.
- **CSRF Protection:** Cryptographically secure CSRF tokens generated per session to prevent cross-site request forgery.
- **Prepared Statements:** All database queries utilize strict bound parameters to neutralize SQL injection vulnerabilities.

---

## ⚙️ Installation & Setup Guide

### Prerequisites
- **XAMPP/WAMP:** Or any standard PHP/MySQL server environment.
- **Composer:** PHP dependency manager.
- **Groq Account:** A free API key from Groq Cloud.

### Build Instructions
1. **Clone the Repository:**
   ```bash
   git clone https://github.com/yourusername/ResumeAI.git
   ```
2. **Install Dependencies:**
   Navigate to the project root and run:
   ```bash
   composer install
   ```
3. **Database Configuration:**
   - Create a MySQL database named `ai_resume_analyzer`.
   - Import the structural schema found in `database/schema.sql`.
4. **Environment Setup:**
   - Rename `includes/config.example.php` to `includes/config.php`.
   - Open `config.php` and insert your Database Credentials, SMTP App Password, and Groq API Key.
5. **Run the App:**
   - Start your Apache and MySQL servers in XAMPP.
   - Visit `http://localhost/ResumeAI` in your browser.

---

## 📂 Codebase Anatomy
```text
ResumeAI/
├── assets/              # CSS, JavaScript, and static images
├── auth/                # Login, registration, and logout logic
├── database/            # MySQL schema and migration scripts
├── includes/            # Global configs, mailer logic, and sidebars
├── tests/               # API, Regex, and Database unit testing scripts
├── user/                # Core AI analysis, upload logic, and dashboard
├── vendor/              # Composer dependencies (Smalot PDF)
├── index.php            # Dynamic landing page & Auth router
└── composer.json        # Project dependency definitions
```

---

<p align="center">
  <i>Architected & Developed with ❤️ for the future of AI recruitment.</i>
</p>
