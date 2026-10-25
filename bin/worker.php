<?php

declare(strict_types=1);

use PhpAmqpLib\Connection\AMQPStreamConnection;
use ShortURL\Adapter\Messaging\ClickWorker;
use ShortURL\Adapter\Persistence\CyclePersistence;
use ShortURL\Adapter\Persistence\CycleShortLinkRepository;

require __DIR__ . '/../vendor/autoload.php';

$connection = new AMQPStreamConnection(
    getenv('AMQP_HOST') ?: 'rabbitmq', // @phpstan-ignore ternary.shortNotAllowed
    5672,
    getenv('AMQP_USER') ?: 'user', // @phpstan-ignore ternary.shortNotAllowed
    getenv('AMQP_PASSWORD') ?: 'password', // @phpstan-ignore ternary.shortNotAllowed
);

$channel = $connection->channel();
$persistence = CyclePersistence::fromEnvironment();
$database = $persistence->databases()->database('default');
$repository = new CycleShortLinkRepository($persistence->orm(), $persistence->entityManager(), $database);
$worker = new ClickWorker($repository, $channel);

$worker->runForever();
