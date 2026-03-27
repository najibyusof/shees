<?php

$modules = [];

foreach (glob(__DIR__.'/report_builder_modules/*.php') ?: [] as $moduleFile) {
    $moduleConfig = require $moduleFile;

    if (is_array($moduleConfig)) {
        $modules = array_merge($modules, $moduleConfig);
    }
}

return [
    'modules' => $modules,
];
