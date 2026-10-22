<?php

declare(strict_types=1);

namespace ShortURL;

use Cycle\Database\DatabaseInterface;
use Cycle\ORM\EntityManager;
use Cycle\ORM\ORM;
use LogicException;
use Psr\Container\ContainerInterface;
use ShortURL\Adapter\Persistence\CyclePersistence;
use ShortURL\Adapter\Persistence\CycleShortLinkRepository;
use ShortURL\Application\Port\ShortLinkRepository;
use ShortURL\Application\UseCase\CreateShortLink;
use ShortURL\Application\UseCase\GetClickStats;
use ShortURL\Application\UseCase\ListShortLinks;
use ShortURL\Application\UseCase\RedirectShortLink;
use ShortURL\Delivery\Http\CreateLinkHandler;
use ShortURL\Delivery\Http\ListLinksHandler;
use ShortURL\Delivery\Http\RedirectHandler;
use ShortURL\Delivery\Http\StatsHandler;

final class ConfigProvider
{
    /**
     * @return array{dependencies: array{factories: array<string, callable>}}
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                'factories' => [
                    CyclePersistence::class => CyclePersistence::fromEnvironment(...),

                    DatabaseInterface::class
                        => static fn(ContainerInterface $container): DatabaseInterface => self::service(
                            $container,
                            CyclePersistence::class,
                        )->databases()->database('default'),

                    ORM::class
                        => static fn(ContainerInterface $container): ORM => self::service(
                            $container,
                            CyclePersistence::class,
                        )->orm(),

                    EntityManager::class
                        => static fn(ContainerInterface $container): EntityManager => self::service(
                            $container,
                            CyclePersistence::class,
                        )->entityManager(),

                    ShortLinkRepository::class
                        => static fn(ContainerInterface $container): ShortLinkRepository => new CycleShortLinkRepository(
                            self::service($container, ORM::class),
                            self::service($container, EntityManager::class),
                            self::service($container, DatabaseInterface::class),
                        ),

                    CreateShortLink::class
                        => static fn(ContainerInterface $container): CreateShortLink => new CreateShortLink(
                            self::service($container, ShortLinkRepository::class),
                        ),
                    RedirectShortLink::class
                        => static fn(ContainerInterface $container): RedirectShortLink => new RedirectShortLink(
                            self::service($container, ShortLinkRepository::class),
                        ),
                    GetClickStats::class
                        => static fn(ContainerInterface $container): GetClickStats => new GetClickStats(
                            self::service($container, ShortLinkRepository::class),
                        ),
                    ListShortLinks::class
                        => static fn(ContainerInterface $container): ListShortLinks => new ListShortLinks(
                            self::service($container, ShortLinkRepository::class),
                        ),

                    CreateLinkHandler::class
                        => static fn(ContainerInterface $container): CreateLinkHandler => new CreateLinkHandler(
                            self::service($container, CreateShortLink::class),
                        ),
                    RedirectHandler::class
                        => static fn(ContainerInterface $container): RedirectHandler => new RedirectHandler(
                            self::service($container, RedirectShortLink::class),
                        ),
                    StatsHandler::class
                        => static fn(ContainerInterface $container): StatsHandler => new StatsHandler(
                            self::service($container, GetClickStats::class),
                        ),
                    ListLinksHandler::class
                        => static fn(ContainerInterface $container): ListLinksHandler => new ListLinksHandler(
                            self::service($container, ListShortLinks::class),
                        ),
                ],
            ],
        ];
    }

    /**
     * @template T of object
     * @param class-string<T> $id
     * @return T
     */
    private static function service(ContainerInterface $container, string $id): object
    {
        $service = $container->get($id);

        if (! $service instanceof $id) {
            throw new LogicException(sprintf('Invalid service %s configs.', $id));
        }

        return $service;
    }
}
