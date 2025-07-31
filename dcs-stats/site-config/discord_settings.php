<?php
/**
 * Discord Link Settings Page
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin_functions.php';
require_once dirname(__DIR__) . '/site_features.php';

// Require admin login and permission
requireAdmin();
requirePermission('change_settings');

// Get current admin
$currentAdmin = getCurrentAdmin();

// Only Air Boss can change Discord settings
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
        
        // Update Discord settings
        $currentFeatures['discord_link_url'] = trim($_POST['discord_link_url'] ?? '');
        $currentFeatures['show_discord_link'] = isset($_POST['show_discord_link']);
        
        // Validate URL
        if ($currentFeatures['show_discord_link'] && !empty($currentFeatures['discord_link_url'])) {
            if (!filter_var($currentFeatures['discord_link_url'], FILTER_VALIDATE_URL)) {
                $message = 'Please enter a valid Discord invite URL';
                $messageType = 'error';
            }
        }
        
        if (empty($message)) {
            // Save settings
            if (saveSiteFeatures($currentFeatures)) {
                logAdminActivity('SETTINGS_CHANGE', $_SESSION['admin_id'], 'settings', 'discord_link', $currentFeatures);
                $message = 'Discord settings saved successfully';
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
$pageTitle = 'Discord Link Settings';
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
                
                <!-- Discord Settings Form -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Discord Link Configuration</h2>
                    </div>
                    
                    <form method="POST" action="">
                        <?= csrfField() ?>
                        
                        <div class="form-group">
                            <div class="setting-item">
                                <input type="checkbox" 
                                       id="show_discord_link" 
                                       name="show_discord_link" 
                                       value="1"
                                       <?= ($currentFeatures['show_discord_link'] ?? false) ? 'checked' : '' ?>>
                                <label for="show_discord_link">Show Discord Link in Navigation</label>
                            </div>
                            <small class="text-muted">Enable or disable the Discord link in the main site navigation</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="discord_link_url">Discord Invite URL</label>
                            <input type="url" 
                                   id="discord_link_url" 
                                   name="discord_link_url" 
                                   class="form-control" 
                                   value="<?= e($currentFeatures['discord_link_url'] ?? 'https://discord.gg/DNENf6pUNX') ?>"
                                   placeholder="https://discord.gg/YourInvite"
                                   required>
                            <small class="text-muted">
                                The Discord invite link that users will click on. 
                                <strong>Tip:</strong> Use a permanent invite link that doesn't expire.
                            </small>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Save Discord Settings</button>
                            <a href="settings.php" class="btn btn-secondary">Back to Site Features</a>
                        </div>
                    </form>
                </div>
                
                <!-- Help Section -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Discord Integration Help</h3>
                    </div>
                    
                    <div class="help-content">
                        <h4>Creating a Discord Invite Link</h4>
                        <ol>
                            <li>Open your Discord server</li>
                            <li>Right-click on a channel (preferably general or welcome)</li>
                            <li>Select "Invite People"</li>
                            <li>Click "Edit invite link"</li>
                            <li>Set expiration to "Never" and max uses to "No limit"</li>
                            <li>Copy the invite link and paste it above</li>
                        </ol>
                        
                        <h4>Best Practices</h4>
                        <ul>
                            <li>Use a permanent invite that doesn't expire</li>
                            <li>Set the invite to point to a welcome or general channel</li>
                            <li>Test the link regularly to ensure it still works</li>
                            <li>Consider using Discord's vanity URL if your server has one</li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>