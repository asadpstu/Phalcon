<?php

$loader = new \Phalcon\Loader();

/**
 * We're a registering a set of directories taken from the configuration file
 */
$loader->registerDirs(
    [
        $config->application->controllersDir,
        $config->application->modelsDir
    ]
);

// Register some classes
$loader->registerClasses(
    [
        'Mail' => APP_PATH. '/library/Mail/Mail.php',
    ]
);

// Register autoloader
$loader->register();
