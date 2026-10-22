<?php

declare(strict_types=1);

namespace ShortURL\Tests\Unit;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ShortURL\Domain\ShortLink;

final class ShortLinkTest extends TestCase
{
    public function testAcceptsAValidHttpLink(): void
    {
        $link = new ShortLink(
            'Ab_12-xY',
            'https://element.id.lv',
            new DateTimeImmutable(),
        );

        self::assertSame('Ab_12-xY', $link->code);
        self::assertSame('https://element.id.lv', $link->destinationUrl);
    }

    #[DataProvider('invalidLinks')]
    public function testRejectsInvalidCodeOrDestination(string $code, string $destination): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ShortLink($code, $destination, new DateTimeImmutable());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidLinks(): iterable
    {
        yield 'short code' => ['short', 'https://element.id.lv'];
        yield 'javascript URL' => ['Ab_12-xY', 'javascript:alert(1)'];
        yield 'unsupported scheme' => ['Ab_12-xY', 'ftp://element.id.lv/file'];
        yield 'invalid URL' => ['Ab_12-xY', 'not a URL'];
    }
}
