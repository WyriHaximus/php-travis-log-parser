<?php declare(strict_types=1);

namespace WyriHaximus\Tests\Travis\LogParser;

use PHPUnit\Framework\TestCase;
use Rx\Observable;
use WyriHaximus\Travis\ConfigParser\Config;
use WyriHaximus\Travis\LogParser\Parser;
use Generator;

final class ParserTest extends TestCase
{
    public function linesProvider(): Generator
    {
        yield [
            "abc\r\n",
            ['abc'],
        ];

        yield [
            "abc\r\ndef\r\n",
            ['abc', 'def'],
        ];

        $randomBytes = bin2hex(random_bytes(256));
        yield [
            "abc\r\ndef\r\n" . $randomBytes . "\r\n",
            ['abc', 'def', $randomBytes],
        ];
    }

    /**
     * @dataProvider linesProvider
     */
    public function testCreateFromString(string $contents, array $lines)
    {
        $array = [];

        Parser::fromString(
            $contents,
            new Config([])
        )->toArray()->subscribeCallback(function ($items) use (&$array) {
            $array = $items;
        });

        self::assertSame($lines, $array);
    }

    /**
     * @dataProvider linesProvider
     */
    public function testCreateFromObservable(string $contents, array $lines)
    {
        $array = [];

        Parser::fromObservable(
            Observable::fromArray(str_split($contents)),
            new Config([])
        )->toArray()->subscribeCallback(function ($items) use (&$array) {
            $array = $items;
        });

        self::assertSame($lines, $array);
    }
}
