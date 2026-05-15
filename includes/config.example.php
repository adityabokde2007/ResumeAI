<?php
// ============================================
// config.example.php — Database Configuration
// RENAME THIS FILE TO: config.php
// ============================================

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// ── Database Credentials ──
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ai_resume_analyzer');
define('DB_CHARSET', 'utf8mb4');

// ── App Settings ──
define('APP_NAME', 'ResumeAI');
define('APP_URL', 'http://localhost/resumeai_project/resumeai'); // Update this path if deployed
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB

// ── Groq API ──
define('GROQ_API_KEY', 'YOUR_GROQ_API_KEY_HERE');
define('GROQ_MODEL', 'llama-3.1-8b-instant');
define('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions');

// ── SMTP / Mailer ──
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587); // Use 465 for SMTPS/SSL or 587 for STARTTLS
define('SMTP_USER', 'your_email@gmail.com'); // Replace with your Gmail address
define('SMTP_PASS', 'your_16_character_app_password'); // Replace with your 16-character App Password
define('SMTP_FROM_EMAIL', 'your_email@gmail.com');
define('SMTP_FROM_NAME', 'ResumeAI Support');

// ── Session Security ──
define('SESSION_LIFETIME', 3600); // 1 hour

// ── Database Connection (MySQLi) ──
function getDB(): mysqli {
    static $conn = null;
    if ($conn === null) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $conn->set_charset(DB_CHARSET);
        } catch (mysqli_sql_exception $e) {
            error_log('DB Connection Error: ' . $e->getMessage());
            http_response_code(503);
            die(json_encode(['error' => 'Database connection failed.']));
        }
    }
    return $conn;
}

// ── Session Init ──
function initSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => false, // Set true in production with HTTPS
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        session_start();
        // Regenerate session ID periodically
        if (!isset($_SESSION['_init'])) {
            session_regenerate_id(true);
            $_SESSION['_init'] = true;
        }
    }
}

// ── Auth Guards ──
function requireUserLogin(): void {
    initSession();
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . APP_URL . '/auth/login.php');
        exit;
    }
}

function requireAdminLogin(): void {
    initSession();
    if (empty($_SESSION['admin_id'])) {
        header('Location: ' . APP_URL . '/admin/login.php');
        exit;
    }
}

// ── CSRF Token ──
function csrfToken(): string {
    initSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    initSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ── Sanitize Input ──
function clean(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// ── JSON Response Helper ──
function jsonResponse(bool $success, string $message, array $data = []): void {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}
