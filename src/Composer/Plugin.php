<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerSuite\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Factory as ComposerFactory;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider as ComposerCommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Sweetchuck\ComposerSuite\SuiteHandler;

class Plugin implements PluginInterface, EventSubscriberInterface, Capable
{

    const NAME = 'composer-suite';

    protected Event $event;

    protected Composer $composer;

    protected IOInterface $io;

    protected SuiteHandler $suiteHandler;

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            PluginEvents::COMMAND => 'onCommandEvent',
        ];
    }

    public function __construct(?SuiteHandler $suiteHandler = null)
    {
        $this->suiteHandler = $suiteHandler ?: new SuiteHandler();
    }

    /**
     * {@inheritDoc}
     */
    public function getCapabilities()
    {
        return [
            ComposerCommandProvider::class => CommandProvider::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        // Nothing to do here.
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * {@inheritDoc}
     */
    public function deactivate(Composer $composer, IOInterface $io)
    {
        // Nothing to do here.
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {
        // Nothing to do here.
        $this->composer = $composer;
        $this->io = $io;
    }

    public function onCommandEvent(CommandEvent $event): bool
    {
        switch ($event->getCommandName()) {
            case 'validate':
                return $this->onCommandEventValidate();
        }

        return true;
    }

    protected function onCommandEventValidate(): bool
    {
        $pluginName = static::NAME;
        $extra = $this->composer->getPackage()->getExtra();
        $suites = $extra[$pluginName] ?? [];

        $composerFile = ComposerFactory::getComposerFile();
        // Very likely somebody else will rise an error.
        $composerContent = file_get_contents($composerFile) ?: '{}';
        $composerData = $this->suiteHandler->decode($composerContent);

        $existingSuiteFiles = $this->suiteHandler->collectSuiteComposerFiles($composerFile);
        $extraSuiteFiles = array_diff_key(
            array_flip($existingSuiteFiles),
            $suites,
        );
        foreach ($extraSuiteFiles as $suiteName => $suiteFile) {
            $this->io->error("<warning>{$pluginName} - ./{$suiteFile} exists, but not defined</warning>");
        }

        $isUpToDate = true;
        foreach ($suites as $suiteName => $actions) {
            $newData = $this->suiteHandler->generate($composerData, $actions);
            $newFileName = $this->suiteHandler->suiteFileName($suiteName, $composerFile);
            $whatToDo = $this->suiteHandler->whatToDo($newFileName, $newData);
            if ($whatToDo === 'skip') {
                continue;
            }

            switch ($whatToDo) {
                case 'create':
                    $this->io->error("<error>{$pluginName} - {$newFileName} is is not exists</error>");
                    break;

                case 'update':
                    $this->io->error("<error>{$pluginName} - {$newFileName} is is not up to date</error>");
                    break;
            }

            $isUpToDate = false;
        }

        return $isUpToDate;
    }
}
