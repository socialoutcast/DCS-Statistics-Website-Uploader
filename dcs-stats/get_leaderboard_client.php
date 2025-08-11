<?php
header('Content-Type: application/json');

// Load configuration
$configFile = __DIR__ . '/api_config.json';
$config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];

// Return configuration for client-side API calls
echo json_encode([
    'api_base_url' => $config['api_base_url'] ?? 'http://localhost:8080',
    'use_api' => $config['use_api'] ?? false,
    'timeout' => $config['timeout'] ?? 30
]);