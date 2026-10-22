<?php

declare(strict_types=1);

namespace ShortURL\Application\UseCase;

use ShortURL\Application\Port\ShortLinkRepository;
use ShortURL\Domain\ShortLink;

final readonly class ListShortLinks
{
    public function __construct(private ShortLinkRepository $links) {}

    /**
     * @return list<ShortLink>
     */
    public function execute(): array
    {
        return $this->links->findAll();
    }
}
