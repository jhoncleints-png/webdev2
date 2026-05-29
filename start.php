<?php

// Start WebSocket server in background
$pid = shell_exec('php bin/console websocket:server > /tmp/websocket.log 2>&1 & echo $!');
error_log('[START] WebSocket server started with PID: ' . $pid);

// Start web server
$command = sprintf('php -d variables_order=EGPCS -S 0.0.0.0:%d -t public', getenv('PORT') ?: 8080);
error_log('[START] Starting web server: ' . $command);
pcntl_exec('/usr/bin/php', ['-d', 'variables_order=EGPCS', '-S', '0.0.0.0:' . (getenv('PORT') ?: 8080), '-t', 'public']);
