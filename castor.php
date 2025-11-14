<?php

use Castor\Attribute\AsTask;

use function Castor\{io,import,capture,run};

import('src/Command/LoadCommand.php');
#[AsTask(description: 'install!')]
function build(): void
{
    io()->title("Installing the application data");

    run('bin/console meili:settings:update --force');
    run('bin/console app:load');
}

#[AsTask(description: 'start the server')]
function start(): void
{
    io()->title("Installing the application data");
    run('symfony server:start -d');
    run('symfony open:local');
}
