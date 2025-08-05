<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../admin_functions.php';

requireAdmin();
requirePermission('manage_updates');

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-cache');

// Note: GitHub redirects from Website-Uploader to Dashboard
$repo = 'Penfold-88/DCS-Statistics-Dashboard';
$currentVersion = defined('ADMIN_PANEL_VERSION') ? ADMIN_PANEL_VERSION : '1.0.0';

// Get version info including branch detection
require_once dirname(__DIR__) . '/version_tracker.php';
$versionInfo = getCurrentVersionInfo();
$currentBranch = $versionInfo['branch'];

echo "Current Version: $currentVersion\n";
echo "----------------------------------------\n\n";

// Only check stable releases
echo "Checking for stable releases...\n";
$releaseUrl = "https://api.github.com/repos/$repo/releases/latest";
$ch = curl_init($releaseUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'DCS-Stats-Updater');
$releaseData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $releaseData) {
    $release = json_decode($releaseData, true);
    if (isset($release['tag_name'])) {
        echo "Latest Release: " . $release['tag_name'] . "\n";
        echo "Published: " . date('Y-m-d H:i:s', strtotime($release['published_at'])) . "\n";
        
        // Compare versions (handle V prefix)
        $current = ltrim($currentVersion, 'Vv');
        $latest = ltrim($release['tag_name'], 'Vv');
        if (version_compare($current, $latest, '<')) {
            echo "\n✅ Update Available!\n";
            echo "Release Notes:\n" . $release['body'] . "\n";
        } else {
            echo "\n✓ You are running the latest stable version.\n";
        }
    }
} else {
    echo "Could not fetch release information.\n";
}

// Check all available releases
echo "\n----------------------------------------\n";
echo "Available versions for downgrade:\n";

$releasesUrl = "https://api.github.com/repos/$repo/releases?per_page=10";
$ch = curl_init($releasesUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'DCS-Stats-Updater');
$releasesData = curl_exec($ch);
curl_close($ch);

if ($releasesData) {
    $releases = json_decode($releasesData, true);
    if (is_array($releases)) {
        foreach ($releases as $rel) {
            if (isset($rel['tag_name'])) {
                $marker = version_compare($currentVersion, $rel['tag_name'], '==') ? ' (current)' : '';
                echo "- " . $rel['tag_name'] . $marker . "\n";
            }
        }
    }
}