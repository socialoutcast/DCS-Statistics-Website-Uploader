<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../admin_functions.php';

requireAdmin();
requirePermission('manage_updates');

set_time_limit(0);
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');

function logMessage($msg) {
    echo $msg . "\n";
    @ob_flush();
    flush();
}

$branch = $_POST['branch'] === 'Dev' ? 'Dev' : 'main';
$backup = isset($_POST['backup']) && $_POST['backup'] === '1';

$repo = 'Penfold-88/DCS-Statistics-Dashboard';
$apiUrl = "https://api.github.com/repos/$repo/zipball/$branch";

$rootPath = dirname(__DIR__, 2); // path to dcs-stats
$upgradeDir = $rootPath . '/UPGRADE';
$backupDir = $rootPath . '/backups';

if (!is_dir($upgradeDir)) {
    mkdir($upgradeDir, 0755, true);
}

if ($backup && !is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

logMessage("Downloading $branch branch...");
$zipFile = $upgradeDir . '/update.zip';
$ch = curl_init($apiUrl);
$fp = fopen($zipFile, 'w');
curl_setopt($ch, CURLOPT_FILE, $fp);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'DCS-Stats-Updater');
$download = curl_exec($ch);
curl_close($ch);
fclose($fp);
if ($download === false) {
    logMessage('Download failed');
    exit;
}
logMessage('Download complete.');

$zip = new ZipArchive();
if ($zip->open($zipFile) !== TRUE) {
    logMessage('Failed to open zip archive');
    exit;
}
$zip->extractTo($upgradeDir);
$zip->close();
logMessage('Extraction complete.');

$extractedDirs = glob($upgradeDir . '/*', GLOB_ONLYDIR);
if (empty($extractedDirs)) {
    logMessage('No extracted directory found');
    exit;
}
$newCodeDir = $extractedDirs[0] . '/dcs-stats';
if (!is_dir($newCodeDir)) {
    logMessage('dcs-stats directory not found in archive');
    exit;
}

if ($backup) {
    $backupFile = $backupDir . '/backup-' . date('Ymd-His') . '.zip';
    logMessage('Creating backup...');
    $backupZip = new ZipArchive();
    if ($backupZip->open($backupFile, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relPath = substr($filePath, strlen($rootPath) + 1);
            if (strpos($relPath, 'backups') === 0 || strpos($relPath, 'UPGRADE') === 0) {
                continue;
            }
            if ($file->isDir()) {
                $backupZip->addEmptyDir($relPath);
            } else {
                $backupZip->addFile($filePath, $relPath);
            }
        }
        $backupZip->close();
        logMessage('Backup saved to ' . $backupFile);
    } else {
        logMessage('Failed to create backup');
    }
}

$exceptions = [
    'site-config/data',
    'backups',
    'UPGRADE'
];

$newFiles = [];
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($newCodeDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);
foreach ($files as $file) {
    $filePath = $file->getRealPath();
    $relPath = substr($filePath, strlen($newCodeDir) + 1);
    $targetPath = $rootPath . '/' . $relPath;
    $newFiles[] = $relPath;

    if ($file->isDir()) {
        if (!is_dir($targetPath)) {
            mkdir($targetPath, 0755, true);
            logMessage('Created directory: ' . $relPath);
        }
    } else {
        if (!is_dir(dirname($targetPath))) {
            mkdir(dirname($targetPath), 0755, true);
        }
        copy($filePath, $targetPath);
        logMessage('Updated file: ' . $relPath);
    }
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($rootPath, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST
);
foreach ($iterator as $file) {
    $filePath = $file->getRealPath();
    $relPath = substr($filePath, strlen($rootPath) + 1);

    foreach ($exceptions as $ex) {
        if ($relPath === $ex || strpos($relPath, $ex . '/') === 0) {
            continue 2;
        }
    }

    if (!in_array($relPath, $newFiles)) {
        if ($file->isDir()) {
            rmdir($filePath);
            logMessage('Removed directory: ' . $relPath);
        } else {
            unlink($filePath);
            logMessage('Removed file: ' . $relPath);
        }
    }
}

logMessage('Cleaning up...');
function rrmdir($dir) {
    if (!is_dir($dir)) return;
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            rrmdir($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}
rrmdir($upgradeDir);
logMessage('Update complete.');
