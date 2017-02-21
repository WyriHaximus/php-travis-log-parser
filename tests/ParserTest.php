<?php declare(strict_types=1);

namespace WyriHaximus\Tests\Travis\LogParser;

use PHPUnit\Framework\TestCase;
use Rx\Observable;
use WyriHaximus\Travis\LogParser\Parser;

final class ParserTest extends TestCase
{
    public function testCreateFromString()
    {
        $contents = "abc\r\ndef";
        $array = [];

        Parser::fromString($contents)->toArray()->subscribeCallback(function ($items) use (&$array) {
            $array = $items;
        });

        self::assertSame(['abc', 'def'], $array);
    }

    public function testCreateFromObservable()
    {
        $contents = Observable::fromArray(['abc', 'def']);
        $array = [];

        Parser::fromObservable($contents)->toArray()->subscribeCallback(function ($items) use (&$array) {
            $array = $items;
        });

        self::assertSame(['abc', 'def'], $array);
    }

    public function testCreateFromObserva()
    {
        $contents = Observable::fromArray(['abc', 'def']);
        $array = [];

        Parser::fromObservable($contents)->toArray()->subscribeCallback(function ($items) use (&$array) {
            $array = $items;
        });

        self::assertSame(['abc', 'def'], $array);
    }
}
