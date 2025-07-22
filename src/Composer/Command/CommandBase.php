<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerSuite\Composer\Command;

use Composer\Command\BaseCommand;
use Sweetchuck\ComposerSuiteHandler\SuiteHandler;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

abstract class CommandBase extends BaseCommand
{
    protected array $result = [];

    protected SuiteHandler $suiteHandler;

    protected Filesystem $fs;

    protected InputInterface $input;

    public function getInput(): InputInterface
    {
        return $this->input;
    }

    public function setInput(InputInterface $input): static
    {
        $this->input = $input;

        return $this;
    }

    protected OutputInterface $output;

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    public function setOutput(OutputInterface $output): static
    {
        $this->output = $output;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function __construct(
        ?string $name = null,
        ?SuiteHandler $suiteHandler = null,
        ?Filesystem $fs = null,
    ) {
        $this->suiteHandler = $suiteHandler ?: new SuiteHandler();
        $this->fs = $fs ?: new Filesystem();
        parent::__construct($name);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this
                ->setInput($input)
                ->setOutput($output)
                ->doIt();
        } catch (\Exception $e) {
            $this->getIO()->error($e->getMessage());

            return 1;
        }

        return $this->result['exitCode'];
    }

    abstract protected function doIt(): static;
}
