<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerSuite\Test\Unit\Composer\Command;

use Sweetchuck\ComposerSuite\Test\Unit\TestBase;
use Symfony\Component\Filesystem\Filesystem;

class CommandTestBase extends TestBase
{

    protected array $envVars = [];

    /**
     * @var array<string>
     */
    protected array $fsEntriesToRemove = [];

    protected Filesystem $fs;

    /**
     * @return void
     */
    protected function _before()
    {
        parent::_before();
        $this->fs = new Filesystem();
        $this->envVarBackup();
    }

    /**
     * @return void
     */
    protected function _after()
    {
        $this->fs->remove($this->fsEntriesToRemove);
        $this->envVarRestore();
        parent::_after();
    }

    /**
     * In the background Composer uses \realpath(), which doesn't work
     * together with vfs://.
     */
    protected function createTmpDir(): string
    {
        $name = $this->fs->tempnam(sys_get_temp_dir(), 'composer-suite-');
        $this->fs->remove($name);
        $this->fs->mkdir($name);
        $this->fsEntriesToRemove[] = $name;

        return $name;
    }

    /**
     * @return $this
     */
    protected function envVarBackup()
    {
        $this->envVars = getenv();

        return $this;
    }

    /**
     * @return $this
     */
    protected function envVarRestore()
    {
        $extra = array_diff_key(getenv(), $this->envVars);
        foreach (array_keys($extra) as $key) {
            putenv($key);
        }

        foreach ($this->envVars as $key => $value) {
            putenv("$key=$value");
        }

        $this->envVars = [];

        return $this;
    }
}
