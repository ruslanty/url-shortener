<?php

declare(strict_types=1);

namespace ShortURL\Tests\Integration;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PHPUnit\Framework\TestCase;
use ShortURL\Adapter\Messaging\ClickWorker;
use ShortURL\Adapter\Persistence\CyclePersistence;
use ShortURL\Adapter\Persistence\CycleShortLinkRepository;
use ShortURL\Application\UseCase\CreateShortLink;
use ShortURL\Application\UseCase\GetClickStats;
use ShortURL\Application\UseCase\RedirectShortLink;

final class ClickFlowTest extends TestCase
{
    public function testRedirectIsRecordedAsynchronouslyAndCanBeDeliveredMoreThanOnce(): void
    {
        $persistence = CyclePersistence::fromEnvironment();
        $database = $persistence->databases()->database('default');
        $repository = new CycleShortLinkRepository($persistence->orm(), $persistence->entityManager(), $database);

        $link = new CreateShortLink($repository)
            ->execute('https://element.id.lv');

        $connection = $this->connectToRabbitMq();
        $channel = $connection->channel();

        try {
            $worker = new ClickWorker($repository, $channel);

            new RedirectShortLink($repository)->execute($link->code);

            $worker->runOneCycle();

            $stats = new GetClickStats($repository)->execute($link->code);

            self::assertNotNull($stats);
            self::assertSame(1, $stats['clickCount']);

            $query = <<<'SQL'
                UPDATE click_outbox
                SET published_at = NULL
                WHERE short_code = ?
            SQL;

            $database->execute($query, [
                $link->code,
            ]);

            $worker->runOneCycle();

            $stats = new GetClickStats($repository)->execute($link->code);

            self::assertNotNull($stats);
            self::assertSame(1, $stats['clickCount']);
        } finally {
            $query = <<<'SQL'
                DELETE FROM short_links
                WHERE code = ?
            SQL;

            $database->execute($query, [
                $link->code,
            ]);

            $channel->close();
            $connection->close();
        }
    }

    private function connectToRabbitMq(): AMQPStreamConnection
    {
        return new AMQPStreamConnection(
            getenv('AMQP_HOST') ?: 'rabbitmq', // @phpstan-ignore ternary.shortNotAllowed
            5672,
            getenv('AMQP_USER') ?: 'user', // @phpstan-ignore ternary.shortNotAllowed
            getenv('AMQP_PASSWORD') ?: 'password', // @phpstan-ignore ternary.shortNotAllowed
        );
    }
}
