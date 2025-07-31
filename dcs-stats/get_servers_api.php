<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

// Include security functions
require_once __DIR__ . '/security_functions.php';

// Rate limiting
if (!checkRateLimit(60, 60)) {
    exit;
}

// The DCSServerBot REST API doesn't provide server/instance data
// Return empty array for now
echo json_encode([
    'error' => 'Server data not available through API',
    'servers' => [],
    'source' => 'api',
    'message' => 'The DCSServerBot REST API does not currently provide server/instance data'
]);