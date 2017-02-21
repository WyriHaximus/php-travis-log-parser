<?php declare(strict_types=1);

namespace WyriHaximus\Travis\LogParser;

use Rx\Observable;

final class Parser extends Observable
{
    const LF = "\r\n";

    /**
     * @var Observable
     */
    private $stream;

    public static function fromString(string $contents): Observable
    {
        return (new self(Observable::fromArray(explode(self::LF, $contents))))->parse();
    }

    public static function fromObservable(Observable $contents): Observable
    {
        return (new self($contents))->parse();
    }

    private function __construct(Observable $stream)
    {
        $this->stream = $stream;
    }

    private function parse(): Observable
    {
        return $this->stream;
    }
}
