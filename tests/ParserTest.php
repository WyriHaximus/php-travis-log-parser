<?php declare(strict_types=1);

namespace WyriHaximus\Tests\Travis\LogParser;

use PHPUnit\Framework\TestCase;
use Rx\Observable;
use WyriHaximus\Travis\ConfigParser\Config;
use WyriHaximus\Travis\LogParser\Parser;
use Generator;
use WyriHaximus\Travis\LogParser\Stages;
use WyriHaximus\Travis\LogParser\StageState;
use WyriHaximus\Travis\LogParser\State;

final class ParserTest extends TestCase
{
    public function linesProvider(): Generator
    {
        $randomBytes = bin2hex(random_bytes(256));

        $notYetStarted = function (State $state) {
            self::assertSame(Stages::NOT_STARTED_YET, $state->getCurrentStage());
        };
        $done = function (State $state) {
            self::assertSame(Stages::DONE, $state->getCurrentStage());
        };

        yield [
            "abc\r\n",
            new Config([]),
            [
                $notYetStarted,
                $done,
            ],
        ];

        yield [
            "abc\r\ndef\r\n" . Parser::ACTION_PREFIX . "make travis\r\n",
            new Config([
                'script' => 'make travis',
            ]),
            [
                $notYetStarted,
                function (State $state) {
                    self::assertSame(Stages::SCRIPT, $state->getCurrentStage());
                    self::assertSame('make travis', (string)$state->getCurrentAction());
                    self::assertSame(1, $state->getCurrentStep());
                },
                $done,
            ],
        ];

        yield [
            "abc\r\ndef\r\n" . Parser::ACTION_PREFIX . "make travis\r\n" . $randomBytes . "\r\n",
            new Config([
                'script' => 'make travis',
                'install' => [
                    'composer install',
                    'composer update',
                ],
            ]),
            [
                $notYetStarted,
                function (State $state) {
                    self::assertSame(Stages::SCRIPT, $state->getCurrentStage());
                    self::assertSame('make travis', (string)$state->getCurrentAction());
                    self::assertSame(1, $state->getCurrentStep());
                },
                $done,
            ],
        ];

        yield [
            "abc\r\ndef\r\n" .
                Parser::ACTION_PREFIX . "make travis\r\n" .
                $randomBytes . "\r\n" .
                Parser::ACTION_PREFIX . "composer install\r\n" .
                $randomBytes . "\r\n" .
                Parser::ACTION_PREFIX . "composer update\r\n" .
                $randomBytes . "\r\n" .
                Parser::ACTION_PREFIX . "composer unknown\r\n" .
                $randomBytes . "\r\n",
            new Config([
                'script' => 'make travis',
                'install' => [
                    'composer install',
                    'composer update',
                ],
            ]),
            [
                $notYetStarted,
                function (State $state) {
                    self::assertSame(Stages::SCRIPT, $state->getCurrentStage());
                    self::assertSame('make travis', (string)$state->getCurrentAction());
                    self::assertSame(1, $state->getCurrentStep());
                },
                function (State $state) {
                    self::assertSame(Stages::INSTALL, $state->getCurrentStage());
                    self::assertSame('composer install', (string)$state->getCurrentAction());
                    self::assertSame(1, $state->getCurrentStep());
                },
                function (State $state) {
                    self::assertSame(Stages::INSTALL, $state->getCurrentStage());
                    self::assertSame('composer update', (string)$state->getCurrentAction());
                    self::assertSame(2, $state->getCurrentStep());
                },
                $done,
            ],
        ];
    }

    /**
     * @dataProvider linesProvider
     * @param string $contents
     * @param Config $config
     * @param callable[] $checks
     */
    public function testCreateFromString(string $contents, Config $config, array $checks)
    {
        $this->assertObservableResults(
            Parser::fromString(
                $contents,
                $config
            ),
            $checks
        );
    }

    /**
     * @dataProvider linesProvider
     * @param string $contents
     * @param Config $config
     * @param callable[] $checks
     */
    public function testCreateFromObservable(string $contents, Config $config, array $checks)
    {
        $this->assertObservableResults(
            Parser::fromObservable(
                Observable::fromArray(str_split($contents)),
                $config
            ),
            $checks
        );
    }

    private function assertObservableResults(Observable $observable, array $checks)
    {
        $array = [];

        $observable->toArray()->subscribeCallback(function ($items) use (&$array) {
            $array = $items;
        });

        foreach ($array as $item) {
            /** @var callable $check */
            $check = array_shift($checks);
            $check($item);
        }
    }
}
