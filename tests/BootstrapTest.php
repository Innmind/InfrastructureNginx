<?php
declare(strict_types = 1);

namespace Tests\Innmind\Infrastructure\Nginx;

use function Innmind\Infrastructure\Nginx\bootstrap;
use Innmind\CLI\Commands;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Server\Status\Server;
use Innmind\Url\Path;
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    public function testBootstrap()
    {
        $os = $this->createMock(OperatingSystem::class);
        $os
            ->expects($this->any())
            ->method('status')
            ->willReturn($server = $this->createMock(Server::class));
        $server
            ->expects($this->any())
            ->method('tmp')
            ->willReturn(Path::none());

        $commands = bootstrap(
            $os,
            Path::of('/tmp/')
        );

        $this->assertInstanceOf(Commands::class, $commands);
    }
}
