<?php
declare(strict_types = 1);

namespace Innmind\Infrastructure\Nginx;

use function Innmind\InstallationMonitor\bootstrap as monitor;
use Innmind\CLI\Commands;
use Innmind\Url\{
    PathInterface,
    Path,
};
use Innmind\OperatingSystem\OperatingSystem;

function bootstrap(OperatingSystem $os, PathInterface $nginx = null): Commands
{
    $clients = monitor($os)['client'];

    return new Commands(
        new Command\Install($os->control()),
        new Command\SetupSite(
            $clients['silence'](
                $clients['ipc']()
            ),
            $os->filesystem()->mount($nginx ?? new Path('/etc/nginx/sites-available'))
        )
    );
}
