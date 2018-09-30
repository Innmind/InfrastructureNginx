<?php
declare(strict_types = 1);

namespace Tests\Innmind\Infrastructure\Nginx\Command;

use Innmind\Infrastructure\Nginx\Command\SetupSite;
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
use Innmind\Filesystem\Adapter;
use Innmind\Stream\Writable;
use Innmind\Immutable\{
    Map,
    Str,
    Stream,
};
use PHPUnit\Framework\TestCase;

class SetupSiteTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Command::class,
            new SetupSite(
                $this->createMock(Client::class),
                $this->createMock(Adapter::class)
            )
        );
    }

    public function testUsage()
    {
        $usage = <<<USAGE
setup-site

This will modify the default nginx site

It will do so by looking at the events registered
by the installation monitor
USAGE;

        $this->assertSame(
            $usage,
            (string) new SetupSite(
                $this->createMock(Client::class),
                $this->createMock(Adapter::class)
            )
        );
    }

    public function testInvokation()
    {
        $setup = new SetupSite(
            $client = $this->createMock(Client::class),
            $config = $this->createMock(Adapter::class)
        );
        $client
            ->expects($this->once())
            ->method('events')
            ->willReturn(Stream::of(
                Event::class,
                new Event(
                    new Event\Name('website_available'),
                    (new Map('string', 'variable'))
                        ->put('path', '/foo')
                ),
                new Event(
                    new Event\Name('website_available'),
                    (new Map('string', 'variable'))
                        ->put('path', '/bar')
                )
            ));
        $config
            ->expects($this->once())
            ->method('add')
            ->with($this->callback(static function($file): bool {
                $maj = \PHP_MAJOR_VERSION;
                $min = \PHP_MINOR_VERSION;

                return (string) $file->name() === 'default' &&
                    (string) $file->content() === <<<CONFIG
server {
    listen 80 default_server;
    listen [::]:80 default_server;

    # SSL configuration

    root /bar;

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
            }));

        $this->assertNull($setup(
            $this->createMock(Environment::class),
            new Arguments,
            new Options
        ));
    }

    public function testFailWhenNoEvent()
    {
        $setup = new SetupSite(
            $client = $this->createMock(Client::class),
            $config = $this->createMock(Adapter::class)
        );
        $client
            ->expects($this->once())
            ->method('events')
            ->willReturn(Stream::of(
                Event::class,
                new Event(
                    new Event\Name('watev'),
                    new Map('string', 'variable')
                )
            ));
        $config
            ->expects($this->never())
            ->method('add');
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->once())
            ->method('error')
            ->willReturn($error = $this->createMock(Writable::class));
        $error
            ->expects($this->once())
            ->method('write')
            ->with(Str::of("No website available\n"));
        $env
            ->expects($this->once())
            ->method('exit')
            ->with(1);

        $this->assertNull($setup(
            $env,
            new Arguments,
            new Options
        ));
    }
}
