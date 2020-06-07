<?php
declare(strict_types = 1);

namespace Innmind\Infrastructure\Nginx\Gene;

use Innmind\Genome\{
    Gene,
    History,
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

final class Install implements Gene
{
    public function name(): string
    {
        return 'Nginx install';
    }

    public function express(
        OperatingSystem $local,
        Server $target,
        History $history
    ): History {
        try {
            $preCondition = new Script(
                Command::foreground('which')->withArgument('apt'),
            );
            $preCondition($target);
        } catch (ScriptFailed $e) {
            throw new PreConditionFailed('apt is missing');
        }

        try {
            $install = new Script(
                Command::foreground('apt')
                    ->withArgument('install')
                    ->withShortOption('y')
                    ->withArgument('nginx'),
                Command::foreground('rm')
                    ->withArgument('/etc/nginx/sites-enabled/default'),
            );
            $install($target);
        } catch (ScriptFailed $e) {
            throw new ExpressionFailed($this->name());
        }

        return $history;
    }
}
