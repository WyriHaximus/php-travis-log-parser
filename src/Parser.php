<?php declare(strict_types=1);

namespace WyriHaximus\Travis\LogParser;

use InvalidArgumentException;
use Exception;
use Rx\Observable;
use Rx\ObserverInterface;
use Rx\SchedulerInterface;
use WyriHaximus\Travis\ConfigParser\Action;
use WyriHaximus\Travis\ConfigParser\Config;

final class Parser
{
    const LF = "\r";
    const ACTION_PREFIX = "\e[0K$ ";
    const STAGES = [
        Stages::BEFORE_INSTALL,
        Stages::INSTALL,
        Stages::BEFORE_SCRIPT,
        Stages::SCRIPT,
        Stages::BEFORE_CACHE,
        Stages::AFTER_SUCCESS,
        Stages::AFTER_FAILURE,
        Stages::AFTER_SCRIPT,
    ];

    /**
     * @var Observable
     */
    private $stream;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var State
     */
    private $state;

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
        $this->state = new State();

        $this->setUpState($config);
    }

    private function setUpState(Config $config)
    {
        foreach (self::STAGES as $stage) {
            if (!isset($config->yaml[$stage])) {
                continue;
            }

            if (is_string($config->yaml[$stage])) {
                $this->state = $this->state->withStage($stage, new Action($config->yaml[$stage]));
                continue;
            }

            $actions = [];
            foreach ($config->yaml[$stage] as $action) {
                $actions[] = new Action($action);
            }
            $this->state = $this->state->withStage($stage, ...$actions);
        }
    }

    private function parse(): Observable
    {
        return Observable::create(function (
            ObserverInterface $observer,
            SchedulerInterface $scheduler
        ) {
            $observer->onNext($this->state);
            $this->stream->subscribeCallback(
                function (string $data) use ($observer) {
                    $this->buffer .= $data;
                    $this->parseBuffer($observer);
                },
                function (Exception $error) use ($observer) {
                    $observer->onError($error);
                },
                function () use ($observer) {
                    $this->state = $this->state->withCurrentStage(Stages::DONE);
                    $observer->onNext($this->state);
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
            if (strpos($line, self::ACTION_PREFIX) !== 0) {
                continue;
            }

            $this->processLine($line, $observer);
        }
    }

    private function extractLines(): array
    {
        $this->buffer = str_replace("\n", '', $this->buffer);
        $bufferLength = strlen($this->buffer);
        $lastLFPosition = strrpos($this->buffer, self::LF);

        if ($lastLFPosition + strlen(self::LF) === $bufferLength &&
            $lastLFPosition === strpos($this->buffer, self::LF)
        ) {
            $lines = [substr($this->buffer, 0, $lastLFPosition)];
            $this->buffer = '';
            return $lines;
        }

        $buffer = substr($this->buffer, 0, $lastLFPosition);
        $this->buffer = substr($this->buffer, $lastLFPosition + strlen(self::LF));

        return explode(self::LF, $buffer);
    }

    private function processLine(string $line, ObserverInterface $observer)
    {
        $line = ltrim($line, self::ACTION_PREFIX);
        $action = new Action($line);
        try {
            $stage = $this->state->getStageByAction($action);
            $this->state = $this->state->withCurrentStage($stage)->withCurrentAction($action);
            $observer->onNext($this->state);
        } catch (InvalidArgumentException $error) {
            // Do nothing, this action isn't configured and could very well be once of travis' own actions
        }
    }
}
