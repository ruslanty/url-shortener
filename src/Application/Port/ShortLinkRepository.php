<?php

declare(strict_types=1);

namespace ShortURL\Application\Port;

use ShortURL\Domain\ClickEvent;
use ShortURL\Domain\ShortLink;

interface ShortLinkRepository
{
    public function save(ShortLink $link): void;

    public function find(string $code): ?ShortLink;

    /**
     * @return list<ShortLink>
     */
    public function findAll(): array;

    /** Atomically finds the link and writes its click event to the outbox. */
    public function recordRedirect(string $code): ?ShortLink;

    public function countClicks(string $code): int;

    public function nextPendingEvent(): ?ClickEvent;

    public function markPublished(string $eventId): void;

    public function recordClick(ClickEvent $event): void;
}
