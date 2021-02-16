<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerSuite\Tests\Acceptance\Composer\Command;

use Sweetchuck\ComposerSuite\Test\AcceptanceTester;
use Symfony\Component\Filesystem\Filesystem;

class ValidateCest
{
    protected string $projectRoot = '';

    protected Filesystem $fs;

    protected array $validComposerJson = [
        'type' =>'composer-plugin',
        'name' =>'my/p01',
        'description' =>'@todo project description',
        'license' =>'GPL-3.0-or-later',
        'minimum-stability' => 'dev',
        'prefer-stable' => true,
        'config' => [
            'preferred-install' => "dist",
            'optimize-autoloader' => true,
            'sort-packages' => true
        ],
        'require-dev' => [
            'sweetchuck/composer-suite' => "*",
        ],
    ];

    public function _before()
    {
        $this->projectRoot = tempnam(sys_get_temp_dir(), 'composer-suite-');
        $this->fs = new Filesystem();
        $this->fs->remove($this->projectRoot);
        $this->fs->mkdir($this->projectRoot);
    }

    public function _after()
    {
        if ($this->fs->exists($this->projectRoot)) {
            $this->fs->remove($this->projectRoot);
        }
    }

    public function runComposerValidate(AcceptanceTester $I)
    {
        $baseData = $this->validComposerJson;
        $baseData['repositories'][] = [
            'type' => 'path',
            'url' => $this->selfProjectRoot(),
        ];
        $baseData['extra'] = [
            'composer-suite' => [
                'one' => [
                    [
                        'type' => 'replaceRecursive',
                        'config' => [
                            'parents' => [],
                            'items' => ['scripts' => []],
                        ],
                    ],
                ],
                'two' => [
                    [
                        'type' => 'replaceRecursive',
                        'config' => [
                            'parents' => [],
                            'items' => ['scripts' => []],
                        ],
                    ],
                ],
                'three' => [
                    [
                        'type' => 'replaceRecursive',
                        'config' => [
                            'parents' => [],
                            'items' => ['scripts' => []],
                        ],
                    ],
                ],
            ],
        ];

        $jsonEncodeFlags = \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_PRETTY_PRINT;

        $baseString = json_encode($baseData, $jsonEncodeFlags);

        $threeData = $baseData;
        unset($threeData['extra']['composer-suite']);
        $threeData['scripts'] = [];
        $threeString = json_encode($threeData, $jsonEncodeFlags);

        $prSafe = escapeshellarg($this->projectRoot);

        $I->wantTo('test that "validate" can detect the valid/missing/outdated/unnecessary *.json files');
        $I->writeToFile("{$this->projectRoot}/composer.json", "$baseString\n");
        $I->writeToFile("{$this->projectRoot}/composer.two.json", "$baseString\n");
        $I->writeToFile("{$this->projectRoot}/composer.three.json", "$threeString\n");
        $I->writeToFile("{$this->projectRoot}/composer.four.json", "{}\n");
        $I->runShellCommand("cd $prSafe && COMPOSER_HOME=/dev/null composer update");
        $I->runShellCommand("cd $prSafe && COMPOSER_HOME=/dev/null composer validate 2>&1", false);
        $I->canSeeResultCodeIs(1);
        $I->seeInShellOutput('composer-suite - ./composer.four.json exists, but not defined');
        $I->seeInShellOutput('composer-suite - ./composer.one.json is not exists');
        $I->seeInShellOutput('composer-suite - ./composer.two.json is not up to date');
        $I->dontSeeInShellOutput('composer.three.json');

        $I->deleteFile("{$this->projectRoot}/composer.four.json");
        $I->writeToFile("{$this->projectRoot}/composer.one.json", "$threeString\n");
        $I->writeToFile("{$this->projectRoot}/composer.two.json", "$threeString\n");
        $I->runShellCommand("cd $prSafe && composer validate 2>&1");

        $I->deleteFile("{$this->projectRoot}/composer.one.json");
        $I->deleteFile("{$this->projectRoot}/composer.two.json");
        $I->deleteFile("{$this->projectRoot}/composer.three.json");
        $I->runShellCommand("cd $prSafe && composer suite:generate");
        $I->runShellCommand("cd $prSafe && composer validate");
    }

    protected function selfProjectRoot(): string
    {
        return dirname(__DIR__, 4);
    }
}
