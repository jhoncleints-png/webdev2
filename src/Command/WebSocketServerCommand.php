<?php

namespace App\Command;

use App\WebSocket\OrderWebSocketServer;
use App\Service\WebSocketBroadcaster;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'websocket:server', description: 'Start WebSocket server for real-time order updates')]
class WebSocketServerCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $port = getenv('WEBSOCKET_PORT') ?: 8080;
        $host = getenv('WEBSOCKET_HOST') ?: '0.0.0.0';
        
        $output->writeln('<info>Starting WebSocket server on ' . $host . ':' . $port . '</info>');
        
        $webSocket = new OrderWebSocketServer();
        
        // Set the server instance for broadcasting
        WebSocketBroadcaster::setServer($webSocket);
        
        $server = IoServer::factory(
            new HttpServer(
                new WsServer($webSocket)
            ),
            $port,
            $host
        );
        
        $output->writeln('<info>WebSocket server started successfully</info>');
        $output->writeln('<info>Press Ctrl+C to stop the server</info>');
        
        $server->run();
        
        return Command::SUCCESS;
    }
}
