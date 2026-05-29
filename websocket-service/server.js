const WebSocket = require('ws');
const http = require('http');

const PORT = process.env.PORT || 8080;

// Create HTTP server for health checks
const server = http.createServer((req, res) => {
  if (req.url === '/health') {
    res.writeHead(200);
    res.end('WebSocket service is running');
  } else {
    res.writeHead(404);
    res.end('Not found');
  }
});

// Create WebSocket server
const wss = new WebSocket.Server({ server });

console.log('[WEBSOCKET] Server starting on port', PORT);

wss.on('connection', (ws) => {
  console.log('[WEBSOCKET] New client connected. Total clients:', wss.clients.size);
  
  ws.on('message', (message) => {
    console.log('[WEBSOCKET] Received message:', message.toString());
  });
  
  ws.on('close', () => {
    console.log('[WEBSOCKET] Client disconnected. Total clients:', wss.clients.size);
  });
  
  ws.on('error', (error) => {
    console.error('[WEBSOCKET] Error:', error);
  });
  
  // Send welcome message
  ws.send(JSON.stringify({
    type: 'connected',
    message: 'Connected to WebSocket server',
    timestamp: new Date().toISOString()
  }));
});

// Broadcast function to send messages to all connected clients
function broadcast(type, data) {
  const message = JSON.stringify({
    type,
    data,
    timestamp: new Date().toISOString()
  });
  
  wss.clients.forEach((client) => {
    if (client.readyState === WebSocket.OPEN) {
      client.send(message);
    }
  });
  
  console.log('[WEBSOCKET] Broadcasted', type, 'to', wss.clients.size, 'clients');
}

// HTTP endpoint for PHP backend to send messages
server.on('request', (req, res) => {
  if (req.method === 'POST' && req.url === '/broadcast') {
    let body = '';
    
    req.on('data', (chunk) => {
      body += chunk.toString();
    });
    
    req.on('end', () => {
      try {
        const { type, data } = JSON.parse(body);
        broadcast(type, data);
        res.writeHead(200);
        res.end(JSON.stringify({ success: true }));
      } catch (error) {
        console.error('[WEBSOCKET] Broadcast error:', error);
        res.writeHead(400);
        res.end(JSON.stringify({ error: 'Invalid JSON' }));
      }
    });
  } else {
    // Handle regular HTTP requests
    server.emit('request', req, res);
  }
});

server.listen(PORT, '0.0.0.0', () => {
  console.log('[WEBSOCKET] Server listening on port', PORT);
});
