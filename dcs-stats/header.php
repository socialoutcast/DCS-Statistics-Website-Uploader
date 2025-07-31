<?php
// Include path configuration
require_once __DIR__ . '/config_path.php';

// Security headers for protection against common web vulnerabilities
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Build dynamic CSP based on API configuration
$cspConnectSrc = "'self'";

// Load API configuration if available
$configFile = __DIR__ . '/api_config.json';
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
    if (!empty($config['api_base_url'])) {
        // Parse the API URL to add to CSP
        $parsedUrl = parse_url($config['api_base_url']);
        if ($parsedUrl) {
            $scheme = $parsedUrl['scheme'] ?? 'http';
            $host = $parsedUrl['host'] ?? '';
            $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
            
            if ($host) {
                // Add the specific API URL
                $cspConnectSrc .= " {$scheme}://{$host}{$port}";
                
                // Also add wildcard for subdomains
                $domain = preg_replace('/^[^.]+\./', '*.', $host);
                if ($domain !== $host) {
                    $cspConnectSrc .= " {$scheme}://{$domain}:*";
                }
            }
        }
    }
}

// Always allow localhost for development
$cspConnectSrc .= " http://localhost:* https://localhost:*";

header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; connect-src {$cspConnectSrc};");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>DCS Statistics Dashboard</title>
  <link rel="stylesheet" href="<?php echo url('styles.php'); ?>" />
  <?php if (file_exists(__DIR__ . '/custom_theme.css')): ?>
  <link rel="stylesheet" href="<?php echo url('custom_theme.css'); ?>" />
  <?php endif; ?>
  <script>
    // Path configuration for JavaScript
    window.DCS_CONFIG = <?php echo getJsConfig(); ?>;
    
    // XSS Protection function
    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }
  </script>
  <script src="<?php echo url('js/api-client.js'); ?>"></script>
</head>
<body>
  <header class="main-header">
    <div class="header-background"></div>
    <div class="header-overlay"></div>
    <div class="header-container">
      <div class="header-brand">
        <div class="brand-text">
          <h1 class="site-title">DCS Statistics</h1>
          <p class="site-subtitle">Combat Data & Analytics Platform</p>
        </div>
      </div>
      <div class="header-actions">
        <div class="admin-link">
          <a href="<?php echo url('site-config/'); ?>" class="admin-button">
            <svg class="admin-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M10 12C11.6569 12 13 10.6569 13 9C13 7.34315 11.6569 6 10 6C8.34315 6 7 7.34315 7 9C7 10.6569 8.34315 12 10 12Z" stroke="currentColor" stroke-width="1.5"/>
              <path d="M17.5 9.5C17.5 9.33 17.5 9.17 17.49 9L18.87 7.91C19 7.81 19.04 7.62 18.95 7.46L17.65 5.04C17.56 4.88 17.37 4.82 17.21 4.88L15.58 5.53C15.23 5.28 14.85 5.08 14.44 4.93L14.17 3.21C14.14 3.04 13.99 2.92 13.82 2.92H11.22C11.05 2.92 10.9 3.04 10.87 3.21L10.6 4.93C10.19 5.08 9.81 5.28 9.46 5.53L7.83 4.88C7.67 4.82 7.48 4.88 7.39 5.04L6.09 7.46C6 7.62 6.04 7.81 6.17 7.91L7.55 9C7.54 9.17 7.54 9.33 7.54 9.5C7.54 9.67 7.54 9.83 7.55 10L6.17 11.09C6.04 11.19 6 11.38 6.09 11.54L7.39 13.96C7.48 14.12 7.67 14.18 7.83 14.12L9.46 13.47C9.81 13.72 10.19 13.92 10.6 14.07L10.87 15.79C10.9 15.96 11.05 16.08 11.22 16.08H13.82C13.99 16.08 14.14 15.96 14.17 15.79L14.44 14.07C14.85 13.92 15.23 13.72 15.58 13.47L17.21 14.12C17.37 14.18 17.56 14.12 17.65 13.96L18.95 11.54C19.04 11.38 19 11.19 18.87 11.09L17.49 10C17.5 9.83 17.5 9.67 17.5 9.5Z" stroke="currentColor" stroke-width="1.5"/>
            </svg>
            <span>Admin Panel</span>
          </a>
        </div>
        <div class="status-indicator">
          <span class="status-dot"></span>
          <span class="status-text">Live Data</span>
        </div>
      </div>
    </div>
  </header>
