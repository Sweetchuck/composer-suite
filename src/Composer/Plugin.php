<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerSuite\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider as ComposerCommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Sweetchuck\ComposerSuiteHandler\SuiteHandler;
use Sweetchuck\ComposerSuiteHandler\Utils;

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

        $composerFileName = './composer.json';
        $composerContent = file_get_contents($composerFileName) ?: '{}';
        $composerData = Utils::decode($composerContent);

        $suiteDefinitions = $this
            ->suiteHandler
            ->collectSuiteDefinitions(
                $composerFileName,
                $this->composer->getPackage()->getExtra(),
            );

        $existingSuiteFiles = $this->suiteHandler->collectSuiteComposerFiles($composerFileName);
        $extraSuiteFiles = array_diff_key(
            array_flip($existingSuiteFiles),
            $suiteDefinitions,
        );
        foreach ($extraSuiteFiles as $suiteFile) {
            $this->io->error("<warning>{$pluginName} - ./{$suiteFile} exists, but not defined</warning>");
        }

        $isUpToDate = true;
        foreach ($suiteDefinitions as $suiteName => $suiteDefinition) {
            $newData = $this->suiteHandler->generate($composerData, $suiteDefinition['actions'] ?? []);
            $newFileName = $this->suiteHandler->suiteFileName($composerFileName, $suiteName);
            $whatToDo = $this->suiteHandler->whatToDo($newFileName, $newData);
            if ($whatToDo === 'skip') {
                continue;
            }

            switch ($whatToDo) {
                case 'create':
                    $this->io->warning("<warning>{$pluginName} - {$newFileName} is not exists</warning>");
                    break;

                case 'update':
                    $isUpToDate = false;
                    $this->io->error("<error>{$pluginName} - {$newFileName} is not up to date</error>");
                    break;
            }
        }

        return $isUpToDate;
    }
}
