<?php
declare(strict_types = 1);

namespace Innmind\Infrastructure\Nginx;

use function Innmind\InstallationMonitor\bootstrap as monitor;
use Innmind\CLI\Commands;
use Innmind\Url\Path;
use Innmind\OperatingSystem\OperatingSystem;

function bootstrap(OperatingSystem $os, Path $nginx = null): Commands
{
    $clients = monitor($os)['client'];

    return new Commands(
        new Command\Install($os->control()),
        new Command\SetupSite(
            $clients['silence'](
                $clients['ipc']()
            ),
            $os->filesystem()->mount($nginx ?? Path::of('/etc/nginx/sites-available/'))
        )
    );
}
