<?php
/**
 * Dynamic CSS with proper path handling
 */
header('Content-Type: text/css');

// Include path configuration
require_once __DIR__ . '/config_path.php';

// Read the base CSS file
$css = file_get_contents(__DIR__ . '/styles.css');

// Replace image URLs with proper paths
$css = preg_replace_callback('/url\([\'"]?([^\'")]+)[\'"]?\)/', function($matches) {
    $path = $matches[1];
    
    // Skip external URLs and data URIs
    if (preg_match('/^(https?:|data:)/', $path)) {
        return $matches[0];
    }
    
    // Build proper URL
    return 'url(\'' . url($path) . '\')';
}, $css);

echo $css;
?>