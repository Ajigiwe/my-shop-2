<?php
/**
 * AVAZONIA SECURE DEPLOYMENT UTILITY
 * Usage: Visit this file in your browser or run curl to force-sync with GitHub.
 * URL: https://yourdomain.com/deploy.php?token=your_secret_token
 */

// Load environment variables
require_once __DIR__ . '/includes/env_loader.php';

// Get secret token from environment variables (fallback to default for initial setup)
$secret_token = $_ENV['DEPLOY_SECRET'] ?? 'asodeploy123';

header('Content-Type: text/plain');

if (!isset($_GET['token']) || $_GET['token'] !== $secret_token) {
    header('HTTP/1.0 403 Forbidden');
    echo "Access Denied: Invalid security token.\n";
    exit;
}

echo "🚀 Avazonia Deployment Utility Started...\n";
echo "------------------------------------------\n";

function run_git_cmd($cmd) {
    echo "Executing: $cmd\n";
    $output = shell_exec($cmd . " 2>&1");
    echo $output . "\n";
    return $output;
}

// Automatically initialize git and set remote origin if it doesn't exist
$github_token = $_ENV['GITHUB_TOKEN'] ?? '';
if (!empty($github_token)) {
    $remote_url = "https://{$github_token}@github.com/Ajigiwe/my-shop-2.git";
} else {
    $remote_url = "https://github.com/Ajigiwe/my-shop-2.git";
}

if (!file_exists(__DIR__ . '/.git')) {
    echo "No Git repository found on server. Initializing...\n";
    run_git_cmd("git init");
    run_git_cmd("git remote add origin {$remote_url}");
    // Ensure we fetch from remote branch
    run_git_cmd("git fetch origin");
    // Align master/main branch
    run_git_cmd("git checkout -b main");
} else {
    // If repository already exists, update remote URL in case token changed
    run_git_cmd("git remote set-url origin {$remote_url}");
}

// 1. Fetch the latest changes from GitHub
run_git_cmd("git fetch --all");

// 2. FORCE the server to match the GitHub 'main' branch exactly
run_git_cmd("git reset --hard origin/main");

// 3. Verify current status
run_git_cmd("git status");

echo "------------------------------------------\n";
echo "✅ Deployment Sync Complete! Please check your site now.\n";
?>

