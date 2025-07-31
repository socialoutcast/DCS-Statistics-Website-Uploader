<?php
/**
 * Admin Panel Installation Script
 * Run this script to set up the admin panel for first use
 */

// Check if already configured
$dataDir = __DIR__ . '/data';
$usersFile = $dataDir . '/users.json';

if (file_exists($usersFile)) {
    die("Admin panel appears to be already installed. Delete admin/data/users.json to reinstall.\n");
}

echo "DCS Statistics Admin Panel Installer\n";
echo "====================================\n\n";

// Check PHP version
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die("Error: PHP 7.4 or higher is required. You have " . PHP_VERSION . "\n");
}

// Check required extensions
$required_extensions = ['json', 'session', 'openssl', 'mbstring'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}

if (!empty($missing_extensions)) {
    die("Error: Missing required PHP extensions: " . implode(', ', $missing_extensions) . "\n");
}

echo "✓ PHP version and extensions OK\n";

// Create data directory
if (!is_dir($dataDir)) {
    if (!mkdir($dataDir, 0700, true)) {
        die("Error: Could not create data directory. Please create it manually with permissions 700.\n");
    }
}

echo "✓ Data directory created\n";

// Check if running from CLI or web
$is_cli = (php_sapi_name() === 'cli');

if ($is_cli) {
    // CLI installation
    echo "\nSetting up default admin user...\n";
    echo "Username [admin]: ";
    $username = trim(fgets(STDIN)) ?: 'admin';
    
    echo "Email: ";
    $email = trim(fgets(STDIN));
    while (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email. Please enter a valid email: ";
        $email = trim(fgets(STDIN));
    }
    
    echo "Password: ";
    system('stty -echo');
    $password = trim(fgets(STDIN));
    system('stty echo');
    echo "\n";
    
    while (strlen($password) < 8) {
        echo "Password must be at least 8 characters. Try again: ";
        system('stty -echo');
        $password = trim(fgets(STDIN));
        system('stty echo');
        echo "\n";
    }
} else {
    // Web installation - use defaults
    $username = 'admin';
    $email = 'admin@example.com';
    $password = 'changeme123';
    
    echo "<pre>";
    echo "\nUsing default credentials:\n";
    echo "Username: admin\n";
    echo "Email: admin@example.com\n";
    echo "Password: changeme123\n";
    echo "\n<strong>IMPORTANT: Change these immediately after first login!</strong>\n";
    echo "</pre>";
}

// Create initial admin user
$admin = [
    'id' => 1,
    'username' => $username,
    'email' => $email,
    'password_hash' => password_hash($password, PASSWORD_BCRYPT),
    'role' => 3, // Super Admin
    'created_at' => date('Y-m-d H:i:s'),
    'last_login' => null,
    'is_active' => true,
    'failed_attempts' => 0,
    'locked_until' => null
];

// Create data files
$files = [
    'users.json' => [$admin],
    'logs.json' => [],
    'bans.json' => [],
    'sessions.json' => []
];

foreach ($files as $filename => $content) {
    $filepath = $dataDir . '/' . $filename;
    if (file_put_contents($filepath, json_encode($content, JSON_PRETTY_PRINT)) === false) {
        die("Error: Could not create $filename\n");
    }
    chmod($filepath, 0600);
}

echo "✓ Data files created\n";

// Create .htaccess if not exists
$htaccess = $dataDir . '/.htaccess';
if (!file_exists($htaccess)) {
    file_put_contents($htaccess, "Order deny,allow\nDeny from all");
}

echo "✓ Security files created\n";

// Test write permissions
$testFile = $dataDir . '/test.tmp';
if (file_put_contents($testFile, 'test') === false) {
    die("\nError: Data directory is not writable. Please check permissions.\n");
}
unlink($testFile);

echo "✓ Write permissions OK\n";

echo "\n";
echo "========================================\n";
echo "Installation completed successfully!\n";
echo "========================================\n\n";

if ($is_cli) {
    echo "You can now access the admin panel at:\n";
    echo "https://yoursite.com/dcs-stats/site-config/\n\n";
    echo "Login with:\n";
    echo "Username: $username\n";
    echo "Password: [the password you entered]\n";
} else {
    echo "You can now <a href=\"login.php\">login to the admin panel</a>.\n";
}

echo "\nNext steps:\n";
echo "1. Login to the admin panel\n";
echo "2. Change your password immediately\n";
echo "3. Create additional admin users as needed\n";
echo "4. Configure your settings\n";
echo "\nFor security, delete or rename this install.php file.\n";