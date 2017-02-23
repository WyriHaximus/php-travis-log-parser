<?php declare(strict_types=1);

namespace WyriHaximus\Travis\LogParser;

use Exception;
use Rx\Observable;
use Rx\ObserverInterface;
use Rx\SchedulerInterface;
use WyriHaximus\Travis\ConfigParser\Config;

final class Parser
{
    const LF = "\r\n";

    /**
     * @var Observable
     */
    private $stream;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var string
     */
    private $buffer = '';

    public static function fromString(string $contents, Config $config): Observable
    {
        return (new self(Observable::fromArray([$contents]), $config))->parse();
    }

    public static function fromObservable(Observable $contents, Config $config): Observable
    {
        return (new self($contents, $config))->parse();
    }

    private function __construct(Observable $stream, Config $config)
    {
        $this->stream = $stream;
        $this->config = $config;
    }

    private function parse(): Observable
    {
        return Observable::create(function (
            ObserverInterface $observer,
            SchedulerInterface $scheduler
        ) {
            $this->stream->subscribeCallback(
                function (string $data) use ($observer) {
                    $this->buffer .= $data;
                    $this->parseBuffer($observer);
                },
                function (Exception $error) use ($observer) {
                    $observer->onError($error);
                },
                function () use ($observer) {
                    $observer->onCompleted();
                },
                $scheduler
            );
        });
    }

    private function parseBuffer(ObserverInterface $observer)
    {
        if (strpos($this->buffer, self::LF) === false) {
            return;
        }

        $lines = $this->extractLines();

        foreach ($lines as $line) {
            $observer->onNext($line);
        }
    }

    private function extractLines(): array
    {
        $bufferLength = strlen($this->buffer);
        $lastLFPosition = strrpos($this->buffer, self::LF);

        if ($lastLFPosition + 2 === $bufferLength && $lastLFPosition === strpos($this->buffer, self::LF)) {
            $lines = [substr($this->buffer, 0, $lastLFPosition)];
            $this->buffer = '';
            return $lines;
        }

        $buffer = substr($this->buffer, 0, $lastLFPosition);
        $this->buffer = substr($this->buffer, $lastLFPosition + 2);

        return explode(self::LF, $buffer);
    }
}
