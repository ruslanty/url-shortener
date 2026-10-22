<?php

declare(strict_types=1);

namespace ShortURL\Domain;

use DateTimeImmutable;

final readonly class ClickEvent
{
    public function __construct(
        public string $id,
        public string $code,
        public DateTimeImmutable $occurredAt,
    ) {}
}
