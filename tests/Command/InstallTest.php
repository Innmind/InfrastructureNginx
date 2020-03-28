<?php
declare(strict_types = 1);

namespace Tests\Innmind\Infrastructure\Nginx\Command;

use Innmind\Infrastructure\Nginx\Command\Install;
use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
};
use Innmind\Server\Control\{
    Server,
    Server\Process,
    Server\Processes,
    Server\Process\ExitCode,
};
use PHPUnit\Framework\TestCase;

class InstallTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Command::class,
            new Install(
                $this->createMock(Server::class)
            )
        );
    }

    public function testUsage()
    {
        $usage = <<<USAGE
install

This will install nginx on the machine
USAGE;

        $this->assertSame($usage, (new Install($this->createMock(Server::class)))->toString());
    }

    public function testInvokation()
    {
        $install = new Install(
            $server = $this->createMock(Server::class)
        );
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return $command->toString() === "apt-get 'install' 'nginx' '-y'";
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->expects($this->once())
            ->method('exitCode')
            ->willReturn(new ExitCode(42));
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->once())
            ->method('exit')
            ->with(42);

        $this->assertNull($install(
            $env,
            new Arguments,
            new Options
        ));
    }
}
