<?php
/**
 * Authentication System for Admin Panel
 * Handles login, logout, session management, and security
 */

// Define admin panel constant
define('ADMIN_PANEL', true);

// Include configuration
require_once __DIR__ . '/config.php';

// Start session with secure settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

if (ENFORCE_HTTPS && isset($_SERVER['HTTPS'])) {
    ini_set('session.cookie_secure', 1);
}

session_name(ADMIN_SESSION_NAME);
session_start();

/**
 * Initialize admin data files if they don't exist
 */
function initializeAdminData() {
    // Create data directory if it doesn't exist
    if (!is_dir(ADMIN_DATA_DIR)) {
        mkdir(ADMIN_DATA_DIR, 0700, true);
    }
    
    // Initialize users file with default admin
    if (!file_exists(ADMIN_USERS_FILE)) {
        $defaultAdmin = [
            'id' => 1,
            'username' => DEFAULT_ADMIN_USERNAME,
            'email' => DEFAULT_ADMIN_EMAIL,
            'password_hash' => password_hash(DEFAULT_ADMIN_PASSWORD, PASSWORD_BCRYPT),
            'role' => ROLE_AIR_BOSS,
            'created_at' => date(DATE_FORMAT),
            'last_login' => null,
            'is_active' => true,
            'failed_attempts' => 0,
            'locked_until' => null
        ];
        
        file_put_contents(ADMIN_USERS_FILE, json_encode([$defaultAdmin], JSON_PRETTY_PRINT));
        chmod(ADMIN_USERS_FILE, 0600);
    }
    
    // Initialize other data files
    $dataFiles = [
        ADMIN_LOGS_FILE => [],
        ADMIN_BANS_FILE => [],
        ADMIN_SESSIONS_FILE => []
    ];
    
    foreach ($dataFiles as $file => $defaultContent) {
        if (!file_exists($file)) {
            file_put_contents($file, json_encode($defaultContent, JSON_PRETTY_PRINT));
            chmod($file, 0600);
        }
    }
}

/**
 * Get all admin users
 */
function getAdminUsers() {
    if (!file_exists(ADMIN_USERS_FILE)) {
        initializeAdminData();
    }
    
    $users = json_decode(file_get_contents(ADMIN_USERS_FILE), true);
    return $users ?: [];
}

/**
 * Save admin users
 */
function saveAdminUsers($users) {
    file_put_contents(ADMIN_USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
}

/**
 * Find admin user by username or email
 */
function findAdminUser($username) {
    $users = getAdminUsers();
    
    foreach ($users as $user) {
        if ($user['username'] === $username || $user['email'] === $username) {
            return $user;
        }
    }
    
    return null;
}

/**
 * Update admin user
 */
function updateAdminUser($userId, $updates) {
    $users = getAdminUsers();
    
    foreach ($users as &$user) {
        if ($user['id'] == $userId) {
            $user = array_merge($user, $updates);
            saveAdminUsers($users);
            return true;
        }
    }
    
    return false;
}

/**
 * Check if user is locked due to failed attempts
 */
function isUserLocked($user) {
    if (!$user['locked_until']) {
        return false;
    }
    
    $lockedUntil = strtotime($user['locked_until']);
    if ($lockedUntil > time()) {
        return true;
    }
    
    // Unlock user if lock period has expired
    updateAdminUser($user['id'], [
        'failed_attempts' => 0,
        'locked_until' => null
    ]);
    
    return false;
}

/**
 * Log admin activity
 */
function logAdminActivity($action, $adminId = null, $targetType = null, $targetId = null, $details = null) {
    if (!LOG_ADMIN_ACTIONS) {
        return;
    }
    
    $logs = json_decode(file_get_contents(ADMIN_LOGS_FILE), true) ?: [];
    
    $log = [
        'id' => count($logs) + 1,
        'admin_id' => $adminId,
        'action' => $action,
        'target_type' => $targetType,
        'target_id' => $targetId,
        'details' => $details,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'created_at' => date(DATE_FORMAT)
    ];
    
    $logs[] = $log;
    
    // Keep only recent logs based on retention policy
    $cutoffDate = date(DATE_FORMAT, strtotime('-' . LOG_RETENTION_DAYS . ' days'));
    $logs = array_filter($logs, function($log) use ($cutoffDate) {
        return $log['created_at'] > $cutoffDate;
    });
    
    file_put_contents(ADMIN_LOGS_FILE, json_encode(array_values($logs), JSON_PRETTY_PRINT));
}

/**
 * Attempt to login
 */
function attemptLogin($username, $password, $remember = false) {
    $user = findAdminUser($username);
    
    if (!$user) {
        logAdminActivity('LOGIN_FAILED', null, 'username', $username, ['reason' => 'User not found']);
        return ['success' => false, 'error' => ERROR_MESSAGES['invalid_credentials']];
    }
    
    // Check if user is locked
    if (isUserLocked($user)) {
        logAdminActivity('LOGIN_FAILED', $user['id'], 'user', $user['username'], ['reason' => 'Account locked']);
        return ['success' => false, 'error' => ERROR_MESSAGES['account_locked']];
    }
    
    // Check if user is active
    if (!$user['is_active']) {
        logAdminActivity('LOGIN_FAILED', $user['id'], 'user', $user['username'], ['reason' => 'Account inactive']);
        return ['success' => false, 'error' => ERROR_MESSAGES['invalid_credentials']];
    }
    
    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        // Increment failed attempts
        $failedAttempts = $user['failed_attempts'] + 1;
        $updates = ['failed_attempts' => $failedAttempts];
        
        // Lock account if too many failed attempts
        if ($failedAttempts >= LOGIN_THROTTLE_ATTEMPTS) {
            $updates['locked_until'] = date(DATE_FORMAT, time() + LOGIN_THROTTLE_WINDOW);
        }
        
        updateAdminUser($user['id'], $updates);
        logAdminActivity('LOGIN_FAILED', $user['id'], 'user', $user['username'], ['reason' => 'Invalid password']);
        
        return ['success' => false, 'error' => ERROR_MESSAGES['invalid_credentials']];
    }
    
    // Successful login
    $_SESSION['admin_id'] = $user['id'];
    $_SESSION['admin_username'] = $user['username'];
    $_SESSION['admin_role'] = $user['role'];
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_login_time'] = time();
    $_SESSION['admin_last_activity'] = time();
    
    // Generate CSRF token
    $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    
    // Update user login info
    updateAdminUser($user['id'], [
        'last_login' => date(DATE_FORMAT),
        'last_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'failed_attempts' => 0,
        'locked_until' => null
    ]);
    
    // Handle remember me
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        
        // Save session to file
        $sessions = json_decode(file_get_contents(ADMIN_SESSIONS_FILE), true) ?: [];
        $sessions[] = [
            'id' => count($sessions) + 1,
            'admin_id' => $user['id'],
            'token_hash' => $tokenHash,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'expires_at' => date(DATE_FORMAT, time() + ADMIN_COOKIE_LIFETIME),
            'created_at' => date(DATE_FORMAT)
        ];
        file_put_contents(ADMIN_SESSIONS_FILE, json_encode($sessions, JSON_PRETTY_PRINT));
        
        // Set cookie
        setcookie(
            ADMIN_COOKIE_NAME,
            $user['id'] . ':' . $token,
            time() + ADMIN_COOKIE_LIFETIME,
            '/',
            '',
            ENFORCE_HTTPS,
            true
        );
    }
    
    logAdminActivity('LOGIN', $user['id'], 'user', $user['username']);
    
    return ['success' => true, 'user' => $user];
}

/**
 * Check if admin is logged in
 */
function isAdminLoggedIn() {
    // Check session
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
        // Check session timeout
        if (time() - $_SESSION['admin_last_activity'] > SESSION_LIFETIME) {
            logout();
            return false;
        }
        
        $_SESSION['admin_last_activity'] = time();
        return true;
    }
    
    // Check remember me cookie
    if (isset($_COOKIE[ADMIN_COOKIE_NAME])) {
        list($userId, $token) = explode(':', $_COOKIE[ADMIN_COOKIE_NAME], 2);
        $tokenHash = hash('sha256', $token);
        
        // Find valid session
        $sessions = json_decode(file_get_contents(ADMIN_SESSIONS_FILE), true) ?: [];
        foreach ($sessions as $session) {
            if ($session['admin_id'] == $userId && 
                $session['token_hash'] === $tokenHash &&
                strtotime($session['expires_at']) > time()) {
                
                // Restore session
                $user = null;
                $users = getAdminUsers();
                foreach ($users as $u) {
                    if ($u['id'] == $userId) {
                        $user = $u;
                        break;
                    }
                }
                
                if ($user && $user['is_active']) {
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['admin_role'] = $user['role'];
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_login_time'] = time();
                    $_SESSION['admin_last_activity'] = time();
                    $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
                    
                    return true;
                }
            }
        }
        
        // Invalid cookie, remove it
        setcookie(ADMIN_COOKIE_NAME, '', time() - 3600, '/');
    }
    
    return false;
}

/**
 * Logout admin
 */
function logout() {
    if (isset($_SESSION['admin_id'])) {
        logAdminActivity('LOGOUT', $_SESSION['admin_id']);
    }
    
    // Clear session
    $_SESSION = [];
    session_destroy();
    
    // Clear remember me cookie
    if (isset($_COOKIE[ADMIN_COOKIE_NAME])) {
        setcookie(ADMIN_COOKIE_NAME, '', time() - 3600, '/');
        
        // Remove session from file
        list($userId, $token) = explode(':', $_COOKIE[ADMIN_COOKIE_NAME], 2);
        $tokenHash = hash('sha256', $token);
        
        $sessions = json_decode(file_get_contents(ADMIN_SESSIONS_FILE), true) ?: [];
        $sessions = array_filter($sessions, function($session) use ($userId, $tokenHash) {
            return !($session['admin_id'] == $userId && $session['token_hash'] === $tokenHash);
        });
        file_put_contents(ADMIN_SESSIONS_FILE, json_encode(array_values($sessions), JSON_PRETTY_PRINT));
    }
}

/**
 * Check if admin has permission
 */
function hasPermission($permission) {
    if (!isAdminLoggedIn()) {
        return false;
    }
    
    $role = $_SESSION['admin_role'] ?? 0;
    $permissions = ROLE_PERMISSIONS[$role] ?? [];
    
    return in_array($permission, $permissions);
}

/**
 * Require admin login
 */
function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Require specific permission
 */
function requirePermission($permission) {
    requireAdmin();
    
    if (!hasPermission($permission)) {
        http_response_code(403);
        die(ERROR_MESSAGES['access_denied']);
    }
}

/**
 * Get CSRF token
 */
function getCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Generate CSRF token field
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(getCSRFToken()) . '">';
}

// Initialize admin data on first load
initializeAdminData();