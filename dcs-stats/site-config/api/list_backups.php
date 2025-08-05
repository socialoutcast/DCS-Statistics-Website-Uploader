<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../admin_functions.php';

requireAdmin();
requirePermission('manage_updates');

header('Content-Type: application/json');

$rootPath = dirname(__DIR__, 2);
$backupDir = $rootPath . '/backups';

$backups = [];

if (is_dir($backupDir)) {
    $files = glob($backupDir . '/backup-*.zip');
    foreach ($files as $file) {
        $filename = basename($file);
        
        // Extract version and branch from filename if available
        $version = 'Unknown';
        $branch = 'Unknown';
        
        // Try new format: backup-YYYYMMDD-HHMMSS-branch-version.zip
        if (preg_match('/backup-\d{8}-\d{6}-([^-]+)-(.+)\.zip/', $filename, $matches)) {
            $branch = $matches[1];
            $version = str_replace('_', '.', $matches[2]);
        }
        // Or check metadata inside zip
        else {
            $zip = new ZipArchive();
            if ($zip->open($file) === TRUE) {
                $metaIndex = $zip->locateName('.backup_meta.json');
                if ($metaIndex !== false) {
                    $metaContent = $zip->getFromIndex($metaIndex);
                    $meta = json_decode($metaContent, true);
                    $version = $meta['version'] ?? 'Unknown';
                    $branch = $meta['branch'] ?? 'Unknown';
                }
                $zip->close();
            }
        }
        
        $backups[] = [
            'name' => $filename,
            'date' => date('Y-m-d H:i:s', filemtime($file)),
            'size' => formatBytes(filesize($file)),
            'version' => $version,
            'branch' => $branch
        ];
    }
    
    // Sort by date descending
    usort($backups, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
}

echo json_encode(['backups' => $backups]);

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}