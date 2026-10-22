<?php

declare(strict_types=1);

namespace ShortURL\Adapter\Persistence;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\Postgres\TcpConnectionConfig;
use Cycle\Database\Config\PostgresDriverConfig;
use Cycle\Database\DatabaseManager;
use Cycle\ORM\EntityManager;
use Cycle\ORM\Factory;
use Cycle\ORM\ORM;
use Cycle\ORM\Schema;

final readonly class CyclePersistence
{
    private ORM $orm;
    private EntityManager $entityManager;

    public function __construct(private DatabaseManager $databases)
    {
        $this->orm = new ORM(new Factory($databases), new Schema([
            'short_link' => [
                Schema::ENTITY => ShortLinkRecord::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'short_links',
                Schema::PRIMARY_KEY => 'code',
                Schema::COLUMNS => [
                    'code' => 'code',
                    'destinationUrl' => 'destination_url',
                    'createdAt' => 'created_at',
                ],
                Schema::TYPECAST => [
                    'code' => 'string',
                    'destinationUrl' => 'string',
                    'createdAt' => 'string',
                ],
                Schema::RELATIONS => [],
            ],
        ]));

        $this->entityManager = new EntityManager($this->orm);
    }

    public static function fromEnvironment(): self
    {
        $manager = new DatabaseManager(new DatabaseConfig([
            'default' => 'default',
            'databases' => [
                'default' => [
                    'connection' => 'postgres',
                ],
            ],
            'connections' => [
                'postgres' => new PostgresDriverConfig(
                    connection: new TcpConnectionConfig(
                        database: getenv('DB_NAME') ?: 'database', // @phpstan-ignore ternary.shortNotAllowed
                        host: getenv('DB_HOST') ?: 'postgres', // @phpstan-ignore ternary.shortNotAllowed
                        port: 5432,
                        user: getenv('DB_USER') ?: 'user', // @phpstan-ignore ternary.shortNotAllowed
                        password: getenv('DB_PASSWORD') ?: 'password', // @phpstan-ignore ternary.shortNotAllowed
                    ),
                    queryCache: false,
                ),
            ],
        ]));

        return new self($manager);
    }

    public function databases(): DatabaseManager
    {
        return $this->databases;
    }

    public function orm(): ORM
    {
        return $this->orm;
    }

    public function entityManager(): EntityManager
    {
        return $this->entityManager;
    }
}
