<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerSuite\Composer\Command;

use Composer\Command\BaseCommand;
use Sweetchuck\ComposerSuite\SuiteHandler;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class CommandBase extends BaseCommand
{
    protected array $result = [];

    protected SuiteHandler $suiteHandler;

    protected Filesystem $fs;

    protected InputInterface $input;

    public function getInput(): InputInterface
    {
        return $this->input;
    }

    /**
     * @return $this
     */
    public function setInput(InputInterface $input)
    {
        $this->input = $input;

        return $this;
    }

    protected OutputInterface $output;

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    /**
     * @return $this
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function __construct(
        string $name = null,
        ?SuiteHandler $generator = null,
        ?Filesystem $fs = null
    ) {
        $this->suiteHandler = $generator ?: new SuiteHandler();
        $this->fs = $fs ?: new Filesystem();
        parent::__construct($name);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
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
}
