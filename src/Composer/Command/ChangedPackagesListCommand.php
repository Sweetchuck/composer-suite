<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerSuite\Composer\Command;

use Sweetchuck\ComposerSuiteHandler\RequireDiffer;
use Sweetchuck\ComposerSuiteHandler\SuiteHandler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class ChangedPackagesListCommand extends CommandBase
{
    protected RequireDiffer $requireDiffer;

    public function __construct(
        string $name = null,
        ?SuiteHandler $suiteHandler = null,
        ?Filesystem $fs = null,
        ?RequireDiffer $requireDiffer = null
    ) {
        if ($name === null) {
            $name = 'suite:changed-packages:list';
        }
        $this->requireDiffer = $requireDiffer ?: new RequireDiffer();
        parent::__construct($name, $suiteHandler, $fs);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        if (!$this->getName()) {
            $this->setName('suite:changed-packages:list');
        }

        // phpcs:disable Generic.Files.LineLength.TooLong
        $this->setDescription('Lists the added/modified packages between the base composer.json and the currently used composer.<suite_id>.json files');
        $this->setHelp('Lists the added/modified packages between the base composer.json and the currently used composer.<suite_id>.json files');
        // phpcs:enable Generic.Files.LineLength.TooLong
    }

    protected string $workingDirectory = '.';

    public function getWorkingDirectory(): string
    {
        return $this->workingDirectory;
    }

    /**
     * @return $this
     */
    public function setWorkingDirectory(string $workingDirectory)
    {
        $this->workingDirectory = $workingDirectory;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function doIt()
    {
        $this->result = [
            'exitCode' => 0,
        ];
        $this->executeCalculateDiff();
        foreach ($this->result['diff'] as $name => $diff) {
            $this->output->writeln($name);
        }

        return $this;
    }

    protected function executeCalculateDiff()
    {
        $workingDirectory = $this->getWorkingDirectory();
        $baseComposerJson = Path::join(
            $workingDirectory,
            'composer.json',
        );
        $base = json_decode(
            file_get_contents($baseComposerJson) ?: '{}',
            true,
        );

        $actualComposerJson = Path::join(
            $workingDirectory,
            getenv('COMPOSER') ?: 'composer.json',
        );
        $actual = json_decode(
            file_get_contents($actualComposerJson) ?: '{}',
            true,
        );

        $this->result['diff'] = $this->requireDiffer->diff($base, $actual);

        return $this;
    }
}
