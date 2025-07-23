<?php

// src/Command/RabbitMQCommand.php
namespace App\Command;

use App\Service\RabbitMQManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'survos:init-rabbitmq')]
class RabbitMQCommand extends Command
{
    public function __construct(private RabbitMQManager $rabbitMQManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->rabbitMQManager->createVhost('dummy');
        $this->rabbitMQManager->setPermissions('dummy', 'guest', '.*', '.*', '.*');
        return Command::SUCCESS;
    }
}