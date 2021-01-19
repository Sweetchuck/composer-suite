<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerSuite;

use Composer\Repository\PlatformRepository;

/**
 * @see \Composer\Json\JsonManipulator::sortPackages
 */
class RequirementComparer
{

    public function __invoke($a, $b): int
    {
        return $this->compare($a, $b);
    }

    public function compare($a, $b): int
    {
        return strnatcmp($this->prefix($a), $this->prefix($b));
    }

    protected function prefix(string $requirement): string
    {
        if (PlatformRepository::isPlatformPackage($requirement)) {
            return preg_replace(
                [
                    '/^php/',
                    '/^hhvm/',
                    '/^ext/',
                    '/^lib/',
                    '/^\D/',
                ],
                [
                    '0-$0',
                    '1-$0',
                    '2-$0',
                    '3-$0',
                    '4-$0',
                ],
                $requirement,
            );
        }

        return "5-$requirement";
    }
}
