<?php

declare(strict_types=1);

namespace ShortURL\Delivery\Http;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ShortURL\Application\UseCase\RedirectShortLink;
use ShortURL\Domain\ShortLink;

final readonly class RedirectHandler implements RequestHandlerInterface
{
    public function __construct(private RedirectShortLink $redirectLink) {}

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

        $link = $this->redirectLink->execute($code);

        if (! $link instanceof ShortLink) {
            return new JsonResponse([
                'error' => 'Short link not found.',
            ], 404);
        }

        return new Response()->withStatus(302)
            ->withHeader('Location', $link->destinationUrl);
    }
}
