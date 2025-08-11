<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../admin_functions.php';
require_once dirname(__DIR__, 2) . '/dev_mode.php';

requireAdmin();
requirePermission('manage_updates');

header('Content-Type: application/json');

// Only allow in dev mode
if (!isDevMode()) {
    echo json_encode(['success' => false, 'error' => 'Not in development mode']);
    exit;
}

$response = [
    'success' => false,
    'branch' => 'unknown',
    'ahead' => 0,
    'behind' => 0,
    'modified' => 0,
    'untracked' => 0
];

// Check if we're in a git repository
$gitDir = dirname(__DIR__, 2) . '/.git';
if (!is_dir($gitDir)) {
    // Try parent directories (in case we're in a subdirectory)
    $checkDir = dirname(__DIR__, 2);
    for ($i = 0; $i < 3; $i++) {
        $checkDir = dirname($checkDir);
        if (is_dir($checkDir . '/.git')) {
            $gitDir = $checkDir . '/.git';
            chdir($checkDir);
            break;
        }
    }
    
    if (!is_dir($gitDir)) {
        echo json_encode($response);
        exit;
    }
} else {
    chdir(dirname(__DIR__, 2));
}

// Get current branch
$branch = trim(shell_exec('git rev-parse --abbrev-ref HEAD 2>/dev/null'));
if ($branch) {
    $response['branch'] = $branch;
    $response['success'] = true;
    
    // Get ahead/behind counts
    $upstream = trim(shell_exec("git rev-parse --abbrev-ref --symbolic-full-name @{u} 2>/dev/null"));
    if ($upstream) {
        $counts = trim(shell_exec("git rev-list --left-right --count HEAD...$upstream 2>/dev/null"));
        if ($counts) {
            list($ahead, $behind) = explode("\t", $counts);
            $response['ahead'] = (int)$ahead;
            $response['behind'] = (int)$behind;
        }
    }
    
    // Get modified files count
    $modified = trim(shell_exec('git diff --name-only 2>/dev/null'));
    if ($modified) {
        $response['modified'] = count(array_filter(explode("\n", $modified)));
    }
    
    // Get staged files count
    $staged = trim(shell_exec('git diff --cached --name-only 2>/dev/null'));
    if ($staged) {
        $response['staged'] = count(array_filter(explode("\n", $staged)));
    }
    
    // Get untracked files count
    $untracked = trim(shell_exec('git ls-files --others --exclude-standard 2>/dev/null'));
    if ($untracked) {
        $response['untracked'] = count(array_filter(explode("\n", $untracked)));
    }
    
    // Get last commit info
    $lastCommit = trim(shell_exec('git log -1 --format="%h - %s (%cr)" 2>/dev/null'));
    if ($lastCommit) {
        $response['last_commit'] = $lastCommit;
    }
}

echo json_encode($response);