<?php
declare(strict_types = 1);

namespace Tests\Innmind\Infrastructure\Nginx;

use function Innmind\Infrastructure\Nginx\bootstrap;
use Innmind\CLI\Commands;
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    public function testBootstrap()
    {
        $commands = bootstrap('/tmp');

        $this->assertInstanceOf(Commands::class, $commands);
    }
}
