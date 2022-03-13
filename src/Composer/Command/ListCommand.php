<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerSuite\Composer\Command;

use Sweetchuck\ComposerSuite\Utils;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputOption;

class ListCommand extends CommandBase
{

    protected function configure()
    {
        parent::configure();
        if (!$this->getName()) {
            $this->setName('suite:list');
        }

        $this->setDescription('Lists the available suite definitions.');
        $this->setHelp('Lists the available suite definitions');
        $this->addOption(
            'format',
            null,
            InputOption::VALUE_REQUIRED,
            'Allowed values: table, json',
            'table',
        );
    }

    protected function doIt()
    {
        $this->result = [
            'exitCode' => 0,
        ];

        $composerFileName = './composer.json';
        $suiteDefinitions = $this
            ->suiteHandler
            ->collectSuiteDefinitions(
                $composerFileName,
                $this->getComposer()->getPackage()->getExtra(),
            );

        if ($this->input->getOption('format') === 'json') {
            $this->getOutput()->writeln(Utils::encode($suiteDefinitions));

            return $this;
        }

        foreach (array_keys($suiteDefinitions) as $suiteName) {
            $suiteDefinitions[$suiteName] = array_intersect_key(
                $suiteDefinitions[$suiteName],
                ['source' => '', 'name' => '', 'description' => ''],
            );
        }

        $table = new Table($this->getOutput());
        $table
            ->setHeaders(['Source', 'Name', 'Description'])
            ->setRows($suiteDefinitions)
            ->render();

        return $this;
    }
}
