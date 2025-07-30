<?php
/**
 * Admin Logout Handler
 */

require_once __DIR__ . '/auth.php';

// Perform logout
logout();

// Redirect to main site index instead of login page
header('Location: ../index.php');
exit;