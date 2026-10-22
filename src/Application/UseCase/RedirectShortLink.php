<?php

declare(strict_types=1);

namespace ShortURL\Application\UseCase;

use ShortURL\Application\Port\ShortLinkRepository;
use ShortURL\Domain\ShortLink;

final readonly class RedirectShortLink
{
    public function __construct(private ShortLinkRepository $links) {}

    public function execute(string $code): ?ShortLink
    {
        return $this->links->recordRedirect($code);
    }
}
