<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerSuite\Tests\Unit\Composer\Command;

use Composer\Console\Application;
use org\bovigo\vfs\vfsStream;
use Sweetchuck\ComposerSuite\Composer\Command\GenerateCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \Sweetchuck\ComposerSuite\Composer\Command\GenerateCommand
 */
class GenerateCommandTest extends CommandTestBase
{

    public function testSuiteGenerateSuccess()
    {
        $vfsRoot = vfsStream::setup(
            __FUNCTION__,
            null,
            [
                'composer.json' => json_encode([
                    'require' => [
                        'a/b' => '^1.0',
                    ],
                    'extra' => [
                        'composer-suite' => [
                            'one' => [
                                [
                                    'type' => 'replaceRecursive',
                                    'config' => [
                                        'parents' => [],
                                        'items' => [
                                            'require' => [
                                                'a/b' => '1.x-dev',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]),
            ],
        );

        putenv('COMPOSER=' . $vfsRoot->url() . '/composer.json');

        $application = new Application();
        $command = new GenerateCommand('suite:generate');
        $command->setApplication($application);
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [],
            [
                'verbosity' => OutputInterface::VERBOSITY_VERY_VERBOSE,
            ],
        );

        $this->tester->assertStringEqualsFile(
            $vfsRoot->url() . '/composer.one.json',
            json_encode(
                [
                    'require' => [
                        'a/b' => '1.x-dev',
                    ],
                    'extra' => [],
                ],
                \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE,
            ),
        );
    }
}
