<?php

declare(strict_types=1);

namespace ShortURL\Application\UseCase;

use ShortURL\Application\Port\ShortLinkRepository;
use ShortURL\Domain\ShortLink;

final readonly class GetClickStats
{
    public function __construct(private ShortLinkRepository $links) {}

    /**
     * @return array{link: ShortLink, clickCount: int}|null
     */
    public function execute(string $code): ?array
    {
        $link = $this->links->find($code);

        if (! $link instanceof ShortLink) {
            return null;
        }

        return [
            'link' => $link,
            'clickCount' => $this->links->countClicks($code),
        ];
    }
}
