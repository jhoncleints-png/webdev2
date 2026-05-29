#!/bin/bash

# Start WebSocket server in background
php bin/console websocket:server > /tmp/websocket.log 2>&1 &

# Start web server
php -d variables_order=EGPCS -S 0.0.0.0:8080 -t public
