<?php
$output = [];
exec('git fetch origin 2>&1', $output);
exec('git reset --hard origin/main 2>&1', $output);
echo '<pre>' . implode("\n", $output) . '</pre>';