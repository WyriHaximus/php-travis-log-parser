<?php declare(strict_types=1);

namespace WyriHaximus\Travis\LogParser;

use WyriHaximus\Travis\ConfigParser\Action;

final class State
{
    const DEFAULT_STAGE = Stages::BEFORE_INSTALL;

    /**
     * @var Action[][]
     */
    private $stages = [
        Stages::BEFORE_INSTALL => [],
        Stages::INSTALL        => [],
        Stages::BEFORE_SCRIPT  => [],
        Stages::SCRIPT         => [],
        Stages::BEFORE_CACHE   => [],
        Stages::AFTER_SUCCESS  => [],
        Stages::AFTER_FAILURE  => [],
        Stages::AFTER_SCRIPT   => [],
    ];

    /**
     * @var string
     */
    private $currentStage = self::DEFAULT_STAGE;

    public function withCurrentStage(string $currentStage): State
    {
        $this->ensureStageExists($currentStage);

        $clone = clone $this;
        $clone->currentStage = $currentStage;
        return $clone;
    }

    public function getCurrentStage(): string
    {
        return $this->currentStage;
    }

    public function withStage(string $stage, Action ...$actions)
    {
        $this->ensureStageExists($stage);

        $clone = clone $this;
        $clone->stages[$stage] = $actions;
        return $clone;
    }

    public function getStage(string $stage)
    {
        $this->ensureStageExists($stage);

        return $this->stages[$stage];
    }

    public function getStagesWithActions(): array
    {
        $stages = $this->stages;

        foreach ($stages as $stage => $actions) {
            if (count($actions) > 0) {
                continue;
            }

            unset($stages[$stage]);
        }

        return $stages;
    }

    private function ensureStageExists(string $stage)
    {
        if (!isset($this->stages[$stage])) {
            throw new \InvalidArgumentException('Stage "' . $stage . '" doesn\'t exist"');
        }
    }
}