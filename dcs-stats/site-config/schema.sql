-- DCS Statistics Admin Panel Database Schema
-- This is optional - the admin panel works with local data storage

-- Create database (optional)
-- CREATE DATABASE IF NOT EXISTS dcs_stats_admin;
-- USE dcs_stats_admin;

-- Admin users table
CREATE TABLE IF NOT EXISTS dcs_admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role TINYINT NOT NULL DEFAULT 1 COMMENT '1=Moderator, 2=Admin, 3=Super Admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    last_ip VARCHAR(45) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    failed_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    ip_whitelist JSON NULL COMMENT 'Array of allowed IP addresses',
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_remember_token (remember_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin activity logs
CREATE TABLE IF NOT EXISTS dcs_admin_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NULL COMMENT 'NULL for failed login attempts',
    action VARCHAR(50) NOT NULL,
    target_type VARCHAR(50) NULL COMMENT 'player, admin, settings, etc',
    target_id VARCHAR(100) NULL,
    details JSON NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_id (admin_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    INDEX idx_target (target_type, target_id),
    FOREIGN KEY (admin_id) REFERENCES dcs_admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Player bans
CREATE TABLE IF NOT EXISTS dcs_player_bans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    player_ucid VARCHAR(100) NOT NULL,
    player_name VARCHAR(255) NULL,
    admin_id INT NOT NULL,
    reason TEXT NULL,
    banned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL COMMENT 'NULL for permanent bans',
    unbanned_at TIMESTAMP NULL,
    unbanned_by INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_player_ucid (player_ucid),
    INDEX idx_active_bans (is_active, expires_at),
    INDEX idx_banned_at (banned_at),
    FOREIGN KEY (admin_id) REFERENCES dcs_admin_users(id),
    FOREIGN KEY (unbanned_by) REFERENCES dcs_admin_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin sessions (for remember me functionality)
CREATE TABLE IF NOT EXISTS dcs_admin_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_token_hash (token_hash),
    INDEX idx_expires_at (expires_at),
    INDEX idx_admin_sessions (admin_id, expires_at),
    FOREIGN KEY (admin_id) REFERENCES dcs_admin_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Player notes (admin notes about players)
CREATE TABLE IF NOT EXISTS dcs_player_notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    player_ucid VARCHAR(100) NOT NULL,
    admin_id INT NOT NULL,
    note TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_player_notes (player_ucid),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (admin_id) REFERENCES dcs_admin_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin settings (key-value store)
CREATE TABLE IF NOT EXISTS dcs_admin_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value JSON NOT NULL,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES dcs_admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create default super admin user (password: changeme123)
-- IMPORTANT: Change this password immediately after first login!
INSERT INTO dcs_admin_users (username, email, password_hash, role) VALUES 
('admin', 'admin@example.com', '$2y$10$YourHashedPasswordHere', 3)
ON DUPLICATE KEY UPDATE id=id;

-- Create indexes for better performance
CREATE INDEX idx_logs_date_range ON dcs_admin_logs(created_at, action);
CREATE INDEX idx_bans_active ON dcs_player_bans(is_active, player_ucid) WHERE is_active = TRUE;

-- Views for common queries
CREATE OR REPLACE VIEW active_bans AS
SELECT 
    pb.*,
    au.username as banned_by_username,
    au2.username as unbanned_by_username
FROM dcs_player_bans pb
LEFT JOIN dcs_admin_users au ON pb.admin_id = au.id
LEFT JOIN dcs_admin_users au2 ON pb.unbanned_by = au2.id
WHERE pb.is_active = TRUE 
  AND (pb.expires_at IS NULL OR pb.expires_at > NOW());

CREATE OR REPLACE VIEW recent_admin_activity AS
SELECT 
    al.*,
    au.username,
    au.email
FROM dcs_admin_logs al
LEFT JOIN dcs_admin_users au ON al.admin_id = au.id
WHERE al.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY al.created_at DESC;