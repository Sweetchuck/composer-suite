<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerSuite;

use Composer\Factory as ComposerFactory;
use Sweetchuck\ComposerSuite\Composer\Plugin;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Webmozart\PathUtil\Path;

class SuiteHandler
{

    protected int $jsonEncodeFlags = \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE;

    protected Filesystem $fs;

    protected array $allowedSortNormalFunctions = [
        'asort',
        'arsort',
        'krsort',
        'ksort',
        'natcasesort',
        'natsort',
        'rsort',
        'shuffle',
        'sort',
    ];

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
                case 'sortNormal':
                    $action += ['config' => []];
                    $method = 'action' . ucfirst($action['type']);
                    $this->{$method}($composerData, $action['config']);
                    break;

                default:
                    assert(false, "invalid step type: {$action['type']}");
            }
        }

        unset($composerData['extra'][Plugin::NAME]);
        $this->sortPackages($composerData);

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
        $base = preg_replace(
            '/\.json$/',
            '',
            Path::getFilename($composerFile),
        );

        $projectRoot = Path::getDirectory($composerFile) ?: '.';

        $files = (new Finder())
            ->in($projectRoot)
            ->files()
            ->name(sprintf('/^%s\.[^\.]+\.json$/', $base))
            ->depth(0);

        $fileNames = [];
        foreach ($files as $file) {
            $fileName = $file->getBasename();
            $parts = explode('.', $fileName);
            array_pop($parts);
            $fileNames[$fileName] = array_pop($parts);
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
        $sub =& NestedArray::getValue($data, $config['parents'], $keyExists);
        if (!$keyExists) {
            NestedArray::setValue($data, $config['parents'], []);
            $sub =& NestedArray::getValue($data, $config['parents'], $keyExists);
        }
        // @todo Do something if $sub is not array.
        settype($sub, 'array');

        if (Utils::isVector($sub)) {
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
        $sub =& NestedArray::getValue($data, $config['parents'], $keyExists);
        if (!$keyExists) {
            NestedArray::setValue($data, $config['parents'], []);
            $sub =& NestedArray::getValue($data, $config['parents'], $keyExists);
        }
        // @todo Do something if $sub is not array.
        settype($sub, 'array');

        if (($sub && Utils::isVector($sub))
            || Utils::isVector($config['items'])
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
        $sub =& NestedArray::getValue($data, $config['parents'], $keyExists);
        if (!$keyExists) {
            NestedArray::setValue($data, $config['parents'], []);
            $sub =& NestedArray::getValue($data, $config['parents'], $keyExists);
        }
        // @todo Do something if $sub is not array.
        settype($sub, 'array');

        $isVector = (
            ($sub && Utils::isVector($sub))
            ||
            (!$sub && Utils::isVector($config['items']))
        );

        if (!$isVector) {
            $key = array_search($key, array_keys($sub));
            if ($key === false) {
                $key = 0;
            }
        }

        if ($config['placement'] === 'after') {
            $key++;
        }

        if ($isVector) {
            $sub = array_merge(
                array_slice($sub, 0, $key, true),
                $config['items'],
                array_slice($sub, $key, null, false),
            );

            return $this;
        }

        $sub = array_slice($sub, 0, $key, true)
            + $config['items']
            + array_slice($sub, $key, null, true);

        return $this;
    }

    protected function actionSortNormal(&$data, array $config)
    {
        $config = array_replace_recursive(
            [
                'parents' => [],
                'function' => 'ksort',
                'params' => [],
            ],
            $config,
        );

        if (!in_array($config['function'], $this->allowedSortNormalFunctions)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'invalid sortNormal function: %s; allowed values: %s',
                    $config['function'],
                    implode(', ', $this->allowedSortNormalFunctions),
                ),
                1,
            );
        }

        $sub =& NestedArray::getValue($data, $config['parents']);
        $function = $config['function'];
        $function($sub, ...$config['params']);

        return $this;
    }

    protected function sortPackages(array &$composerData)
    {
        if (empty($composerData['config']['sort-packages'])) {
            return $this;
        }

        $comparer = new RequirementComparer();
        if (isset($composerData['require'])) {
            uksort($composerData['require'], $comparer);
        }

        if (isset($composerData['require-dev'])) {
            uksort($composerData['require-dev'], $comparer);
        }

        return $this;
    }
}
