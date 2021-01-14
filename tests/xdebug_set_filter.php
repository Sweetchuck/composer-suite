<?php

declare(strict_types = 1);

if (extension_loaded('xdebug')) {
    xdebug_set_filter(
        \XDEBUG_FILTER_CODE_COVERAGE,
        \XDEBUG_PATH_WHITELIST,
        [
            dirname(__DIR__) . '/src',
        ]
    );
}
