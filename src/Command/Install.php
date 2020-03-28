<?php
declare(strict_types = 1);

namespace Innmind\Infrastructure\Nginx\Command;

use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
};
use Innmind\Server\Control\{
    Server,
    Server\Command as ServerCommand,
};

final class Install implements Command
{
    private Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function __invoke(Environment $env, Arguments $arguments, Options $options): void
    {
        $code = $this
            ->server
            ->processes()
            ->execute(
                ServerCommand::foreground('apt-get')
                    ->withArgument('install')
                    ->withArgument('nginx')
                    ->withShortOption('y')
            )
            ->wait()
            ->exitCode();
        $env->exit($code->toInt());
    }

    public function __toString(): string
    {
        return <<<USAGE
install

This will install nginx on the machine
USAGE;
    }
}
