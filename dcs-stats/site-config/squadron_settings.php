<?php
/**
 * Squadron Homepage Settings Page
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin_functions.php';
require_once dirname(__DIR__) . '/site_features.php';

// Require admin login and permission
requireAdmin();
requirePermission('manage_squadrons');

// Get current admin
$currentAdmin = getCurrentAdmin();

// Only Air Boss can change Squadron settings
if ($currentAdmin['role'] !== ROLE_AIR_BOSS) {
    header('Location: index.php?error=access_denied');
    exit();
}

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $message = ERROR_MESSAGES['csrf_invalid'];
        $messageType = 'error';
    } else {
        // Load current features
        $currentFeatures = loadSiteFeatures();
        
        // Update Squadron settings
        $currentFeatures['show_squadron_homepage'] = isset($_POST['show_squadron_homepage']);
        $currentFeatures['squadron_homepage_url'] = trim($_POST['squadron_homepage_url'] ?? '');
        $currentFeatures['squadron_homepage_text'] = trim($_POST['squadron_homepage_text'] ?? 'Squadron');
        
        // Validate inputs
        if ($currentFeatures['show_squadron_homepage']) {
            if (empty($currentFeatures['squadron_homepage_url'])) {
                $message = 'Squadron homepage URL is required when enabled';
                $messageType = 'error';
            } elseif (!filter_var($currentFeatures['squadron_homepage_url'], FILTER_VALIDATE_URL)) {
                $message = 'Please enter a valid squadron homepage URL';
                $messageType = 'error';
            }
            
            if (empty($currentFeatures['squadron_homepage_text'])) {
                $currentFeatures['squadron_homepage_text'] = 'Squadron';
            } elseif (strlen($currentFeatures['squadron_homepage_text']) > 50) {
                $message = 'Link text must be 50 characters or less';
                $messageType = 'error';
            }
        }
        
        if (empty($message)) {
            // Save settings
            if (saveSiteFeatures($currentFeatures)) {
                logAdminActivity('SETTINGS_CHANGE', $_SESSION['admin_id'], 'settings', 'squadron_homepage', $currentFeatures);
                $message = 'Squadron homepage settings saved successfully';
                $messageType = 'success';
            } else {
                $message = 'Failed to save settings';
                $messageType = 'error';
            }
        }
    }
}

// Load current settings
$currentFeatures = loadSiteFeatures();

// Page title
$pageTitle = 'Squadron Homepage Settings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Carrier Air Wing Command</title>
    <link rel="stylesheet" href="css/admin.css">
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
                
                <!-- Squadron Settings Form -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Squadron Homepage Configuration</h2>
                        <p class="text-muted">Add a custom link to your squadron's website or homepage in the main navigation</p>
                    </div>
                    
                    <form method="POST" action="">
                        <?= csrfField() ?>
                        
                        <div class="form-group">
                            <div class="setting-item">
                                <input type="checkbox" 
                                       id="show_squadron_homepage" 
                                       name="show_squadron_homepage" 
                                       value="1"
                                       <?= ($currentFeatures['show_squadron_homepage'] ?? false) ? 'checked' : '' ?>>
                                <label for="show_squadron_homepage">Enable Squadron Homepage Link</label>
                            </div>
                            <small class="text-muted">Show a custom squadron link in the main site navigation (appears between Discord and Servers)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="squadron_homepage_url">Squadron Homepage URL</label>
                            <input type="url" 
                                   id="squadron_homepage_url" 
                                   name="squadron_homepage_url" 
                                   class="form-control" 
                                   value="<?= e($currentFeatures['squadron_homepage_url'] ?? '') ?>"
                                   placeholder="https://your-squadron-website.com">
                            <small class="text-muted">The URL to your squadron's website, forum, or homepage</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="squadron_homepage_text">Link Text</label>
                            <input type="text" 
                                   id="squadron_homepage_text" 
                                   name="squadron_homepage_text" 
                                   class="form-control" 
                                   value="<?= e($currentFeatures['squadron_homepage_text'] ?? 'Squadron') ?>"
                                   placeholder="Squadron"
                                   maxlength="50">
                            <small class="text-muted">Text displayed for the navigation link (maximum 50 characters)</small>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Save Squadron Settings</button>
                            <a href="settings.php" class="btn btn-secondary">Back to Site Features</a>
                        </div>
                    </form>
                </div>
                
                <!-- Preview Section -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Navigation Preview</h3>
                    </div>
                    
                    <p class="text-muted">Here's how your navigation menu will look with these settings:</p>
                    
                    <div style="background: #2a2a2a; padding: 15px; border-radius: 5px; margin: 15px 0;">
                        <nav style="display: flex; gap: 20px; flex-wrap: wrap;">
                            <span style="color: #4CAF50;">Home</span>
                            <span style="color: #4CAF50;">Leaderboard</span>
                            <span style="color: #4CAF50;">Pilot Statistics</span>
                            <?php if (getFeatureValue('show_discord_link', true)): ?>
                            <span style="color: #4CAF50;">Discord</span>
                            <?php endif; ?>
                            <?php if ($currentFeatures['show_squadron_homepage'] ?? false): ?>
                            <span style="color: #4CAF50; font-weight: bold;">
                                <?= e($currentFeatures['squadron_homepage_text'] ?? 'Squadron') ?>
                            </span>
                            <?php else: ?>
                            <span style="color: #666; font-style: italic;">[Squadron Link - Disabled]</span>
                            <?php endif; ?>
                            <span style="color: #4CAF50;">Servers</span>
                        </nav>
                    </div>
                </div>
                
                <!-- Help Section -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Squadron Homepage Ideas</h3>
                    </div>
                    
                    <div class="help-content">
                        <h4>Common Squadron Links</h4>
                        <ul>
                            <li><strong>Official Website:</strong> Your squadron's main website</li>
                            <li><strong>Forum:</strong> Discussion board or community forum</li>
                            <li><strong>Training Portal:</strong> Training schedules and materials</li>
                            <li><strong>Operations Board:</strong> Mission briefings and schedules</li>
                            <li><strong>Squadron Tools:</strong> Custom applications or utilities</li>
                        </ul>
                        
                        <h4>Link Text Suggestions</h4>
                        <ul>
                            <li>"Squadron" (generic)</li>
                            <li>"VFA-103" (squadron designation)</li>
                            <li>"Jolly Rogers" (squadron name)</li>
                            <li>"Operations" (for ops boards)</li>
                            <li>"Training" (for training sites)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>