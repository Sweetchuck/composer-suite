<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerSuite\Test\Unit;

use org\bovigo\vfs\vfsStream;
use Sweetchuck\ComposerSuite\SuiteHandler;

/**
 * @covers \Sweetchuck\ComposerSuite\SuiteHandler
 * @covers \Sweetchuck\ComposerSuite\RequirementComparer
 */
class SuiteHandlerTest extends TestBase
{

    public function casesSuiteFileName(): array
    {
        return [
            'basic' => ['./composer.foo.json', './composer.json', 'foo'],
            'multi dot' => ['./composer.one.two.json', './composer.json', 'one.two'],
        ];
    }

    /**
     * @dataProvider casesSuiteFileName
     */
    public function testSuiteFileName(string $expected, string $composerFileName, string $suiteName): void
    {
        $suiteHandler = new SuiteHandler();
        $this->tester->assertSame($expected, $suiteHandler->suiteFileName($composerFileName, $suiteName));
    }

    public function casesGenerateSuccess(): array
    {
        return [
            'replaceRecursive - associative; parents 0;' => [
                [
                    'a' => [
                        'b' => 'c',
                        'd' => 'e+',
                        'f' => 'g',
                    ],
                ],
                [
                    'a' => [
                        'b' => 'c',
                        'd' => 'e',
                    ],
                ],
                [
                    [
                        'type' => 'replaceRecursive',
                        'config' => [
                            'parents' => [],
                            'items' => [
                                'a' => [
                                    'd' => 'e+',
                                    'f' => 'g',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'replaceRecursive - associative; parents 1;' => [
                [
                    'a' => [
                        'b' => 'c',
                        'd' => 'e+',
                        'f' => 'g',
                    ],
                ],
                [
                    'a' => [
                        'b' => 'c',
                        'd' => 'e',
                    ],
                ],
                [
                    [
                        'type' => 'replaceRecursive',
                        'config' => [
                            'parents' => ['a'],
                            'items' => [
                                'd' => 'e+',
                                'f' => 'g',
                            ],
                        ],
                    ],
                ],
            ],
            'unset - simple' => [
                [
                    'a' => [
                        'b' => 'c',
                        'f' => 'g',
                    ],
                ],
                [
                    'a' => [
                        'b' => 'c',
                        'd' => 'e',
                        'f' => 'g',
                    ],
                ],
                [
                    [
                        'type' => 'unset',
                        'config' => [
                            'parents' => ['a'],
                            'items' => ['d'],
                        ],
                    ],
                ],
            ],
            'unset - multiple' => [
                [
                    'a' => [
                        'b' => 'c',
                        'h' => 'i',
                    ],
                ],
                [
                    'a' => [
                        'b' => 'c',
                        'd' => 'e',
                        'f' => 'g',
                        'h' => 'i',
                    ],
                ],
                [
                    [
                        'type' => 'unset',
                        'config' => [
                            'parents' => ['a'],
                            'items' => ['d', 'f'],
                        ],
                    ],
                ],
            ],
            'unset - items empty' => [
                [
                    'a' => [
                        'b' => 'c',
                        'd' => 'e',
                        'f' => 'g',
                        'h' => 'i',
                    ],
                    'b' => 'k',
                ],
                [
                    'a' => [
                        'b' => 'c',
                        'd' => 'e',
                        'f' => 'g',
                        'h' => 'i',
                    ],
                    'b' => 'k',
                ],
                [
                    [
                        'type' => 'unset',
                        'config' => [
                            'parents' => ['a'],
                            'items' => [],
                        ],
                    ],
                ],
            ],
            'unset - key not exists' => [
                [
                    'a' => [
                        'b' => 'c',
                        'd' => 'e',
                    ],
                    'b' => 'k',

                ],
                [
                    'a' => [
                        'b' => 'c',
                        'd' => 'e',
                    ],
                    'b' => 'k',
                ],
                [
                    [
                        'type' => 'unset',
                        'config' => [
                            'parents' => ['c'],
                            'items' => ['b'],
                        ],
                    ],
                ],
            ],
            'prepend - both empty;' => [
                [
                    'a' => [],
                ],
                [
                    'a' => [],
                ],
                [
                    [
                        'type' => 'prepend',
                        'config' => [
                            'parents' => ['a'],
                            'items' => [],
                        ],
                    ],
                ],
            ],
            'prepend - vector' => [
                [
                    'a' => [
                        'x',
                        'y',
                        'z',
                        'b',
                        'c',
                        'd',
                        'e',
                    ],
                ],
                [
                    'a' => [
                        'b',
                        'c',
                        'd',
                        'e',
                    ],
                ],
                [
                    [
                        'type' => 'prepend',
                        'config' => [
                            'parents' => ['a'],
                            'items' => ['x', 'y', 'z'],
                        ],
                    ],
                ],
            ],
            'prepend - vector; dst empty;' => [
                [
                    'a' => [
                        'x',
                        'y',
                        'z',
                    ],
                ],
                [
                    'a' => [],
                ],
                [
                    [
                        'type' => 'prepend',
                        'config' => [
                            'parents' => ['a'],
                            'items' => ['x', 'y', 'z'],
                        ],
                    ],
                ],
            ],
            'prepend - vector; items empty;' => [
                [
                    'a' => [
                        'x',
                        'y',
                        'z',
                    ],
                ],
                [
                    'a' => [
                        'x',
                        'y',
                        'z',
                    ],
                ],
                [
                    [
                        'type' => 'prepend',
                        'config' => [
                            'parents' => ['a'],
                            'items' => [],
                        ],
                    ],
                ],
            ],
            'prepend - vector; dst not exists;' => [
                [
                    'a' => ['b', 'c'],
                ],
                [],
                [
                    [
                        'type' => 'prepend',
                        'config' => [
                            'parents' => ['a'],
                            'items' => ['b', 'c'],
                        ],
                    ],
                ],
            ],
            'prepend - assoc' => [
                [
                    'a' => [
                        'x' => 'y',
                        'z' => '_',
                        'd' => 'e+',
                        'b' => 'c',
                    ],
                ],
                [
                    'a' => [
                        'b' => 'c',
                        'd' => 'e',
                    ],
                ],
                [
                    [
                        'type' => 'prepend',
                        'config' => [
                            'parents' => ['a'],
                            'items' => [
                                'x' => 'y',
                                'z' => '_',
                                'd' => 'e+',
                            ],
                        ],
                    ],
                ],
            ],
            'prepend - assoc; dst empty;' => [
                [
                    'a' => [
                        'x' => 'y',
                        'z' => '_',
                        'd' => 'e+',
                    ],
                ],
                [
                    'a' => [],
                ],
                [
                    [
                        'type' => 'prepend',
                        'config' => [
                            'parents' => ['a'],
                            'items' => [
                                'x' => 'y',
                                'z' => '_',
                                'd' => 'e+',
                            ],
                        ],
                    ],
                ],
            ],
            'prepend - assoc; items empty;' => [
                [
                    'a' => [
                        'x' => 'y',
                        'z' => '_',
                        'd' => 'e+',
                    ],
                ],
                [
                    'a' => [
                        'x' => 'y',
                        'z' => '_',
                        'd' => 'e+',
                    ],
                ],
                [
                    [
                        'type' => 'prepend',
                        'config' => [
                            'parents' => ['a'],
                            'items' => [],
                        ],
                    ],
                ],
            ],
            'prepend - assoc; dst not exists;' => [
                [
                    'a' => [
                        'x' => 'y',
                        'z' => '_',
                        'd' => 'e+',
                    ],
                ],
                [],
                [
                    [
                        'type' => 'prepend',
                        'config' => [
                            'parents' => ['a'],
                            'items' => [
                                'x' => 'y',
                                'z' => '_',
                                'd' => 'e+',
                            ],
                        ],
                    ],
                ],
            ],
            'append - both empty;' => [
                [
                    'a' => [],
                ],
                [
                    'a' => [],
                ],
                [
                    [
                        'type' => 'append',
                        'config' => [
                            'parents' => ['a'],
                            'items' => [],
                        ],
                    ],
                ],
            ],
            'append - vector' => [
                [
                    'a' => [
                        'b',
                        'c',
                        'd',
                        'e',
                        'x',
                        'y',
                        'z',
                    ],
                ],
                [
                    'a' => [
                        'b',
                        'c',
                        'd',
                        'e',
                    ],
                ],
                [
                    [
                        'type' => 'append',
                        'config' => [
                            'parents' => ['a'],
                            'items' => ['x', 'y', 'z'],
                        ],
                    ],
                ],
            ],
            'append - vector; dst empty;' => [
                [
                    'a' => [
                        'x',
                        'y',
                        'z',
                    ],
                ],
                [
                    'a' => [],
                ],
                [
                    [
                        'type' => 'append',
                        'config' => [
                            'parents' => ['a'],
                            'items' => ['x', 'y', 'z'],
                        ],
                    ],
                ],
            ],
            'append - vector; items empty;' => [
                [
                    'a' => [
                        'x',
                        'y',
                        'z',
                    ],
                ],
                [
                    'a' => [
                        'x',
                        'y',
                        'z',
                    ],
                ],
                [
                    [
                        'type' => 'append',
                        'config' => [
                            'parents' => ['a'],
                            'items' => [],
                        ],
                    ],
                ],
            ],
            'append - vector; dst not exists;' => [
                [
                    'a' => ['b', 'c'],
                ],
                [],
                [
                    [
                        'type' => 'append',
                        'config' => [
                            'parents' => ['a'],
                            'items' => ['b', 'c'],
                        ],
                    ],
                ],
            ],
            'append - assoc' => [
                [
                    'a' => [
                        'b' => 'c',
                        'd' => 'e',
                        'x' => 'y',
                        'z' => '_',
                    ],
                ],
                [
                    'a' => [
                        'b' => 'c',
                        'd' => 'e',
                    ],
                ],
                [
                    [
                        'type' => 'append',
                        'config' => [
                            'parents' => ['a'],
                            'items' => [
                                'x' => 'y',
                                'z' => '_',
                                'd' => 'e+',
                            ],
                        ],
                    ],
                ],
            ],
            'append - assoc; dst empty;' => [
                [
                    'a' => [
                        'x' => 'y',
                        'z' => '_',
                        'd' => 'e+',
                    ],
                ],
                [
                    'a' => [],
                ],
                [
                    [
                        'type' => 'append',
                        'config' => [
                            'parents' => ['a'],
                            'items' => [
                                'x' => 'y',
                                'z' => '_',
                                'd' => 'e+',
                            ],
                        ],
                    ],
                ],
            ],
            'append - assoc; items empty;' => [
                [
                    'a' => [
                        'x' => 'y',
                        'z' => '_',
                        'd' => 'e+',
                    ],
                ],
                [
                    'a' => [
                        'x' => 'y',
                        'z' => '_',
                        'd' => 'e+',
                    ],
                ],
                [
                    [
                        'type' => 'prepend',
                        'config' => [
                            'parents' => ['a'],
                            'items' => [],
                        ],
                    ],
                ],
            ],
            'append - assoc; dst not exists;' => [
                [
                    'a' => [
                        'x' => 'y',
                        'z' => '_',
                        'd' => 'e+',
                    ],
                ],
                [],
                [
                    [
                        'type' => 'prepend',
                        'config' => [
                            'parents' => ['a'],
                            'items' => [
                                'x' => 'y',
                                'z' => '_',
                                'd' => 'e+',
                            ],
                        ],
                    ],
                ],
            ],
            'insertBefore - both empty;' => [
                [
                    'a' => [],
                ],
                [
                    'a' => [],
                ],
                [
                    [
                        'type' => 'insertBefore',
                        'config' => [
                            'parents' => ['a'],
                            'items' => [],
                        ],
                    ],
                ],
            ],
            'insertBefore - vector' => [
                [
                    'a' => [
                        'a',
                        'b',
                        'c',
                        'd',
                        'g',
                    ],
                ],
                [
                    'a' => [
                        'a',
                        'd',
                        'g',
                    ],
                ],
                [
                    [
                        'type' => 'insertBefore',
                        'config' => [
                            'parents' => ['a', 1],
                            'items' => [
                                'b',
                                'c',
                            ],
                        ],
                    ],
                ],
            ],
            'insertBefore - assoc' => [
                [
                    'a' => [
                        'a' => 1,
                        'b' => 2,
                        'c' => 3,
                        'd' => 4,
                        'g' => 7,
                    ],
                ],
                [
                    'a' => [
                        'a' => 1,
                        'd' => 4,
                        'g' => 7,
                    ],
                ],
                [
                    [
                        'type' => 'insertBefore',
                        'config' => [
                            'parents' => ['a', 'd'],
                            'items' => [
                                'b' => 2,
                                'c' => 3,
                            ],
                        ],
                    ],
                ],
            ],
            'insertAfter - both empty;' => [
                [
                    'a' => [],
                ],
                [
                    'a' => [],
                ],
                [
                    [
                        'type' => 'insertAfter',
                        'config' => [
                            'parents' => ['a'],
                            'items' => [],
                        ],
                    ],
                ],
            ],
            'insertAfter - vector' => [
                [
                    'a' => [
                        'a',
                        'd',
                        'e',
                        'f',
                        'g',
                    ],
                ],
                [
                    'a' => [
                        'a',
                        'd',
                        'g',
                    ],
                ],
                [
                    [
                        'type' => 'insertAfter',
                        'config' => [
                            'parents' => ['a', 1],
                            'items' => [
                                'e',
                                'f',
                            ],
                        ],
                    ],
                ],
            ],
            'insertAfter - vector; dst not exists' => [
                [
                    'b' => [],
                    'a' => [
                        'e',
                        'f',
                    ],
                ],
                [
                    'b' => [],
                ],
                [
                    [
                        'type' => 'insertAfter',
                        'config' => [
                            'parents' => ['a', 4],
                            'items' => [
                                'e',
                                'f',
                            ],
                        ],
                    ],
                ],
            ],
            'insertAfter - assoc' => [
                [
                    'a' => [
                        'a' => 1,
                        'd' => 4,
                        'e' => 5,
                        'f' => 6,
                        'g' => 7,
                    ],
                ],
                [
                    'a' => [
                        'a' => 1,
                        'd' => 4,
                        'g' => 7,
                    ],
                ],
                [
                    [
                        'type' => 'insertAfter',
                        'config' => [
                            'parents' => ['a', 'd'],
                            'items' => [
                                'e' => 5,
                                'f' => 6,
                            ],
                        ],
                    ],
                ],
            ],
            'insertAfter - assoc; dst not exists' => [
                [
                    'b' => [],
                    'a' => [
                        'e' => 5,
                        'f' => 6,
                    ],
                ],
                [
                    'b' => [],
                ],
                [
                    [
                        'type' => 'insertAfter',
                        'config' => [
                            'parents' => ['a', 'd'],
                            'items' => [
                                'e' => 5,
                                'f' => 6,
                            ],
                        ],
                    ],
                ],
            ],
            'sortNormal 1' => [
                [
                    'tags' => [
                        'a',
                        'b',
                        'c',
                    ],
                ],
                [
                    'tags' => [
                        'b',
                        'c',
                        'a',
                    ],
                ],
                [
                    [
                        'type' => 'sortNormal',
                        'config' => [
                            'parents' => ['tags'],
                            'function' => 'sort',
                        ],
                    ],
                ],
            ],
            'sortPackages no' => [
                [
                    'config' => [
                        'sort-packages' => false,
                    ],
                    'tags' => [
                        'a',
                        'b',
                        'c',
                    ],
                    'require' => [
                        'ext-a11' => '*',
                        'php' => '>=7.4',
                        'a/b' => '^1.0',
                        'ext-a2' => '*',
                    ],
                    'require-dev' => [
                        'ext-a11' => '*',
                        'php' => '>=7.4',
                        'a/b' => '^1.0',
                        'ext-a2' => '*',
                    ],
                ],
                [
                    'config' => [
                        'sort-packages' => false,
                    ],
                    'tags' => [
                        'b',
                        'c',
                        'a',
                    ],
                    'require' => [
                        'ext-a11' => '*',
                        'php' => '>=7.4',
                        'a/b' => '^1.0',
                        'ext-a2' => '*',
                    ],
                    'require-dev' => [
                        'ext-a11' => '*',
                        'php' => '>=7.4',
                        'a/b' => '^1.0',
                        'ext-a2' => '*',
                    ],
                ],
                [
                    [
                        'type' => 'sortNormal',
                        'config' => [
                            'parents' => ['tags'],
                            'function' => 'sort',
                        ],
                    ],
                ],
            ],
            'sortPackages yes' => [
                [
                    'config' => [
                        'sort-packages' => true,
                    ],
                    'tags' => [
                        'a',
                        'b',
                        'c',
                    ],
                    'require' => [
                        'php' => '>=7.4',
                        'ext-a2' => '*',
                        'ext-a11' => '*',
                        'a/b' => '^1.0',
                    ],
                    'require-dev' => [
                        'php' => '>=7.4',
                        'ext-a2' => '*',
                        'ext-a11' => '*',
                        'a/b' => '^1.0',
                    ],
                ],
                [
                    'config' => [
                        'sort-packages' => true,
                    ],
                    'tags' => [
                        'b',
                        'c',
                        'a',
                    ],
                    'require' => [
                        'ext-a11' => '*',
                        'php' => '>=7.4',
                        'a/b' => '^1.0',
                        'ext-a2' => '*',
                    ],
                    'require-dev' => [
                        'ext-a11' => '*',
                        'php' => '>=7.4',
                        'a/b' => '^1.0',
                        'ext-a2' => '*',
                    ],
                ],
                [
                    [
                        'type' => 'sortNormal',
                        'config' => [
                            'parents' => ['tags'],
                            'function' => 'sort',
                        ],
                    ],
                ],
            ],
            // @todo Add more tests with multiple actions.
        ];
    }

    /**
     * @dataProvider casesGenerateSuccess
     */
    public function testGenerateSuccess(array $expected, array $rootData, array $actions)
    {
        $suiteHandler = new SuiteHandler();
        $this->tester->assertSame($expected, $suiteHandler->generate($rootData, $actions));
    }

    public function testGenerateFailInvalidSortFunction()
    {
        $rootData = [
            'tags' => [
                'a',
                'b',
            ],
        ];
        $actions = [
            [
                'type' => 'sortNormal',
                'config' => [
                    'parents' => ['tags'],
                    'function' => 'my_not_exists',
                ],
            ],
        ];
        $suiteHandler = new SuiteHandler();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1);
        $this->expectExceptionMessage(
            'invalid sortNormal function: my_not_exists; allowed values: '
            . implode(', ', [
                'asort',
                'arsort',
                'krsort',
                'ksort',
                'natcasesort',
                'natsort',
                'rsort',
                'shuffle',
                'sort',
            ]),
        );
        $suiteHandler->generate($rootData, $actions);
    }

    public function testGenerateFailUnknownAction()
    {
        $this->tester->expectThrowable(
            \AssertionError::class,
            function () {
                $suiteHandler = new SuiteHandler();
                $suiteHandler->generate(
                    [],
                    [
                        [
                            'type' => 'unknown',
                        ]
                    ],
                );
            },
        );
    }

    public function casesWhatToDo(): array
    {
        return [
            'create' => [
                'create',
                [],
                'composer.foo.json',
                [
                    'name' => 'a/b',
                ],
            ],
            'update' => [
                'update',
                [
                    'composer.foo.json' => implode("\n", [
                        '{',
                        '    "name": "a/c"',
                        '}',
                    ]),
                ],
                'composer.foo.json',
                [
                    'name' => 'a/b',
                ],
            ],
            'skip' => [
                'skip',
                [
                    'composer.foo.json' => implode("\n", [
                        '{',
                        '    "name": "a/b"',
                        '}',
                    ]),
                ],
                'composer.foo.json',
                [
                    'name' => 'a/b',
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesWhatToDo
     */
    public function testWhatToDo(string $expected, array $vfsStructure, string $fileName, array $dataNew): void
    {
        $vfs = vfsStream::setup(
            'root',
            0777,
            [
                __FUNCTION__ => $vfsStructure,
            ],
        );

        $fileName = $vfs->url() . '/' . __FUNCTION__ . '/' . $fileName;

        $suiteHandler = new SuiteHandler();
        $this->tester->assertSame($expected, $suiteHandler->whatToDo($fileName, $dataNew));
    }

    public function testCollectSuiteComposerFiles()
    {
        $expected = [
            'composer.a.json' => 'a',
            'composer.b.json' => 'b',
            'composer.c.json' => 'c',
        ];
        $composerFile = 'composer.json';
        $vfsStructure = [
            'composer.json' => 'default',
            'composer.a.json' => 'a',
            'composer.b.json' => 'b',
            'composer.c.json' => 'c',
            'other.d.json' => 'd',
        ];

        $vfs = vfsStream::setup(
            __FUNCTION__,
            0777,
            $vfsStructure,
        );

        $composerFile = $vfs->url() . '/' . $composerFile;

        $suiteHandler = new SuiteHandler();
        $this->tester->assertSame($expected, $suiteHandler->collectSuiteComposerFiles($composerFile));
    }

    public function casesCollectSuiteDefinitions(): array
    {
        return [
            'with .composer-suite dir' => [
                [
                    'one' => [
                        'source' => './composer.json#/extra/composer-suite/one',
                        'name' => 'one',
                        'description' => '',
                        'actions' => [
                            [
                                'type' => 'replaceRecursive',
                                'config' => [
                                    'parents' => [],
                                    'items' => [],
                                ],
                            ],
                        ],
                    ],
                    'two-inner' => [
                        'source' => 'vfs://root/testCollectSuiteDefinitions/.composer-suite/composer-suite.two.json',
                        'name' => 'two-inner',
                        'description' => '',
                    ],
                    'three' => [
                        'source' => 'vfs://root/testCollectSuiteDefinitions/.composer-suite/composer-suite.three.json',
                        'name' => 'three',
                        'description' => '',
                    ],
                    'four' => [
                        'source' => 'vfs://root/testCollectSuiteDefinitions/.composer-suite/composer-suite.four.json',
                        'name' => 'four',
                        'description' => '',
                    ],
                ],
                [
                    '.composer-suite' => [
                        'composer-suite.two.json' => json_encode([
                            'name' => 'two-inner',
                        ]),
                        'composer-suite.three.json' => json_encode([
                            'description' => '',
                        ]),
                        'composer-suite.four.json' => json_encode([
                            'name' => 'four',
                        ]),
                    ],
                    'composer.json' => '{}',
                ],
                [
                    'composer-suite' => [
                        'one' => [
                            'actions' => [
                                [
                                    'type' => 'replaceRecursive',
                                    'config' => [
                                        'parents' => [],
                                        'items' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'without .composer-suite dir' => [
                [
                    'one' => [
                        'source' => './composer.json#/extra/composer-suite/one',
                        'name' => 'one',
                        'description' => '',
                        'actions' => [
                            [
                                'type' => 'replaceRecursive',
                                'config' => [
                                    'parents' => [],
                                    'items' => [],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'composer.json' => '{}',
                ],
                [
                    'composer-suite' => [
                        'one' => [
                            'actions' => [
                                [
                                    'type' => 'replaceRecursive',
                                    'config' => [
                                        'parents' => [],
                                        'items' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesCollectSuiteDefinitions
     */
    public function testCollectSuiteDefinitions($expected, array $vfsStructure, array $extra): void
    {
        $vfs = vfsStream::setup(
            'root',
            0777,
            [
                __FUNCTION__ => $vfsStructure,
            ],
        );

        $composerFileName = $vfs->url() . '/' . __FUNCTION__ . '/composer.json';
        $suiteHandler = new SuiteHandler();
        $this->tester->assertEquals(
            $expected,
            $suiteHandler->collectSuiteDefinitions($composerFileName, $extra),
        );
    }
}
