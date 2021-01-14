<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerSuite;

use Composer\Factory as ComposerFactory;
use Sweetchuck\ComposerSuite\Composer\Plugin;
use Symfony\Component\Filesystem\Filesystem;

class SuiteHandler
{

    protected int $jsonEncodeFlags = \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE;

    protected Filesystem $fs;

    public function __construct(
        ?Filesystem $fs = null
    ) {
        $this->fs = $fs ?: new Filesystem();
    }

    public function suiteFileName(string $suiteName, string $composerFile = ''): string
    {
        if ($composerFile === '') {
            $composerFile = ComposerFactory::getComposerFile();
        }

        $base = preg_replace('/\.json$/', '', $composerFile);

        return "$base.$suiteName.json";
    }

    public function generate(array $composerData, array $actions): array
    {
        foreach ($actions as $action) {
            switch ($action['type']) {
                case 'replaceRecursive':
                case 'unset':
                case 'prepend':
                case 'append':
                case 'insertBefore':
                case 'insertAfter':
                    $action += ['config' => []];
                    $method = 'action' . ucfirst($action['type']);
                    $this->{$method}($composerData, $action['config']);
                    break;

                default:
                    assert(false, "invalid step type: {$action['type']}");
            }
        }

        unset($composerData['extra'][Plugin::NAME]);

        return $composerData;
    }

    public function whatToDo(string $fileName, array $dataNew): string
    {
        // @todo Error handling.
        $contentOld = $this->fs->exists($fileName) ?
            file_get_contents($fileName)
            : null;

        if ($contentOld === null) {
            return 'create';
        }

        $dataOld = $this->decode($contentOld);

        return $dataOld === $dataNew ? 'skip' : 'update';
    }

    public function encode(array $data): string
    {
        return json_encode($data, $this->jsonEncodeFlags);
    }

    public function decode(string $encoded): array
    {
        return json_decode($encoded, true);
    }

    public function collectSuiteComposerFiles(string $composerFile): array
    {
        $base = preg_replace('/\.json$/', '', $composerFile);
        $iterator = new \GlobIterator("$base.*.json");
        $fileNames = [];
        while ($iterator->valid()) {
            $fileName = $iterator->current()->getFilename();
            $parts = explode('.', $fileName);
            array_pop($parts);
            $fileNames[$fileName] = array_pop($parts);
            $iterator->next();
        }

        return $fileNames;
    }

    protected function actionReplaceRecursive(array &$data, array $config)
    {
        $config = array_replace_recursive(
            [
                'parents' => [],
                'items' => [],
            ],
            $config,
        );
        $sub =& NestedArray::getValue($data, $config['parents']);
        $sub = array_replace_recursive($sub, $config['items']);

        return $this;
    }

    protected function actionUnset(array &$data, array $config)
    {
        $config = array_replace_recursive(
            [
                'parents' => [],
            ],
            $config,
        );

        if (!$config['parents']) {
            $data = [];

            return $this;
        }

        $children = (array) array_pop($config['parents']);
        $keyExists = false;
        $sub =& NestedArray::getValue($data, $config['parents'], $keyExists);
        if (!$keyExists) {
            return $this;
        }

        foreach ($children as $child) {
            unset($sub[$child]);
        }

        return $this;
    }

    protected function actionPrepend(&$data, array $config)
    {
        $config = array_replace_recursive(
            [
                'parents' => [],
                'items' => [],
            ],
            $config,
        );
        $sub =& NestedArray::getValue($data, $config['parents']);

        if ($this->isVector($sub)) {
            $sub = array_merge($config['items'], $sub);

            return $this;
        }

        $sub = $config['items'] + $sub;

        return $this;
    }

    protected function actionAppend(&$data, array $config)
    {
        $config = array_replace_recursive(
            [
                'parents' => [],
                'items' => [],
            ],
            $config,
        );
        $sub =& NestedArray::getValue($data, $config['parents']);

        if (($sub && $this->isVector($sub))
            || $this->isVector($config['items'])
        ) {
            $sub = array_merge($sub, $config['items']);

            return $this;
        }

        $sub += $config['items'];

        return $this;
    }

    protected function actionInsertBefore(array &$data, array $config)
    {
        $config = array_replace_recursive(
            [
                'parents' => [],
                'items' => [],
            ],
            $config,
        );
        $config['placement'] = 'before';

        return $this->actionInsert($data, $config);
    }

    protected function actionInsertAfter(array &$data, array $config)
    {
        $config = array_replace_recursive(
            [
                'parents' => [],
                'items' => [],
            ],
            $config,
        );
        $config['placement'] = 'after';

        return $this->actionInsert($data, $config);
    }

    protected function actionInsert(&$data, array $config)
    {
        $config = array_replace_recursive(
            [
                'parents' => [],
                'items' => [],
                'placement' => 'before',
            ],
            $config,
        );

        $key = array_pop($config['parents']);
        $sub =& NestedArray::getValue($data, $config['parents']);

        $isVector = (
            ($sub && $this->isVector($sub))
            ||
            (!$sub && $this->isVector($config['items']))
        );

        if (!$isVector) {
            $key = array_search($key, array_keys($sub));
        }

        if ($config['placement'] === 'after') {
            $key++;
        }

        if ($isVector) {
            $sub = array_merge(
                array_slice($sub, 0, $key, true),
                $config['items'],
                array_slice($sub, $key, null, true),
            );

            return $this;
        }

        $sub = array_slice($sub, 0, $key, true)
            + $config['items']
            + array_slice($sub, $key, null, true);

        return $this;
    }

    protected function isVector(array $items): bool
    {
        if (!$items) {
            return false;
        }

        return array_keys($items) === range(0, count($items) - 1);
    }
}
