<?php
declare(strict_types = 1);

namespace Innmind\Infrastructure\Nginx;

use function Innmind\InstallationMonitor\bootstrap as monitor;
use Innmind\CLI\Commands;
use Innmind\Server\Control\ServerFactory;
use Innmind\Filesystem\Adapter\FilesystemAdapter;
use Innmind\OperatingSystem\OperatingSystem;

function bootstrap(OperatingSystem $os, string $nginx = null): Commands
{
    $clients = monitor($os)['client'];

    return new Commands(
        new Command\Install(ServerFactory::build()),
        new Command\SetupSite(
            $clients['silence'](
                $clients['ipc']()
            ),
            new FilesystemAdapter($nginx ?? '/etc/nginx/sites-available')
        )
    );
}
