<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerSuite\Tests\Unit;

use Sweetchuck\ComposerSuite\SuiteHandler;

/**
 * @covers \Sweetchuck\ComposerSuite\SuiteHandler
 */
class SuiteHandlerTest extends TestBase
{

    public function casesGenerate(): array
    {
        $rootData = [
            'type' => 'my-type',
            'name' => 'my/name',
            'description' => 'my description',
            'tags' => [
                'd',
            ],
            'authors' => [
                [
                    'a' => 'a',
                ],
                [
                    'c' => 'c',
                ],
                [
                    'e' => 'e',
                ],
            ],
            'repositories' => [],
            'require' => [
                'a/a' => '^2.0',
                'a/b' => '^1.0',
                'a/c' => '^3.0',
            ],
            'extra' => [
                'a' => 'b',
                'composer-suite' => [
                    'not in use' => 'see $actions parameter',
                ],
                'c' => 'd',
                'remove-me-1' => 1,
                'remove-me-2' => 2,
            ],
            'remove-me-3' => 3,
        ];

        return [
            'all-in-one' => [
                [
                    'type' => 'my-type',
                    'name' => 'my/name',
                    'description' => 'my description',
                    'tags' => [
                        "a",
                        "b",
                        "d",
                        "y",
                        "z",
                    ],
                    'authors' => [
                        [
                            'a' => 'a',
                        ],
                        [
                            'b' => 'b',
                        ],
                        [
                            'c' => 'c',
                        ],
                        [
                            'd' => 'd',
                        ],
                        [
                            'e' => 'e',
                        ],
                    ],
                    'repositories' => [
                        'a/b' => [
                            'type' => 'path',
                            'url' => '../../a/b-1.x-dev',
                        ],
                    ],
                    'require' => [
                        'a/a' => '^2.0',
                        'a/e1' => '^2.0',
                        'a/e2' => '^2.0',
                        'a/b' => '1.x-dev',
                        'a/c' => '^3.0',
                    ],
                    'extra' => [
                        'a' => 'b',
                        'c' => 'd',
                    ],
                ],
                $rootData,
                [
                    [
                        'type' => 'replaceRecursive',
                        'config' => [
                            'parents' => [],
                            'items' => [
                                'repositories' => [
                                    'a/b' => [
                                        'type' => 'path',
                                        'url' => '../../a/b-1.x-dev',
                                    ],
                                ],
                                'require' => [
                                    'a/b' => '1.x-dev',
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'unset',
                        'config' => [
                            'parents' => [
                                'extra',
                                [
                                    'remove-me-1',
                                    'remove-me-2',
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'unset',
                        'config' => [
                            'parents' => [
                                'remove-me-3',
                            ],
                        ],
                    ],
                    [
                        'type' => 'append',
                        'config' => [
                            'parents' => [
                                'tags',
                            ],
                            'items' => [
                                'y',
                                'z',
                            ],
                        ],
                    ],
                    [
                        'type' => 'prepend',
                        'config' => [
                            'parents' => [
                                'tags',
                            ],
                            'items' => [
                                'a',
                                'b',
                            ],
                        ],
                    ],
                    [
                        'type' => 'insertBefore',
                        'config' => [
                            'parents' => [
                                'authors',
                                1,
                            ],
                            'items' => [
                                [
                                    'b' => 'b',
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'insertAfter',
                        'config' => [
                            'parents' => [
                                'authors',
                                2,
                            ],
                            'items' => [
                                [
                                    'd' => 'd',
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'insertBefore',
                        'config' => [
                            'parents' => [
                                'require',
                                'a/b',
                            ],
                            'items' => [
                                'a/e2' => '^2.0',
                            ],
                        ],
                    ],
                    [
                        'type' => 'insertAfter',
                        'config' => [
                            'parents' => [
                                'require',
                                'a/a',
                            ],
                            'items' => [
                                'a/e1' => '^2.0',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesGenerate
     */
    public function testGenerate(array $expected, array $rootData, array $actions)
    {
        $suiteHandler = new SuiteHandler();
        $this->tester->assertEquals($expected, $suiteHandler->generate($rootData, $actions));
    }
}
