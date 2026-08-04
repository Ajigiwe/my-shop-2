<?php
$output = [];
exec('git fetch origin 2>&1', $output);
exec('git reset --hard origin/main 2>&1', $output);
exec('git log -1 --oneline 2>&1', $output);

if (function_exists('opcache_reset')) {
    @opcache_reset();
    $output[] = "OPcache reset: OK";
}
clearstatcache(true);

echo '<pre style="background: #111; color: #00e650; padding: 20px; border-radius: 8px; font-family: monospace; font-size: 14px;">';
echo "=== ASO LIVE SERVER SYNC COMPLETE ===\n\n";
echo implode("\n", $output);
echo '</pre>';