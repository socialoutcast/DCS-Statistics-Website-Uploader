<?php
/**
 * Admin Logout Handler
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config_path.php';

// Perform logout
logout();

// Redirect to main site index instead of login page
header('Location: ' . url('index.php'));
exit;