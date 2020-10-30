<?php declare(strict_types=1);

namespace WyriHaximus\Travis\LogParser;

use InvalidArgumentException;
use WyriHaximus\Travis\ConfigParser\Action;

final class State
{
    const DEFAULT_STAGE = Stages::NOT_STARTED_YET;

    /**
     * @var Action[][]
     */
    private $stages = [
        Stages::NOT_STARTED_YET => [],
        Stages::BEFORE_INSTALL  => [],
        Stages::INSTALL         => [],
        Stages::BEFORE_SCRIPT   => [],
        Stages::SCRIPT          => [],
        Stages::BEFORE_CACHE    => [],
        Stages::AFTER_SUCCESS   => [],
        Stages::AFTER_FAILURE   => [],
        Stages::AFTER_SCRIPT    => [],
        Stages::DONE            => [],
    ];

    /**
     * @var array
     */
    private $actionToStageMap = [];

    /**
     * @var array
     */
    private $actionStepInStageMap = [];

    /**
     * @var string
     */
    private $currentStage = self::DEFAULT_STAGE;

    /**
     * @var Action
     */
    private $currentAction;

    public function __construct()
    {
        $this->currentAction = new Action('');
    }

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

    public function withCurrentAction(Action $currentAction): State
    {
        $clone = clone $this;
        $clone->currentAction = $currentAction;
        return $clone;
    }

    public function getCurrentAction(): Action
    {
        return $this->currentAction;
    }

    /**
     * @param string $stage
     * @param Action[] ...$actions
     * @return State
     */
    public function withStage(string $stage, Action ...$actions): State
    {
        $this->ensureStageExists($stage);

        $clone = clone $this;
        $clone->stages[$stage] = $actions;
        foreach ($clone->actionToStageMap as $actionCommand => $actionStage) {
            if ($actionStage === $stage) {
                unset($clone->actionToStageMap[$actionCommand]);
            }
        }

        $actionCount = count($actions);
        for ($i = 0; $i < $actionCount; $i++) {
            $clone->actionStepInStageMap[(string)$actions[$i]] = $i + 1;
            $clone->actionToStageMap[(string)$actions[$i]] = $stage;
        }

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

    public function getStageByAction(Action $action): string
    {
        $action = (string)$action;
        foreach ($this->actionToStageMap as $actionCommand => $stage) {
            if ($actionCommand === $action) {
                return $stage;
            }
        }

        throw new InvalidArgumentException('No stage found for action "' . $action . '"');
    }

    public function getSummary(): array
    {
        $summary = [];
        $currentStageFound = false;

        foreach ($this->stages as $stage => $actions) {
            if ($currentStageFound === false && $stage !== $this->currentStage) {
                $summary[$stage] = [
                    'stage' => StageState::COMPLETED,
                    'steps' => count($actions),
                    'step' => count($actions),
                ];
                continue;
            }

            if ($currentStageFound === false && $stage === $this->currentStage) {
                $summary[$stage] = [
                    'stage' => StageState::RUNNING,
                    'steps' => count($actions),
                    'step' => $this->getCurrentStep(),
                ];
                $currentStageFound = true;
                continue;
            }

            $summary[$stage] = [
                'stage' => StageState::WAITING,
                'steps' => count($actions),
                'step' => 0,
            ];
        }

        return $summary;
    }

    public function getCurrentStep()
    {
        return $this->actionStepInStageMap[(string)$this->currentAction] ?? 0;
    }

    private function ensureStageExists(string $stage): void
    {
        if (!isset($this->stages[$stage])) {
            throw new InvalidArgumentException('Stage "' . $stage . '" doesn\'t exist"');
        }
    }
}
