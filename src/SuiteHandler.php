<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerSuite;

use Sweetchuck\ComposerSuite\Composer\Plugin;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Webmozart\PathUtil\Path;

/**
 * @todo Use a 3th-party array manipulator library.
 */
class SuiteHandler
{

    protected Filesystem $fs;

    protected array $allowedSortNormalFunctions = [
        // @todo Root namespace \sort.
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

    public function __construct(?Filesystem $fs = null)
    {
        $this->fs = $fs ?: new Filesystem();
    }

    public function suiteFileName(string $composerFileName, string $suiteName): string
    {
        assert(trim($suiteName) !== '', 'suite name cannot be empty');

        return preg_replace(
            '@(^|/)?composer\.json$@',
            "\${1}composer.$suiteName.json",
            $composerFileName,
        );
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

        $dataOld = Utils::decode($contentOld);

        return $dataOld === $dataNew ? 'skip' : 'update';
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

    public function collectSuiteDefinitions(string $composerFileName, array $extra): array
    {
        $projectRoot = dirname($composerFileName);
        if ($projectRoot === '') {
            $projectRoot = '.';
        }

        $suiteDefinitionDir = "$projectRoot/." . Plugin::NAME;

        return $this->mergeSuiteDefinitions(
            $this->collectSuiteDefinitionsFromExtra($extra),
            $this->collectSuiteDefinitionsFromDir($suiteDefinitionDir, Plugin::NAME . '.'),
        );
    }

    public function collectSuiteDefinitionsFromExtra(array $extra): array
    {
        $suiteDefinitions = $extra[Plugin::NAME] ?? [];
        foreach (array_keys($suiteDefinitions) as $suiteName) {
            $suiteDefinitions[$suiteName] = array_replace(
                [
                    'source' => '',
                    'name' => '',
                    'description' => '',
                ],
                $suiteDefinitions[$suiteName],
            );
            $suiteDefinitions[$suiteName]['source'] = "./composer.json#/extra/composer-suite/$suiteName";
            $suiteDefinitions[$suiteName]['name'] = $suiteName;
        }

        return $suiteDefinitions;
    }

    public function collectSuiteDefinitionsFromDir(string $dir, string $fileNamePrefix): array
    {
        $files = $this->collectSuiteDefinitionFilesFromDir($dir, $fileNamePrefix);

        return $this->parseSuiteDefinitionFiles($files, $fileNamePrefix);
    }

    public function collectSuiteDefinitionFilesFromDir(string $dir, string $fileNamePrefix): \Iterator
    {
        if (!$this->fs->exists($dir)) {
            return new \ArrayIterator([]);
        }

        $iterator = (new Finder())
            ->in($dir)
            ->files()
            ->name("$fileNamePrefix*.json")
            ->getIterator();
        $iterator->rewind();

        return $iterator;
    }

    public function parseSuiteDefinitionFiles(\Iterator $files, string $fileNamePrefix): array
    {
        $prefixLength = strlen($fileNamePrefix);
        $suitesNormal = [];
        $suitesOverride = [];
        while ($files->valid()) {
            /** @var \SplFileInfo $file */
            $file = $files->current();
            $suiteDefinition = Utils::decode(file_get_contents($file->getPathname()));
            $nameFile = substr($file->getBasename('.json'), $prefixLength);
            $nameInner = $suiteDefinition['name'] ?? $nameFile;

            $suiteDefinition = array_replace(
                [
                    'source' => '',
                    'name' => '',
                    'description' => '',
                ],
                $suiteDefinition,
            );
            $suiteDefinition['source'] = $file->getPathname();
            $suiteDefinition['name'] = $nameInner;

            if ($nameFile === $nameInner) {
                $suitesNormal[$nameInner] = $suiteDefinition;
            } else {
                $suitesOverride[$nameInner] = $suiteDefinition;
            }

            $files->next();
        }

        return $this->mergeSuiteDefinitions($suitesNormal, $suitesOverride);
    }

    public function mergeSuiteDefinitions(array $normal, array $overrides): array
    {
        $result = $overrides + $normal;
        ksort($result, \SORT_NATURAL);

        return $result;
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
                'items' => [],
            ],
            $config,
        );

        $keyExists = false;
        $sub =& NestedArray::getValue($data, $config['parents'], $keyExists);
        if (!$keyExists) {
            return $this;
        }

        foreach ($config['items'] as $child) {
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
