-- ============================================
-- AI Resume Analyzer - Complete Database Schema
-- ============================================

CREATE DATABASE IF NOT EXISTS ai_resume_analyzer CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ai_resume_analyzer;

-- ============================================
-- USERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    is_verified TINYINT(1) DEFAULT 0,
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_token_expiry DATETIME DEFAULT NULL,
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- ADMINS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- JOB ROLES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS job_roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    keywords TEXT NOT NULL COMMENT 'Comma-separated ATS keywords',
    description TEXT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- RESUMES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS resumes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL UNIQUE,
    file_path VARCHAR(500) NOT NULL,
    file_size INT UNSIGNED NOT NULL COMMENT 'Size in bytes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- ANALYSES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS analyses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    resume_id INT UNSIGNED NOT NULL,
    job_role_id INT UNSIGNED DEFAULT NULL,
    ats_score TINYINT UNSIGNED DEFAULT 0 COMMENT '0-100',
    matched_skills TEXT DEFAULT NULL COMMENT 'JSON array',
    missing_skills TEXT DEFAULT NULL COMMENT 'JSON array',
    suggestions TEXT DEFAULT NULL COMMENT 'JSON array',
    resume_strength VARCHAR(50) DEFAULT NULL COMMENT 'Weak/Average/Good/Excellent',
    raw_response LONGTEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (resume_id) REFERENCES resumes(id) ON DELETE CASCADE,
    FOREIGN KEY (job_role_id) REFERENCES job_roles(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- ADMIN LOGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS admin_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id INT UNSIGNED NOT NULL,
    action VARCHAR(255) NOT NULL,
    target_type VARCHAR(50) DEFAULT NULL COMMENT 'user/job_role/analysis',
    target_id INT UNSIGNED DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- SAMPLE DATA
-- ============================================

-- Default Admin (password: Admin@123)
INSERT INTO admins (name, email, password) VALUES
('Super Admin', 'admin@resumeai.com', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMqJqhcVY29lFc5bBbLKHC3Kti');

-- Job Roles with ATS Keywords
INSERT INTO job_roles (title, keywords) VALUES
('Software Engineer', 'JavaScript,Python,Java,React,Node.js,SQL,Git,REST API,Agile,Docker,AWS,TypeScript,CI/CD,Unit Testing,OOP'),
('Data Analyst', 'Python,SQL,Excel,Tableau,Power BI,Statistics,Machine Learning,Pandas,NumPy,Data Visualization,R,ETL,Big Data,Jupyter'),
('Product Manager', 'Roadmap,Agile,Scrum,User Stories,KPIs,Stakeholder Management,Jira,Product Strategy,Market Research,A/B Testing,Wireframes'),
('UI/UX Designer', 'Figma,Adobe XD,Sketch,Prototyping,User Research,Wireframing,Design Systems,CSS,Accessibility,Usability Testing,InVision'),
('DevOps Engineer', 'Docker,Kubernetes,AWS,CI/CD,Jenkins,Terraform,Linux,Ansible,Git,Monitoring,Prometheus,Grafana,Azure,GCP'),
('Data Scientist', 'Python,Machine Learning,Deep Learning,TensorFlow,PyTorch,SQL,Statistics,NLP,Computer Vision,Scikit-learn,Keras,R'),
('Frontend Developer', 'HTML,CSS,JavaScript,React,Vue.js,TypeScript,Webpack,REST API,Git,Responsive Design,SASS,Redux,Testing'),
('Backend Developer', 'Python,Node.js,Java,PHP,SQL,NoSQL,REST API,GraphQL,Docker,Microservices,Redis,PostgreSQL,MongoDB');
