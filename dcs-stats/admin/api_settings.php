<?php
/**
 * Admin API Settings Page - Configure DCSServerBot API
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin_functions.php';

// Require admin login and permission
requireAdmin();
requirePermission('change_settings');

// Get current admin
$currentAdmin = getCurrentAdmin();

// Only Air Boss can change API settings
if ($currentAdmin['role'] !== ROLE_AIR_BOSS) {
    header('Location: index.php?error=access_denied');
    exit();
}

// Load current API configuration
$configFile = dirname(__DIR__) . '/api_config.json';
$apiConfig = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [
    'api_base_url' => '',
    'timeout' => 30,
    'cache_ttl' => 300,
    'use_api' => false,
    'enabled_endpoints' => [],
    'endpoints' => [
        'getuser' => '/getuser',
        'stats' => '/stats',
        'topkills' => '/topkills',
        'topkdr' => '/topkdr',
        'missilepk' => '/missilepk'
    ]
];

// Handle form submission
$message = '';
$messageType = '';
$testResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $message = ERROR_MESSAGES['csrf_invalid'];
        $messageType = 'error';
    } else {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'save':
                    // Update API configuration
                    $apiConfig['api_base_url'] = trim($_POST['api_base_url'] ?? '');
                    $apiConfig['timeout'] = intval($_POST['timeout'] ?? 30);
                    $apiConfig['cache_ttl'] = intval($_POST['cache_ttl'] ?? 300);
                    $apiConfig['use_api'] = isset($_POST['use_api']);
                    
                    // Save configuration
                    if (file_put_contents($configFile, json_encode($apiConfig, JSON_PRETTY_PRINT))) {
                        logAdminActivity('API_CONFIG_CHANGE', $_SESSION['admin_id'], 'settings', 'api_config', $apiConfig);
                        $message = 'API configuration saved successfully';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to save API configuration';
                        $messageType = 'error';
                    }
                    break;
                    
                case 'test':
                    // Test API connection
                    if (!empty($apiConfig['api_base_url'])) {
                        $testUrl = rtrim($apiConfig['api_base_url'], '/') . '/stats';
                        $ch = curl_init($testUrl);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                        
                        
                        $response = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        $error = curl_error($ch);
                        curl_close($ch);
                        
                        if ($error) {
                            $testResult = ['success' => false, 'message' => 'Connection error: ' . $error];
                        } elseif ($httpCode === 200) {
                            $data = json_decode($response, true);
                            if ($data !== null) {
                                $testResult = ['success' => true, 'message' => 'API connection successful!'];
                            } else {
                                $testResult = ['success' => false, 'message' => 'Invalid JSON response from API'];
                            }
                        } else {
                            $testResult = ['success' => false, 'message' => 'HTTP error: ' . $httpCode];
                        }
                    } else {
                        $testResult = ['success' => false, 'message' => 'Please enter an API URL first'];
                    }
                    break;
            }
        }
    }
}

// Page title
$pageTitle = 'API Settings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Carrier Air Wing Command</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .api-form {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--accent-primary);
            font-weight: bold;
        }
        
        .form-group input[type="text"],
        .form-group input[type="number"] {
            width: 100%;
            padding: 10px;
            background-color: var(--bg-tertiary);
            border: 1px solid var(--border-primary);
            border-radius: 4px;
            color: var(--text-primary);
            font-size: 14px;
        }
        
        .form-group input[type="text"]:focus,
        .form-group input[type="number"]:focus {
            border-color: var(--accent-primary);
            outline: none;
        }
        
        .form-group .help-text {
            margin-top: 5px;
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
            width: 18px;
            height: 18px;
        }
        
        .test-section {
            background-color: var(--bg-tertiary);
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }
        
        .test-result {
            margin-top: 15px;
            padding: 15px;
            border-radius: 4px;
        }
        
        .test-result.success {
            background-color: rgba(76, 175, 80, 0.2);
            border: 1px solid var(--accent-success);
            color: var(--accent-success);
        }
        
        .test-result.error {
            background-color: rgba(244, 67, 54, 0.2);
            border: 1px solid var(--accent-danger);
            color: var(--accent-danger);
        }
        
        .endpoints-info {
            background-color: var(--bg-tertiary);
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .endpoints-info h4 {
            margin-bottom: 15px;
            color: var(--accent-primary);
        }
        
        .endpoints-list {
            font-family: monospace;
            font-size: 13px;
            line-height: 1.8;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'nav.php'; ?>
        
        <!-- Main Content -->
        <main class="admin-main">
            <!-- Header -->
            <header class="admin-header">
                <h1><?= $pageTitle ?></h1>
                <div class="admin-user-menu">
                    <div class="admin-user-info">
                        <div class="admin-username"><?= e($currentAdmin['username']) ?></div>
                        <div class="admin-role"><?= getRoleBadge($currentAdmin['role']) ?></div>
                    </div>
                    <a href="logout.php" class="btn btn-secondary btn-small">Logout</a>
                </div>
            </header>
            
            <!-- Content -->
            <div class="admin-content">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>">
                        <?= e($message) ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">DCSServerBot API Configuration</h2>
                    </div>
                    
                    <form method="POST" action="" class="api-form">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="save">
                        
                        <div class="form-group">
                            <label for="api_base_url">API Base URL</label>
                            <input type="text" 
                                   id="api_base_url" 
                                   name="api_base_url" 
                                   value="<?= e($apiConfig['api_base_url']) ?>"
                                   placeholder="http://localhost:8080">
                            <div class="help-text">The base URL of your DCSServerBot REST API (without /api suffix)</div>
                        </div>
                        
                        
                        <div class="form-group">
                            <label for="timeout">Request Timeout (seconds)</label>
                            <input type="number" 
                                   id="timeout" 
                                   name="timeout" 
                                   value="<?= $apiConfig['timeout'] ?>"
                                   min="5" 
                                   max="300">
                            <div class="help-text">Maximum time to wait for API responses</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="cache_ttl">Cache TTL (seconds)</label>
                            <input type="number" 
                                   id="cache_ttl" 
                                   name="cache_ttl" 
                                   value="<?= $apiConfig['cache_ttl'] ?>"
                                   min="0" 
                                   max="3600">
                            <div class="help-text">How long to cache API responses (0 to disable caching)</div>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" 
                                   id="use_api" 
                                   name="use_api" 
                                   value="1"
                                   <?= $apiConfig['use_api'] ? 'checked' : '' ?>>
                            <label for="use_api">Enable API Integration</label>
                        </div>
                        
                        
                        <div class="button-group">
                            <button type="submit" class="btn btn-primary">Save Configuration</button>
                            <button type="submit" class="btn btn-secondary" name="action" value="test">Test Connection</button>
                        </div>
                    </form>
                </div>
                
                <?php if ($testResult): ?>
                    <div class="test-section">
                        <h3>Connection Test Result</h3>
                        <div class="test-result <?= $testResult['success'] ? 'success' : 'error' ?>">
                            <?= e($testResult['message']) ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="endpoints-info">
                    <h4>Available API Endpoints</h4>
                    <div class="endpoints-list">
                        <strong>DCSServerBot REST API provides these endpoints:</strong><br>
                        • /getuser - Get user statistics<br>
                        • /stats - Get general statistics<br>
                        • /topkills - Get top killers leaderboard<br>
                        • /topkdr - Get top K/D ratio leaderboard<br>
                        • /missilepk - Get missile performance statistics<br>
                        <br>
                        <strong>Note:</strong> The DCSServerBot API has limited endpoints. Some features may require JSON file exports for full functionality.
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>