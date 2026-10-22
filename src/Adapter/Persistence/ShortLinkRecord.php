<?php

declare(strict_types=1);

namespace ShortURL\Adapter\Persistence;

class ShortLinkRecord
{
    public string $code;

    public string $destinationUrl;

    public string $createdAt;
}
