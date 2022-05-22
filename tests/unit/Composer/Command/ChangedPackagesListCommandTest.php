<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerSuite\Test\Unit\Composer\Command;

use Composer\Console\Application;
use org\bovigo\vfs\vfsStream;
use Sweetchuck\ComposerSuite\Composer\Command\ChangedPackagesListCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \Sweetchuck\ComposerSuite\Composer\Command\ChangedPackagesListCommand<extended>
 */
class ChangedPackagesListCommandTest extends CommandTestBase
{

    public function testSuiteChangedPackagesListSuccess()
    {
        $vfs = vfsStream::setup(
            'root',
            0777,
            [
                __FUNCTION__ => [
                    'composer.json' => implode("\n", [
                        '{',
                        '    "require": {',
                        '        "a/a": "^1.0",',
                        '        "a/b": "^2.0"',
                        '    }',
                        '}',
                    ]),
                    'composer.local.json' => implode("\n", [
                        '{',
                        '    "require": {',
                        '        "a/a": "^1.0",',
                        '        "a/b": "1.x-dev",',
                        '        "a/c": "1.x-dev"',
                        '    }',
                        '}',
                    ]),
                ],
            ],
        );

        putenv('COMPOSER=composer.local.json');

        $application = new Application();
        $command = new ChangedPackagesListCommand();
        $command->setWorkingDirectory($vfs->url() . '/' . __FUNCTION__);
        $command->setApplication($application);
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [],
            [
                'decorated' => false,
                'verbosity' => OutputInterface::VERBOSITY_VERY_VERBOSE,
            ],
        );

        $expectedExitCode = 0;
        $this->tester->assertSame(
            $expectedExitCode,
            $commandTester->getStatusCode(),
            "exit code $expectedExitCode",
        );

        $this->tester->assertSame(
            implode("\n", [
                'a/b',
                'a/c',
                '',
            ]),
            $commandTester->getDisplay(),
        );
    }
}
