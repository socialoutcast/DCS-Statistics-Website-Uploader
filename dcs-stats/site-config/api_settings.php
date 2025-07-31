<?php
/**
 * Admin API Settings Page - Configure DCSServerBot API
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin_functions.php';
require_once __DIR__ . '/../api_config_helper.php';

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

// Load current API configuration with auto-fixing
$configResult = loadApiConfigWithFix();
$apiConfig = $configResult['config'];
$configFile = $configResult['config_path'];
$autoFixMessage = '';

// Show any auto-fix messages
if (isset($configResult['fixed']) && $configResult['fixed'] && !empty($configResult['changes'])) {
    $autoFixMessage = 'Configuration auto-fixed: ' . implode(', ', $configResult['changes']);
}

// Show config location if not standard
if ($configFile !== dirname(__DIR__) . '/api_config.json') {
    $autoFixMessage .= ($autoFixMessage ? ' | ' : '') . 'Config location: ' . $configFile;
}

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
                    // Get form inputs
                    $apiHost = trim($_POST['api_host'] ?? '');
                    $timeout = intval($_POST['timeout'] ?? 30);
                    $cacheTtl = intval($_POST['cache_ttl'] ?? 300);
                    $useApi = isset($_POST['use_api']);
                    
                    // Use helper to create complete config
                    $saveResult = createApiConfigFromHost($apiHost, $configFile);
                    
                    if ($saveResult['success']) {
                        // Update with form values
                        $apiConfig = $saveResult['config'];
                        $apiConfig['timeout'] = $timeout;
                        $apiConfig['cache_ttl'] = $cacheTtl;
                        $apiConfig['use_api'] = $useApi;
                        
                        // Save again with all settings
                        if (file_put_contents($configFile, json_encode($apiConfig, JSON_PRETTY_PRINT))) {
                            logAdminActivity('API_CONFIG_CHANGE', $_SESSION['admin_id'], 'settings', 'api_config', $apiConfig);
                            $message = 'API configuration saved successfully with all endpoints enabled';
                            $messageType = 'success';
                        }
                    } else {
                        $message = $saveResult['message'];
                        $messageType = 'error';
                    }
                    break;
                    
                case 'test':
                    // Test API connection using enhanced client with auto-detection
                    require_once dirname(__DIR__) . '/api_client_enhanced.php';
                    
                    try {
                        // Create enhanced client
                        $client = createEnhancedAPIClient();
                        
                        // Try to make a simple request
                        $result = $client->request('/servers', null, 'GET');
                        
                        if ($result !== null) {
                            $protocol = $client->getDetectedProtocol();
                            $detectedUrl = $client->getApiBaseUrl();
                            
                            // Update config with detected protocol
                            $apiHost = $apiConfig['api_host'] ?? preg_replace('#^https?://#', '', $apiConfig['api_base_url']);
                            $apiConfig['api_host'] = $apiHost;
                            $apiConfig['api_base_url'] = $detectedUrl;
                            
                            // Save the configuration with detected protocol
                            $saveConfig = $apiConfig;
                            $saveConfig['api_base_url'] = $detectedUrl;
                            
                            if (@file_put_contents($configFile, json_encode($saveConfig, JSON_PRETTY_PRINT))) {
                                $testResult = [
                                    'success' => true, 
                                    'message' => "API connection successful! Detected and saved {$protocol}:// protocol.",
                                    'protocol' => $protocol
                                ];
                            } else {
                                $testResult = [
                                    'success' => true, 
                                    'message' => "API connection successful using {$protocol}://",
                                    'protocol' => $protocol
                                ];
                            }
                        } else {
                            $testResult = ['success' => false, 'message' => 'No response from API'];
                        }
                    } catch (Exception $e) {
                        $errorMsg = $e->getMessage();
                        
                        // The enhanced client should have handled protocol detection
                        // If we still get an error, it's a real connection issue
                        $testResult = [
                            'success' => false, 
                            'message' => 'Unable to connect to API: ' . $errorMsg
                        ];
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
        
        /* Critical inline CSS for layout */
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; overflow-x: hidden; }
        .admin-wrapper { display: flex; min-height: 100vh; width: 100%; overflow-x: hidden; }
        .admin-sidebar { width: 250px; flex-shrink: 0; background: #2a2a2a; }
        .admin-main { flex: 1; min-width: 0; overflow-x: hidden; }
        .admin-content { padding: 30px; max-width: 100%; overflow-x: hidden; }
        .card { max-width: 100%; overflow-x: auto; }
    </style>
</head>
<body class="admin-body">
    <div class="admin-wrapper">
        <?php include __DIR__ . '/nav.php'; ?>
        
        <main class="admin-main">
            <div class="admin-content">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>">
                        <?= e($message) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($autoFixMessage): ?>
                    <div class="alert alert-info">
                        <?= e($autoFixMessage) ?>
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
                            <label for="api_host">API Host</label>
                            <input type="text" 
                                   id="api_host" 
                                   name="api_host" 
                                   value="<?= e($apiConfig['api_host'] ?? preg_replace('#^https?://#', '', $apiConfig['api_base_url'])) ?>"
                                   placeholder="localhost:8080"
                                   pattern="[a-zA-Z0-9.-]+:[0-9]+">
                            <div class="help-text">Enter domain:port (e.g., dcs1.example.com:9876). Protocol will be auto-detected.</div>
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
                    <h4>Available Endpoints</h4>
                    <div class="endpoints-list">
                        <strong>DCSServerBot REST API endpoints:</strong><br>
                        • /servers - Get server status (GET)<br>
                        • /credits - Get player credits (POST with nick and date)<br>
                        • /stats - Get enhanced statistics (POST with nick and date)<br>
                        • /getuser - Get user statistics (POST with nick)<br>
                        • /topkills - Get top killers leaderboard (GET)<br>
                        • /topkdr - Get top K/D ratio leaderboard (GET)<br>
                        • /missilepk - Get missile probability of kill (POST with nick and date)<br>
                        • /squadrons - Get all squadrons (GET)<br>
                        • /squadron_members - Get squadron members (POST with name)<br>
                        • /squadron_credits - Get squadron credits (POST with name)<br>
                        <br>
                        <strong>Our system endpoints (PHP files):</strong><br>
                        • get_servers.php - Server status page data<br>
                        • get_leaderboard.php - Leaderboard page data<br>
                        • get_player_stats.php - Individual player statistics<br>
                        • get_pilot_credits.php - Credits leaderboard<br>
                        • get_pilot_statistics.php - Pilot details<br>
                        • get_server_statistics.php - Server statistics<br>
                        • get_active_players.php - Currently active players<br>
                        • search_players.php - Player search functionality<br>
                        • get_leaderboard_client.php - Client-side leaderboard<br>
                        • get_credits.php - Credits data<br>
                        • get_missionstats.php - Mission statistics<br>
                        • get_server_stats.php - Server performance stats<br>
                        <br>
                        <strong>Note:</strong> All features now work through the API
                    </div>
                </div>

<script>
    // Enhanced test functionality
    document.addEventListener(\'DOMContentLoaded\', function() {
        const testBtn = document.querySelector(\'button[value="test"]\');
        const apiHostInput = document.getElementById(\'api_host\');
        
        if (testBtn) {
            testBtn.addEventListener(\'click\', async function(e) {
                e.preventDefault();
                
                const apiHost = apiHostInput.value.trim();
                if (!apiHost) {
                    alert(\'Please enter an API host first\');
                    return;
                }
                
                // Show loading state
                testBtn.disabled = true;
                testBtn.textContent = \'Testing...\';
                
                try {
                    // Use our test endpoint
                    const response = await fetch(\'' . url("test_api_connection.php") . '\', {
                        method: \'POST\',
                        headers: {
                            \'Content-Type\': \'application/x-www-form-urlencoded\'
                        },
                        body: new URLSearchParams({
                            api_host: apiHost,
                            endpoint: \'/stats\'
                        })
                    });
                    
                    const result = await response.json();
                    
                    // Display results
                    let message = \'\';
                    
                    if (result.recommended_protocol) {
                        if (result.recommendation_reason && result.recommendation_reason.includes(\'SSL errors\')) {
                            message = `⚠️ SSL Error Detected!\n\n`;
                            message += `The API server is using HTTP, not HTTPS.\n`;
                            message += `Detected: ${result.recommended_protocol}://${apiHost}\n\n`;
                            message += `Click OK to save and test again with the correct protocol.`;
                            
                            if (confirm(message)) {
                                // Submit the form to save with auto-detection
                                const form = testBtn.closest(\'form\');
                                if (form) {
                                    // The server-side test will handle the SSL error and auto-configure
                                    form.submit();
                                }
                            }
                        } else {
                            message = `✅ Connection Successful!\n\n`;
                            message += `Protocol: ${result.recommended_protocol}\n`;
                            message += `API URL: ${result.recommended_url}\n`;
                            message += `Reason: ${result.recommendation_reason}`;
                            alert(message);
                        }
                    } else {
                        message = \'❌ Could not connect to API\\n\\n\';
                        message += \'Details:\\n\';
                        for (const [protocol, test] of Object.entries(result.results)) {
                            if (test.ssl_error) {
                                message += `${protocol}: SSL Error - Server is using HTTP\\n`;
                            } else {
                                message += `${protocol}: ${test.error || \'HTTP \' + test.http_code}\\n`;
                            }
                        }
                        message += \'\\nMake sure the API server is running and accessible.\';
                        alert(message);
                    }
                    
                } catch (error) {
                    alert(\'Test failed: \' + error.message);
                } finally {
                    testBtn.disabled = false;
                    testBtn.textContent = \'Test Connection\';
                }
            });
        }
    });
</script>
            </div>
        </main>
    </div>
</body>
</html>