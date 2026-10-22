<?php

declare(strict_types=1);

use Mezzio\Application;

require dirname(__DIR__) . '/vendor/autoload.php';

$container = require dirname(__DIR__) . '/config/container.php';
$app = $container->get(Application::class);

(require dirname(__DIR__) . '/config/pipeline.php')($app, $container);
(require dirname(__DIR__) . '/config/routes.php')($app);

$app->run();
