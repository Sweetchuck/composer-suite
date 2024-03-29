<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerSuite\Composer;

use Composer\Plugin\Capability\CommandProvider as ComposerCommandProvider;
use Sweetchuck\ComposerSuite\Composer\Command\ChangedPackagesListCommand;
use Sweetchuck\ComposerSuite\Composer\Command\GenerateCommand;
use Sweetchuck\ComposerSuite\Composer\Command\ListCommand;

class CommandProvider implements ComposerCommandProvider
{
    /**
     * {@inheritDoc}
     */
    public function getCommands()
    {
        return [
            new GenerateCommand(),
            new ListCommand(),
            new ChangedPackagesListCommand(),
        ];
    }
}
