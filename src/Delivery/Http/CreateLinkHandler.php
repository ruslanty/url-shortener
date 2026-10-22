<?php

declare(strict_types=1);

namespace ShortURL\Delivery\Http;

use InvalidArgumentException;
use JsonException;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ShortURL\Application\UseCase\CreateShortLink;

final readonly class CreateLinkHandler implements RequestHandlerInterface
{
    public function __construct(private CreateShortLink $createLink) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $payload = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return new JsonResponse([
                'error' => 'Invalid JSON request body.',
            ], 400);
        }

        $url = is_array($payload)
            ? ($payload['url'] ?? null)
            : null;

        if (! is_string($url)) {
            return new JsonResponse([
                'error' => 'Please provide URL string.',
            ], 400);
        }

        try {
            $link = $this->createLink->execute($url);
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse([
                'error' => $exception->getMessage(),
            ], 422);
        }

        return new JsonResponse([
            'code' => $link->code,
            'url' => '/' . $link->code,
            'destinationUrl' => $link->destinationUrl,
        ], 201);
    }
}
