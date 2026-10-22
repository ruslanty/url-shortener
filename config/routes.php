<?php

declare(strict_types=1);

use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Application;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ShortURL\Delivery\Http\CreateLinkHandler;
use ShortURL\Delivery\Http\ListLinksHandler;
use ShortURL\Delivery\Http\RedirectHandler;
use ShortURL\Delivery\Http\StatsHandler;

return static function (Application $app): void {
    $app->get('/', static fn(
        ServerRequestInterface $_request,
        RequestHandlerInterface $_handler,
    ): ResponseInterface => new JsonResponse(['status' => 'ok']), 'health');

    $app->get('/api/links', ListLinksHandler::class, 'links.list');
    $app->post('/api/links', CreateLinkHandler::class, 'links.create');

    $app->get('/{code}', RedirectHandler::class, 'links.redirect');
    $app->get('/{code}/stats', StatsHandler::class, 'links.stats');
};
