<?php

declare(strict_types=1);

namespace ShortURL\Adapter\Persistence;

use Cycle\Database\DatabaseInterface;
use Cycle\ORM\EntityManager;
use Cycle\ORM\ORM;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use ShortURL\Application\Port\ShortLinkRepository;
use ShortURL\Domain\ClickEvent;
use ShortURL\Domain\ShortLink;
use UnexpectedValueException;

final readonly class CycleShortLinkRepository implements ShortLinkRepository
{
    public function __construct(
        private ORM $orm,
        private EntityManager $entityManager,
        private DatabaseInterface $database,
    ) {}

    public function save(ShortLink $link): void
    {
        $record = new ShortLinkRecord();
        $record->code = $link->code;
        $record->destinationUrl = $link->destinationUrl;
        $record->createdAt = $link->createdAt->format(DateTimeImmutable::ATOM);

        $this->entityManager->persist($record)->run();
    }

    public function find(string $code): ?ShortLink
    {
        $record = $this->orm->getRepository(ShortLinkRecord::class)
            ->findByPK($code);

        if (! $record instanceof ShortLinkRecord) {
            return null;
        }

        return new ShortLink(
            $record->code,
            $record->destinationUrl,
            new DateTimeImmutable($record->createdAt),
        );
    }

    /**
     * @return list<ShortLink>
     * @throws \DateMalformedStringException
     */
    public function findAll(): array
    {
        $records = $this->orm->getRepository(ShortLinkRecord::class)
            ->findAll();

        $links = [];

        foreach ($records as $record) {
            if (! $record instanceof ShortLinkRecord) {
                throw new UnexpectedValueException('Invalid record.');
            }

            $links[] = new ShortLink(
                $record->code,
                $record->destinationUrl,
                new DateTimeImmutable($record->createdAt),
            );
        }

        return $links;
    }

    /**
     * @throws \Throwable
     */
    public function recordRedirect(string $code): ?ShortLink
    {
        return $this->database->transaction(function (DatabaseInterface $_database) use ($code): ?ShortLink {
            $link = $this->find($code);

            if (! $link instanceof ShortLink) {
                return null;
            }

            $event = new ClickEvent(
                Uuid::uuid7()->toString(),
                $code,
                new DateTimeImmutable(),
            );

            $this->database->insert('click_outbox')->values([
                'event_id' => $event->id,
                'short_code' => $event->code,
                'occurred_at' => $event->occurredAt->format(DateTimeImmutable::ATOM),
            ])->run();

            return $link;
        });
    }

    public function countClicks(string $code): int
    {
        $query = <<<'SQL'
            SELECT COUNT(1) AS click_count
            FROM link_clicks
            WHERE short_code = ?
        SQL;

        $rows = $this->database->query($query, [
            $code,
        ])->fetchAll();

        $row = $rows[0] ?? null;

        if (! is_array($row)) {
            throw new UnexpectedValueException('Invalid row count.');
        }

        $value = $row['click_count'] ?? null;

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value)
            && ctype_digit($value)
        ) {
            return (int) $value;
        }

        throw new UnexpectedValueException('Invalid count.');
    }

    public function nextPendingEvent(): ?ClickEvent
    {
        $query = <<<'SQL'
            SELECT event_id,
                short_code,
                occurred_at
            FROM click_outbox
            WHERE published_at IS NULL
            ORDER BY occurred_at,
                event_id
            LIMIT 1
        SQL;

        $rows = $this->database->query($query)
            ->fetchAll();

        $row = $rows[0] ?? null;

        if (! is_array($row)) {
            return null;
        }

        foreach (['event_id', 'short_code', 'occurred_at'] as $column) {
            if (! is_string($row[$column] ?? null)) {
                throw new UnexpectedValueException(sprintf('Invalid %s value.', $column));
            }
        }

        return new ClickEvent(
            $row['event_id'],
            $row['short_code'],
            new DateTimeImmutable($row['occurred_at']),
        );
    }

    public function markPublished(string $eventId): void
    {
        $query = <<<'SQL'
            UPDATE click_outbox
            SET published_at = NOW()
            WHERE event_id = ?
                AND published_at IS NULL
        SQL;

        $this->database->execute($query, [
            $eventId,
        ]);
    }

    public function recordClick(ClickEvent $event): void
    {
        $query = <<<'SQL'
            INSERT INTO link_clicks (
                event_id,
                short_code,
                occurred_at
            )
            VALUES (?, ?, ?)
            ON CONFLICT (event_id) DO NOTHING
        SQL;

        $this->database->execute($query, [
            $event->id,
            $event->code,
            $event->occurredAt->format(DateTimeImmutable::ATOM),
        ]);
    }
}
