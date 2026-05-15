<div align="center">

# 📄 ResumeAI

### Next-Generation AI-Powered ATS Resume Analyzer

<p>
  <img src="https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white" alt="HTML5" />
  <img src="https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white" alt="CSS3" />
  <img src="https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" alt="JavaScript" />
  <img src="https://img.shields.io/badge/PHP_8+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP" />
  <img src="https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL" />
  <img src="https://img.shields.io/badge/Groq_API-F55036?style=for-the-badge&logo=groq&logoColor=white" alt="Groq" />
</p>

<p>
  <img src="https://img.shields.io/github/stars/adityabokde2007/ResumeAI?style=flat-square&color=yellow" alt="Stars" />
  <img src="https://img.shields.io/github/forks/adityabokde2007/ResumeAI?style=flat-square&color=blue" alt="Forks" />
  <img src="https://img.shields.io/github/last-commit/adityabokde2007/ResumeAI?style=flat-square&color=green" alt="Last Commit" />
  <img src="https://img.shields.io/badge/license-MIT-brightgreen?style=flat-square" alt="License" />
</p>

</div>

---

## 🎬 Demo

[![Watch ResumeAI Demo](https://img.youtube.com/vi/1qAAj-6JkPA/maxresdefault.jpg)](https://youtu.be/1qAAj-6JkPA)

> Click the thumbnail above to watch the full demo

---

## 📖 About

**ResumeAI** is a cutting-edge web application designed to help job seekers optimize their resumes for Applicant Tracking Systems (ATS). It bridges the gap between raw PDF resumes and intelligent, actionable career feedback.

Powered by the lightning-fast **Groq API (Llama 3.1)** and engineered with a **Glassmorphism Dark Mode UI**, ResumeAI delivers deep, context-aware resume analysis with a premium user experience.

---

## 🌟 Key Features

### 🧠 Intelligent ATS Analysis
- **Smart Parsing** — Extracts raw text from PDF resumes using `smalot/pdfparser`
- **Role-Based Analysis** — Analyzes resume against specific job roles (e.g., Data Scientist, Web Developer)
- **Comprehensive Feedback** — Returns ATS Score (0–100), matched skills, missing skills, and Resume Strength rating

### 🛡️ Secure User Ecosystem
- **Session Management** — Secure login/registration with hashed passwords and anti-CSRF tokens
- **Smart Routing** — Cookie tracking redirects returning users to dashboard automatically
- **Email System** — SMTP-driven emails via PHPMailer for account verification

### 📊 Interactive Dashboard & History
- **Analytics Dashboard** — Real-time metrics: total resumes analyzed, average ATS score
- **History Tracker** — View past analyses, re-read AI feedback, or delete old records
- **Polished UI** — Auto-fading alerts, drag-and-drop file upload with strict validation

### 🔒 Privacy-First Architecture
- **Auto-Destruction** — Uploaded PDFs are permanently deleted the moment text extraction completes (`unlink()`)
- **Zero-Footprint** — No resume files ever stored on server post-analysis

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, Vanilla JavaScript (ES6+) |
| Backend | PHP 8+ (Procedural + OOP) |
| Database | MySQL with Prepared Statements |
| AI Engine | Groq API — Llama 3.1 |
| PDF Parsing | smalot/pdfparser (via Composer) |
| Email | PHPMailer (SMTP) |
| UI Style | Glassmorphism, Dark Mode, CSS Variables |

---

## 🔒 Security

- **Zero-Footprint Storage** — PDFs wiped immediately post-analysis
- **Bcrypt Encryption** — `password_hash()` for all user passwords
- **CSRF Protection** — Cryptographically secure tokens per session
- **SQL Injection Prevention** — All queries use strict prepared statements

---

## ⚙️ Installation

### Prerequisites
- **XAMPP / WAMP** — PHP + MySQL server environment
- **Composer** — PHP dependency manager
- **Groq API Key** — Free key from [Groq Cloud](https://console.groq.com)

### Setup Steps

**1. Clone the repository**
```bash
git clone https://github.com/adityabokde2007/ResumeAI.git
cd ResumeAI
```

**2. Install dependencies**
```bash
composer install
```

**3. Set up the database**
- Create a MySQL database named `ai_resume_analyzer`
- Import the schema:
```bash
mysql -u root -p ai_resume_analyzer < database/schema.sql
```

**4. Configure environment**
- Rename `includes/config.example.php` → `includes/config.php`
- Fill in your credentials:
```php
DB_HOST     = "localhost"
DB_NAME     = "ai_resume_analyzer"
DB_USER     = "root"
DB_PASS     = "your_password"
GROQ_API_KEY = "your_groq_api_key"
SMTP_PASS   = "your_smtp_app_password"
```

**5. Run the app**
- Start Apache + MySQL in XAMPP
- Visit: `http://localhost/ResumeAI`

---

## 📂 Project Structure

```
ResumeAI/
├── assets/
│   ├── css/            # Stylesheets
│   ├── js/             # JavaScript files
│   └── images/         # Static images & logo
├── auth/               # Login, register, logout
├── database/           # MySQL schema & migrations
├── includes/           # Config, mailer, sidebar
├── tests/              # API, regex & DB test scripts
├── user/               # Dashboard, upload, analysis, history
├── vendor/             # Composer dependencies
├── index.php           # Landing page & auth router
└── composer.json       # Dependency definitions
```

---

## 🚀 How It Works

```
Upload PDF → Extract Text → Send to Groq AI → Parse Response → Show ATS Score + Feedback → Delete PDF
```

1. User uploads resume (PDF)
2. `smalot/pdfparser` extracts raw text
3. Text + job role sent to Groq (Llama 3.1) via cURL
4. AI returns structured JSON with score, skills, suggestions
5. Results saved to MySQL & displayed on dashboard
6. PDF immediately deleted from server

---

## 📬 Contact

<p align="center">
  <a href="mailto:adityabokde2007@gmail.com">
    <img src="https://img.shields.io/badge/Email-D14836?style=for-the-badge&logo=gmail&logoColor=white" />
  </a>
  &nbsp;
  <a href="https://github.com/adityabokde2007">
    <img src="https://img.shields.io/badge/GitHub-181717?style=for-the-badge&logo=github&logoColor=white" />
  </a>
  &nbsp;
  <a href="https://linkedin.com/in/adityabokde">
    <img src="https://img.shields.io/badge/LinkedIn-0077B5?style=for-the-badge&logo=linkedin&logoColor=white" />
  </a>
  &nbsp;
  <a href="https://instagram.com/adityabokde">
    <img src="https://img.shields.io/badge/Instagram-E4405F?style=for-the-badge&logo=instagram&logoColor=white" />
  </a>
</p>

---

<p align="center">
  <i>Architected & Developed with ❤️ by <a href="https://github.com/adityabokde2007">Aditya Bokde</a></i>
  <br/>
  🌟 Star this repo if ResumeAI helped you land your dream job!
</p>