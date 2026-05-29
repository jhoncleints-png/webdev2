<?php

namespace App\Command;

use App\WebSocket\NativeWebSocketServer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'websocket:server', description: 'Start WebSocket server for real-time order updates')]
class WebSocketServerCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Starting native WebSocket server...</info>');
        
        $webSocket = new NativeWebSocketServer();
        $webSocket->start();
        
        return Command::SUCCESS;
    }
}
