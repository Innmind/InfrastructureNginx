<?php
declare(strict_types = 1);

namespace Innmind\Infrastructure\Nginx\Command;

use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
};
use Innmind\InstallationMonitor\{
    Client,
    Event,
};
use Innmind\Filesystem\{
    Adapter,
    File\File,
    Stream\StringStream,
};
use Innmind\Immutable\Str;

final class SetupSite implements Command
{
    private Client $client;
    private Adapter $config;

    public function __construct(Client $client, Adapter $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    public function __invoke(Environment $env, Arguments $arguments, Options $options): void
    {
        $events = $this
            ->client
            ->events()
            ->groupBy(static function(Event $event): string {
                return (string) $event->name();
            });

        if (!$events->contains('website_available')) {
            $env->error()->write(
                Str::of("No website available\n")
            );
            $env->exit(1);

            return;
        }

        $path = $events
            ->get('website_available')
            ->last()
            ->payload()
            ->get('path');

        $maj = \PHP_MAJOR_VERSION;
        $min = \PHP_MINOR_VERSION;

        $config = <<<CONFIG
server {
    listen 80 default_server;
    listen [::]:80 default_server;

    # SSL configuration

    root $path;

    index index.html index.htm index.php;

    server_name _;

    location / {
        try_files \$uri /index.php\$is_args\$args;
    }

    location ~ ^/index\\.php(/|\$) {
        try_files \$uri =404;
        fastcgi_split_path_info ^(.+\\.php)(/.*)$;
        fastcgi_pass unix:/var/run/php/php$maj.$min-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_param PATH_INFO \$fastcgi_path_info;
    }
}
CONFIG;

        $this->config->add(new File(
            'default',
            new StringStream($config)
        ));
    }

    public function __toString(): string
    {
        return <<<USAGE
setup-site

This will modify the default nginx site

It will do so by looking at the events registered
by the installation monitor
USAGE;
    }
}
