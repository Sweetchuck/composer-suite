<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerSuite\Composer\Command;

use Sweetchuck\ComposerSuiteHandler\Utils;

class GenerateCommand extends CommandBase
{

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        if (!$this->getName()) {
            $this->setName('suite:generate');
        }

        $this->setDescription('DESC Generates composer.<suite_id>.json files.');
        $this->setHelp('HELP Generates composer.<suite_id>.json files.');
    }

    /**
     * {@inheritdoc}
     */
    protected function doIt()
    {
        $this->result = [
            'exitCode' => 0,
        ];

        // @todo Placeholders aren't supported :-(.
        $io = $this->getIO();

        $composerFileName = getenv('COMPOSER') ?: 'composer.json';
        $workingDirectory = dirname($composerFileName) ?: '.';
        $composerFileName = "$workingDirectory/composer.json";
        $suiteDefinitions = $this
            ->suiteHandler
            ->collectSuiteDefinitions(
                $composerFileName,
                $this->getComposer()->getPackage()->getExtra(),
            );

        if (!$suiteDefinitions) {
            $io->warning(sprintf(
                'IO There are no suite definitions in the "%s" file',
                $composerFileName,
            ));

            return $this;
        }

        $composerContent = file_get_contents($composerFileName) ?: '{}';
        $this->dumpSuites($composerFileName, Utils::decode($composerContent), $suiteDefinitions);

        return $this;
    }

    /**
     * @return $this
     */
    protected function dumpSuites(string $composerFileName, array $composerData, array $suiteDefinitions)
    {
        foreach ($suiteDefinitions as $suiteDefinition) {
            $this->dumpSuite($composerFileName, $composerData, $suiteDefinition);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function dumpSuite(string $composerFileName, array $composerData, array $suiteDefinition)
    {
        $actions = $suiteDefinition['actions'] ?? [];
        if (!$actions) {
            $this->getIO()->warning(sprintf(
                'There are no action steps in the "%s" suite',
                $suiteDefinition['name'],
            ));

            return $this;
        }

        $suiteFileName = $this->suiteHandler->suiteFileName($composerFileName, $suiteDefinition['name']);

        $suiteData = $this->suiteHandler->generate($composerData, $actions);
        $task = $this->suiteHandler->whatToDo($suiteFileName, $suiteData);
        $this->doItMessage($task, $suiteFileName);
        if (in_array($task, ['create', 'update'])) {
            $this->fs->dumpFile(
                $suiteFileName,
                Utils::encode($suiteData) . "\n",
            );
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function doItMessage(string $task, string $fileName)
    {
        $io = $this->getIO();
        switch ($task) {
            case 'skip':
                $io->info("no need to update <info>$fileName</info>");
                break;

            case 'create':
                $io->info("create <info>$fileName</info>");
                break;

            case 'update':
                $io->info("update <info>$fileName</info>");
                break;
        }

        return $this;
    }
}
