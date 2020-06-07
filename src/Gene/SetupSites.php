<?php
declare(strict_types = 1);

namespace Innmind\Infrastructure\Nginx\Gene;

use Innmind\Genome\{
    Gene,
    History,
    History\Event,
    Exception\PreConditionFailed,
    Exception\ExpressionFailed,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Server\Control\{
    Server,
    Server\Command,
    Server\Script,
    Exception\ScriptFailed,
};
use Innmind\Url\Path;

final class SetupSites implements Gene
{
    public function name(): string
    {
        return 'Setup nginx websites';
    }

    public function express(
        OperatingSystem $local,
        Server $target,
        History $history
    ): History {
        $sites = $history->get('website_available');

        if ($sites->empty()) {
            return $history;
        }

        try {
            $preCondition = new Script(
                Command::foreground('test')->withShortOption(
                    'd',
                    '/etc/nginx/sites-available',
                ),
            );
            $preCondition($target);
        } catch (ScriptFailed $e) {
            throw new PreConditionFailed('No nginx configuration site');
        }

        /** @var list<Command> */
        $commands = $sites->reduce(
            [],
            static function(array $sites, Event $event): array {
                /** @var string */
                $path = $event->payload()->get('path');
                /** @var string */
                $phpVersion = $event->payload()->get('php_version');
                /** @var string */
                $name = $event->payload()->get('name');

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
        fastcgi_pass unix:/var/run/php/php$phpVersion-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_param PATH_INFO \$fastcgi_path_info;
    }
}
CONFIG;

                $path = '/etc/nginx/sites-available/'.$name;
                $sites[] = Command::foreground('echo')
                    ->withArgument($config)
                    ->overwrite(Path::of($path));
                $sites[] = Command::foreground('ln')
                    ->withShortOption('s')
                    ->withArgument($path)
                    ->withArgument('/etc/nginx/sites-enabled/');

                return $sites;
            },
        );

        try {
            $commands[] = Command::foreground('service')
                ->withArgument('nginx')
                ->withArgument('reload');
            $configure = new Script(...$commands);
            $configure($target);
        } catch (ScriptFailed $e) {
            throw new ExpressionFailed($this->name());
        }

        return $history;
    }
}
