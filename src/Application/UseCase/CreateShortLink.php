<?php

declare(strict_types=1);

namespace ShortURL\Application\UseCase;

use DateTimeImmutable;
use ShortURL\Application\Port\ShortLinkRepository;
use ShortURL\Domain\ShortLink;

final readonly class CreateShortLink
{
    public function __construct(private ShortLinkRepository $links) {}

    public function execute(string $destination): ShortLink
    {
        // $code = rtrim(strtr(base64_encode(random_bytes(6)), '+/', '-_'), '=');
        $code = 6
                |> random_bytes(...)
                |> base64_encode(...)
                |> (static fn($x): string => strtr($x, '+/', '-_'))
                |> (static fn($x): string => rtrim($x, '='));

        $link = new ShortLink($code, $destination, new DateTimeImmutable());

        $this->links->save($link);

        return $link;
    }
}
