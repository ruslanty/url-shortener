<?php

declare(strict_types=1);

use ShortURL\Adapter\Persistence\CyclePersistence;

require __DIR__ . '/../vendor/autoload.php';

$contents = file_get_contents(__DIR__ . '/../database/migrations/001_create_short_links.sql');

if ($contents === false) {
    throw new RuntimeException('Invalid migration file.');
}

$migration = trim($contents);

if ($migration === '') {
    throw new RuntimeException('Migration file is empty.');
}

$database = CyclePersistence::fromEnvironment()->databases()->database('default');

foreach (explode(';', $migration) as $statement) {
    $statement = trim($statement);

    if ($statement !== '') {
        $database->execute($statement);
    }
}

fwrite(STDOUT, 'Database schema is ready.' . PHP_EOL);
