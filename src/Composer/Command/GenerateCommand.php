<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerSuite\Composer\Command;

use Composer\Factory as ComposerFactory;
use Composer\Package\Dumper\ArrayDumper;
use Sweetchuck\ComposerSuite\Composer\Plugin;
use Symfony\Component\Console\Input\InputOption;

class GenerateCommand extends CommandBase
{

    /**
     * {@inheritDoc}
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

    protected function doIt()
    {
        $this->result = [
            'exitCode' => 0,
        ];

        $package = $this->getComposer()->getPackage();
        //$packageDumper = new ArrayDumper();
        $composerFile = ComposerFactory::getComposerFile();
        $composerContent = file_get_contents($composerFile) ?: '{}';
        $composerData = $this->suiteHandler->decode($composerContent);
        //$composerData = $packageDumper->dump($package);

        $extra = $package->getExtra();
        $suites = $extra[Plugin::NAME] ?? [];
        foreach ($suites as $suiteName => $actions) {
            //codecept_debug($actions);
            $suiteFileName = $this->suiteHandler->suiteFileName($suiteName, $composerFile);
            $suiteData = $this->suiteHandler->generate($composerData, $actions);
            $action = $this->suiteHandler->whatToDo($suiteFileName, $suiteData);
            $this->doItMessage($action, $suiteFileName);
            if (in_array($action, ['create', 'update'])) {
                $this->fs->dumpFile(
                    $suiteFileName,
                    $this->suiteHandler->encode($suiteData),
                );
            }
        }

        return $this;
    }

    protected function doItMessage(string $action, string $fileName)
    {
        $io = $this->getIO();
        switch ($action) {
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
