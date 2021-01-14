<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerSuite\Composer;

use Composer\Plugin\Capability\CommandProvider as ComposerCommandProvider;
use Sweetchuck\ComposerSuite\Composer\Command\GenerateCommand;

class CommandProvider implements ComposerCommandProvider
{
    /**
     * {@inheritDoc}
     */
    public function getCommands()
    {
        return [
            new GenerateCommand(),
        ];
    }
}
