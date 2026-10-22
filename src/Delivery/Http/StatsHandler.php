<?php

declare(strict_types=1);

namespace ShortURL\Delivery\Http;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ShortURL\Application\UseCase\GetClickStats;
use ShortURL\Domain\ShortLink;

final readonly class StatsHandler implements RequestHandlerInterface
{
    public function __construct(private GetClickStats $getClickStats) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $value = $request->getAttribute('code');
        $code = is_string($value)
            ? $value
            : '';

        if (! ShortLink::hasValidCode($code)) {
            return new JsonResponse([
                'error' => 'Short link not found.',
            ], 404);
        }

        $stats = $this->getClickStats->execute($code);

        if ($stats === null) {
            return new JsonResponse([
                'error' => 'Short link not found.',
            ], 404);
        }

        return new JsonResponse([
            'code' => $code,
            'destinationUrl' => $stats['link']->destinationUrl,
            'clicks' => $stats['clickCount'],
        ]);
    }
}
