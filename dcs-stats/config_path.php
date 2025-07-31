<?php
/**
 * Path Configuration
 * Automatically detects the base path for portable installations
 */

// Get the base URL path dynamically
function getBasePath() {
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $scriptPath = dirname($scriptName);
    
    // Normalize the path
    if ($scriptPath === '/' || $scriptPath === '\\') {
        return '';
    }
    
    // Ensure it doesn't end with a slash
    return rtrim($scriptPath, '/\\');
}

// Get the full base URL including protocol and domain
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $basePath = getBasePath();
    
    return $protocol . $host . $basePath;
}

// Define constants if not already defined
if (!defined('BASE_PATH')) {
    define('BASE_PATH', getBasePath());
}

if (!defined('BASE_URL')) {
    define('BASE_URL', getBaseUrl());
}

// Helper function to create proper URLs
function url($path = '') {
    if (empty($path)) {
        return BASE_PATH;
    }
    
    // Remove leading slash from path
    $path = ltrim($path, '/');
    
    // If BASE_PATH is empty (root installation), just return with leading slash
    if (empty(BASE_PATH)) {
        return '/' . $path;
    }
    
    return BASE_PATH . '/' . $path;
}

// Helper function for absolute URLs
function absoluteUrl($path = '') {
    if (empty($path)) {
        return BASE_URL;
    }
    
    // Remove leading slash from path
    $path = ltrim($path, '/');
    
    return BASE_URL . '/' . $path;
}

// JavaScript configuration generator
function getJsConfig() {
    return json_encode([
        'basePath' => BASE_PATH,
        'baseUrl' => BASE_URL
    ]);
}
?>