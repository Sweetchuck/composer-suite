<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerSuite\Test\Unit\Composer\Command;

use Sweetchuck\ComposerSuite\Test\Unit\TestBase;

class CommandTestBase extends TestBase
{

    protected array $envVars = [];

    /**
     * {@inheritdoc}
     */
    protected function _before()
    {
        parent::_before();
        $this->envVarBackup();
    }

    /**
     * {@inheritdoc}
     */
    protected function _after()
    {
        $this->envVarRestore();
        parent::_after();
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
