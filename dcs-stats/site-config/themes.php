<?php
/**
 * Themes Management Page
 * Allows super admins to customize site appearance
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin_functions.php';

// Require admin login and permission
requireAdmin();
requirePermission('change_settings');

// Get current admin to check specific permissions
$currentAdmin = getCurrentAdmin();
$isAirBoss = ($currentAdmin['role'] === ROLE_AIR_BOSS);

$message = '';
$error = '';

// Handle theme actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid request token';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'upload_css':
                // Only Air Boss can upload CSS
                if (!$isAirBoss) {
                    $error = 'Only Air Boss can upload custom CSS files';
                    break;
                }
                // Handle CSS file upload
                if (isset($_FILES['css_file']) && $_FILES['css_file']['error'] === UPLOAD_ERR_OK) {
                    $uploadedFile = $_FILES['css_file'];
                    $fileName = $uploadedFile['name'];
                    $fileTmp = $uploadedFile['tmp_name'];
                    $fileSize = $uploadedFile['size'];
                    
                    // Validate file type
                    $allowedTypes = ['text/css', 'text/plain'];
                    $fileType = mime_content_type($fileTmp);
                    
                    if (!in_array($fileType, $allowedTypes) || !preg_match('/\.css$/i', $fileName)) {
                        $error = 'Please upload a valid CSS file';
                    } elseif ($fileSize > 1048576) { // 1MB limit
                        $error = 'CSS file size must be less than 1MB';
                    } else {
                        // Backup current CSS
                        $currentCSS = __DIR__ . '/../styles.css';
                        $backupDir = __DIR__ . '/theme_backups';
                        
                        if (!is_dir($backupDir)) {
                            mkdir($backupDir, 0755, true);
                        }
                        
                        $backupFile = $backupDir . '/styles_' . date('Y-m-d_H-i-s') . '.css';
                        copy($currentCSS, $backupFile);
                        
                        // Upload new CSS
                        if (move_uploaded_file($fileTmp, $currentCSS)) {
                            $message = 'CSS file uploaded successfully. Previous version backed up.';
                            logActivity('THEME_UPLOAD', 'Uploaded new CSS file: ' . $fileName);
                        } else {
                            $error = 'Failed to upload CSS file';
                        }
                    }
                } else {
                    $error = 'Please select a CSS file to upload';
                }
                break;
                
            case 'update_colors':
                // Handle color updates
                $colors = [
                    'primary_color' => $_POST['primary_color'] ?? '',
                    'secondary_color' => $_POST['secondary_color'] ?? '',
                    'background_color' => $_POST['background_color'] ?? '',
                    'text_color' => $_POST['text_color'] ?? '',
                    'link_color' => $_POST['link_color'] ?? '',
                    'border_color' => $_POST['border_color'] ?? ''
                ];
                
                // Generate CSS variables
                $cssVars = ":root {\n";
                foreach ($colors as $key => $value) {
                    if (preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
                        $cssVars .= "    --{$key}: {$value};\n";
                    }
                }
                $cssVars .= "}\n\n";
                
                // Save to custom theme file
                $customCSS = __DIR__ . '/../custom_theme.css';
                file_put_contents($customCSS, $cssVars);
                
                $message = 'Color theme updated successfully';
                logActivity('THEME_COLORS', 'Updated theme colors');
                break;
                
            case 'restore_backup':
                // Only Air Boss can restore backups
                if (!$isAirBoss) {
                    $error = 'Only Air Boss can restore theme backups';
                    break;
                }
                // Restore from backup
                $backupFile = $_POST['backup_file'] ?? '';
                $backupPath = __DIR__ . '/theme_backups/' . basename($backupFile);
                
                if (file_exists($backupPath)) {
                    $currentCSS = __DIR__ . '/../styles.css';
                    copy($backupPath, $currentCSS);
                    $message = 'Theme restored from backup';
                    logActivity('THEME_RESTORE', 'Restored theme from: ' . $backupFile);
                } else {
                    $error = 'Backup file not found';
                }
                break;
        }
    }
}

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Get list of backup files
$backupDir = __DIR__ . '/theme_backups';
$backups = [];
if (is_dir($backupDir)) {
    $files = scandir($backupDir);
    foreach ($files as $file) {
        if (preg_match('/^styles_.*\.css$/', $file)) {
            $backups[] = [
                'filename' => $file,
                'date' => filemtime($backupDir . '/' . $file),
                'size' => filesize($backupDir . '/' . $file)
            ];
        }
    }
    // Sort by date descending
    usort($backups, function($a, $b) {
        return $b['date'] - $a['date'];
    });
}

// Load current custom colors if they exist
$customColors = [
    'primary_color' => '#1a1a1a',
    'secondary_color' => '#2a2a2a',
    'background_color' => '#0f0f0f',
    'text_color' => '#e0e0e0',
    'link_color' => '#4a9eff',
    'border_color' => '#333333'
];

$customCSS = __DIR__ . '/../custom_theme.css';
if (file_exists($customCSS)) {
    $content = file_get_contents($customCSS);
    preg_match_all('/--([a-z_]+):\s*(#[0-9a-fA-F]{6});/', $content, $matches);
    if (!empty($matches[1]) && !empty($matches[2])) {
        foreach ($matches[1] as $i => $varName) {
            if (isset($customColors[$varName])) {
                $customColors[$varName] = $matches[2][$i];
            }
        }
    }
}

// Page title
$pageTitle = 'Theme Management';
define('ADMIN_PANEL', true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Carrier Air Wing Command</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .theme-section {
            background: var(--bg-secondary);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .color-inputs {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .color-input-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .color-input-group label {
            flex: 1;
        }
        
        .color-input-group input[type="color"] {
            width: 50px;
            height: 35px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            cursor: pointer;
        }
        
        .upload-section {
            margin-top: 20px;
        }
        
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }
        
        .file-input-wrapper input[type="file"] {
            position: absolute;
            left: -9999px;
        }
        
        .file-input-button {
            display: inline-block;
            padding: 10px 20px;
            background: var(--bg-tertiary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .file-input-button:hover {
            background: var(--bg-primary);
        }
        
        .backup-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .backup-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            margin-bottom: 5px;
            background: var(--bg-tertiary);
            border-radius: 4px;
        }
        
        .backup-info {
            flex: 1;
        }
        
        .preview-frame {
            width: 100%;
            height: 500px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background: #fff;
        }
        
        .theme-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .theme-tab {
            padding: 10px 20px;
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.3s;
        }
        
        .theme-tab.active {
            color: var(--text-primary);
            border-bottom-color: var(--accent-primary);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'nav.php'; ?>
        
        <main class="admin-main">
            <div class="admin-content">
                <div class="card">
                    <div class="card-header">
                        <h1 class="card-title"><?= $pageTitle ?></h1>
                    </div>
                <?php if ($message): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <!-- Theme Preview Section -->
                <div class="theme-section">
                    <h2>Theme Preview</h2>
                    <p>Preview how the site looks with current theme settings:</p>
                    
                    <iframe src="../index.php" class="preview-frame" id="preview-frame"></iframe>
                    
                    <div style="margin-top: 10px;">
                        <button type="button" class="btn btn-sm" onclick="refreshPreview()">Refresh Preview</button>
                    </div>
                </div>
                
                <!-- Theme Tabs -->
                <div class="theme-tabs">
                    <button class="theme-tab active" onclick="switchTab('simple')">Simple Customization</button>
                    <?php if ($isAirBoss): ?>
                    <button class="theme-tab" onclick="switchTab('advanced')">Advanced CSS Upload</button>
                    <button class="theme-tab" onclick="switchTab('backups')">Backup & Restore</button>
                    <?php endif; ?>
                </div>
                
                <!-- Simple Customization Tab -->
                <div id="simple-tab" class="tab-content active">
                    <div class="theme-section">
                        <h2>Color Customization</h2>
                        <p>Customize the main colors used throughout the site. Changes will be applied immediately.</p>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="update_colors">
                            
                            <div class="color-inputs">
                                <div class="color-input-group">
                                    <label for="primary_color">Primary Color:</label>
                                    <input type="color" id="primary_color" name="primary_color" 
                                           value="<?= htmlspecialchars($customColors['primary_color']) ?>">
                                </div>
                                
                                <div class="color-input-group">
                                    <label for="secondary_color">Secondary Color:</label>
                                    <input type="color" id="secondary_color" name="secondary_color" 
                                           value="<?= htmlspecialchars($customColors['secondary_color']) ?>">
                                </div>
                                
                                <div class="color-input-group">
                                    <label for="background_color">Background Color:</label>
                                    <input type="color" id="background_color" name="background_color" 
                                           value="<?= htmlspecialchars($customColors['background_color']) ?>">
                                </div>
                                
                                <div class="color-input-group">
                                    <label for="text_color">Text Color:</label>
                                    <input type="color" id="text_color" name="text_color" 
                                           value="<?= htmlspecialchars($customColors['text_color']) ?>">
                                </div>
                                
                                <div class="color-input-group">
                                    <label for="link_color">Link Color:</label>
                                    <input type="color" id="link_color" name="link_color" 
                                           value="<?= htmlspecialchars($customColors['link_color']) ?>">
                                </div>
                                
                                <div class="color-input-group">
                                    <label for="border_color">Border Color:</label>
                                    <input type="color" id="border_color" name="border_color" 
                                           value="<?= htmlspecialchars($customColors['border_color']) ?>">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary" style="margin-top: 20px;">Update Colors</button>
                        </form>
                    </div>
                </div>
                
                <?php if ($isAirBoss): ?>
                <!-- Advanced CSS Upload Tab -->
                <div id="advanced-tab" class="tab-content">
                    <div class="theme-section">
                        <h2>Upload Custom CSS</h2>
                        <div class="alert alert-warning">
                            <strong>Warning:</strong> Uploading a new CSS file will completely replace the current site styling. 
                            Make sure to backup the current theme first!
                        </div>
                        
                        <form method="POST" action="" enctype="multipart/form-data" class="upload-section">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="upload_css">
                            
                            <div class="file-input-wrapper">
                                <input type="file" name="css_file" id="css_file" accept=".css">
                                <label for="css_file" class="file-input-button">Choose CSS File</label>
                            </div>
                            <span id="file-name" style="margin-left: 10px;">No file selected</span>
                            
                            <div style="margin-top: 20px;">
                                <button type="submit" class="btn btn-primary">Upload CSS</button>
                            </div>
                        </form>
                        
                        <div style="margin-top: 30px;">
                            <h3>CSS Guidelines</h3>
                            <ul>
                                <li>Maximum file size: 1MB</li>
                                <li>Use CSS variables for easy color management</li>
                                <li>Test thoroughly before uploading</li>
                                <li>Ensure mobile responsiveness</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Backup & Restore Tab -->
                <div id="backups-tab" class="tab-content">
                    <div class="theme-section">
                        <h2>Theme Backups</h2>
                        
                        <?php if (empty($backups)): ?>
                            <p>No backups found.</p>
                        <?php else: ?>
                            <div class="backup-list">
                                <?php foreach ($backups as $backup): ?>
                                    <div class="backup-item">
                                        <div class="backup-info">
                                            <strong><?= htmlspecialchars($backup['filename']) ?></strong><br>
                                            <small>
                                                Created: <?= date('Y-m-d H:i:s', $backup['date']) ?> | 
                                                Size: <?= number_format($backup['size'] / 1024, 2) ?> KB
                                            </small>
                                        </div>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="action" value="restore_backup">
                                            <input type="hidden" name="backup_file" value="<?= htmlspecialchars($backup['filename']) ?>">
                                            <button type="submit" class="btn btn-sm" 
                                                    onclick="return confirm('Are you sure you want to restore this backup?')">
                                                Restore
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Tab switching
        function switchTab(tabName) {
            // Remove active class from all tabs and contents
            document.querySelectorAll('.theme-tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // Add active class to selected tab and content
            event.target.classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
        }
        
        // File input handling
        document.getElementById('css_file').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'No file selected';
            document.getElementById('file-name').textContent = fileName;
        });
        
        // Refresh preview
        function refreshPreview() {
            const iframe = document.getElementById('preview-frame');
            iframe.src = iframe.src;
        }
        
        // Live color preview (optional enhancement)
        document.querySelectorAll('input[type="color"]').forEach(input => {
            input.addEventListener('input', function() {
                // Could implement live preview here
            });
        });
    </script>
</body>
</html>