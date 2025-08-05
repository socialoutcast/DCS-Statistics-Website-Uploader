<?php
/**
 * Development Mode Detection
 * Centralized function to check if the site is running in development mode
 */

function isDevMode() {
    static $devMode = null;
    
    // Cache the result so we don't check multiple times
    if ($devMode !== null) {
        return $devMode;
    }
    
    $devMode = false;
    
    // Get the site root directory (dcs-stats)
    $rootPath = __DIR__;
    
    // 1. Check for .dev file in site root
    if (file_exists($rootPath . '/.dev')) {
        $devMode = true;
        return $devMode;
    }
    
    // 2. Check for .dev file in parent directory (when called from subdirectories)
    $parentPath = dirname($rootPath);
    if (basename($parentPath) !== 'dcs-stats' && file_exists($parentPath . '/.dev')) {
        $devMode = true;
        return $devMode;
    }
    
    // 3. Walk up directory tree to find dcs-stats root
    $checkPath = $rootPath;
    for ($i = 0; $i < 5; $i++) { // Limit depth to prevent infinite loops
        if (basename($checkPath) === 'dcs-stats' && file_exists($checkPath . '/.dev')) {
            $devMode = true;
            return $devMode;
        }
        $checkPath = dirname($checkPath);
        if ($checkPath === '/' || $checkPath === '.') {
            break;
        }
    }
    
    // 4. Optional: Check environment variables (kept for backward compatibility)
    // But the .dev file is the preferred method
    if (getenv('DEV_BRANCH') === 'true') {
        $devMode = true;
        return $devMode;
    }
    
    return $devMode;
}

/**
 * Get dev mode indicator HTML
 */
function getDevModeIndicator() {
    if (!isDevMode()) {
        return '';
    }
    
    return '<div style="background: #ff9800; color: #000; padding: 5px 10px; text-align: center; font-size: 12px; position: fixed; bottom: 0; left: 0; right: 0; z-index: 9999;">
        ⚠️ DEVELOPMENT MODE - API validation disabled
    </div>';
}