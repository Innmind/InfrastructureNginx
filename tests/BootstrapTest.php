<?php
declare(strict_types = 1);

namespace Tests\Innmind\Infrastructure\Nginx;

use function Innmind\Infrastructure\Nginx\bootstrap;
use Innmind\CLI\Commands;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Url\Path;
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    public function testBootstrap()
    {
        $commands = bootstrap(
            $this->createMock(OperatingSystem::class),
            new Path('/tmp')
        );

        $this->assertInstanceOf(Commands::class, $commands);
    }
}
