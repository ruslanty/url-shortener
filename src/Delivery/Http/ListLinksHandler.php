<?php

declare(strict_types=1);

namespace ShortURL\Delivery\Http;

use DateTimeInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ShortURL\Application\UseCase\ListShortLinks;
use ShortURL\Domain\ShortLink;

final readonly class ListLinksHandler implements RequestHandlerInterface
{
    public function __construct(private ListShortLinks $listShortLinks) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $links = array_map(
            static fn(ShortLink $link): array => [
                'code' => $link->code,
                'url' => '/' . $link->code,
                'destinationUrl' => $link->destinationUrl,
                'createdAt' => $link->createdAt->format(DateTimeInterface::ATOM),
            ],
            $this->listShortLinks->execute(),
        );

        return new JsonResponse([
            'links' => $links,
        ]);
    }
}
