<?php
/**
 * Get Squadrons API Implementation
 * Fetches all squadrons from DCSServerBot API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/api_client_enhanced.php';

// Initialize response
$response = ['data' => [], 'error' => null];

try {
    // Create API client
    $client = createEnhancedAPIClient();
    
    // Call the squadrons endpoint (GET request)
    $squadrons = $client->request('/squadrons', null, 'GET');
    
    if ($squadrons) {
        $response['data'] = $squadrons;
    } else {
        $response['error'] = 'No squadrons data available';
    }
    
} catch (Exception $e) {
    $response['error'] = 'Failed to fetch squadrons: ' . $e->getMessage();
}

echo json_encode($response);