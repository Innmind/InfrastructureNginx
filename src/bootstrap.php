<?php
declare(strict_types = 1);

namespace Innmind\Infrastructure\Nginx;

use Innmind\Infrastructure\Nginx\Command\Install;
use Innmind\CLI\Commands;
use Innmind\Server\Control\ServerFactory;

function bootstrap(): Commands
{
    return new Commands(
        new Install(ServerFactory::build())
    );
}
