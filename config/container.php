<?php

declare(strict_types=1);

use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ArrayUtils;
use Mezzio\ConfigProvider as MezzioConfigProvider;
use Mezzio\Router\ConfigProvider as RouterConfigProvider;
use Mezzio\Router\FastRouteRouter\ConfigProvider as FastRouteConfigProvider;
use ShortURL\ConfigProvider;

$aggregator = new ConfigAggregator([
    MezzioConfigProvider::class,
    RouterConfigProvider::class,
    FastRouteConfigProvider::class,
    Laminas\Diactoros\ConfigProvider::class,
    ConfigProvider::class,
]);

$config = $aggregator->getMergedConfig();

if (! is_array($config['dependencies'] ?? null)) {
    throw new RuntimeException('Invalid application dependencies.');
}

/** @var array<string, mixed> $baseDependencies */
$baseDependencies = $config['dependencies'];

$dependencies = ArrayUtils::merge($baseDependencies, [
    'services' => [
        'config' => $config,
    ],
]);

return new ServiceManager($dependencies); // @phpstan-ignore argument.type (merged from framework ConfigProviders)
