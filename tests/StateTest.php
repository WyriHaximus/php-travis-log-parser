<?php declare(strict_types=1);

namespace WyriHaximus\Tests\Travis\LogParser;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rx\Observable;
use WyriHaximus\Travis\ConfigParser\Action;
use WyriHaximus\Travis\LogParser\Stages;
use WyriHaximus\Travis\LogParser\StageState;
use WyriHaximus\Travis\LogParser\State;

final class StateTest extends TestCase
{
    public function testCurrentStage()
    {
        $state = new State();
        self::assertSame(State::DEFAULT_STAGE, $state->getCurrentStage());
        $newState = $state->withCurrentStage(Stages::INSTALL);
        self::assertNotSame($newState, $state);
        self::assertSame(State::DEFAULT_STAGE, $state->getCurrentStage());
        self::assertSame(Stages::INSTALL, $newState->getCurrentStage());
    }

    public function testCurrentAction()
    {
        $actionInstall = new Action('composer install');
        $actionUpdate = new Action('composer update');
        $state = (new State())->
            withStage(Stages::INSTALL, $actionInstall)->
            withStage(Stages::BEFORE_SCRIPT, $actionUpdate)->
            withCurrentAction($actionInstall);
        self::assertSame($actionInstall, $state->getCurrentAction());
        $newState = $state->withCurrentAction($actionUpdate);
        self::assertNotSame($newState, $state);
        self::assertSame($actionInstall, $state->getCurrentAction());
        self::assertSame($actionUpdate, $newState->getCurrentAction());
    }

    public function testCurrentStageNonExistingStage()
    {
        self::expectException(InvalidArgumentException::class);

        (new State())->withCurrentStage('foo.bar');
    }

    public function testStages()
    {
        $action = new Action('composer install');
        $state = new State();
        self::assertSame([], $state->getStage(Stages::INSTALL));
        $newState = $state->withStage(Stages::INSTALL, $action);
        self::assertNotSame($newState, $state);
        self::assertSame([], $state->getStage(Stages::INSTALL));
        self::assertSame([$action], $newState->getStage(Stages::INSTALL));
    }

    public function tesWithStageNonExistingStage()
    {
        self::expectException(\InvalidArgumentException::class);

        (new State())->withStage('foo.bar', new Action(''));
    }

    public function tesGetStageNonExistingStage()
    {
        self::expectException(\InvalidArgumentException::class);

        (new State())->getStage('foo.bar');
    }

    public function testGetStagesWithActions()
    {
        $actionInstall = new Action('composer install');
        $actionUpdate = new Action('composer update');
        $state = (new State())->withStage(Stages::INSTALL, $actionInstall);
        self::assertSame(
            [
                Stages::INSTALL => [
                    $actionInstall,
                ],
            ],
            $state->getStagesWithActions()
        );
        $state = $state->withStage(Stages::INSTALL, $actionUpdate);
        self::assertSame(
            [
                Stages::INSTALL => [
                    $actionUpdate,
                ],
            ],
            $state->getStagesWithActions()
        );
    }

    public function testGetStageByAction()
    {
        $action = new Action('composer install');
        $state = (new State())->withStage(Stages::INSTALL, $action);

        self::assertSame(Stages::INSTALL, $state->getStageByAction($action));
    }

    public function testGetStageByActionException()
    {
        self::expectException(InvalidArgumentException::class);

        (new State())->getStageByAction(new Action('composer install'));
    }

    public function testGetSummary()
    {
        $action = new Action('composer install');
        $state = (new State())->
            withStage(Stages::INSTALL, $action);

        self::assertSame(
            [
                Stages::NOT_STARTED_YET => [
                    'stage' => StageState::RUNNING,
                    'steps' => 0,
                    'step' => 0,
                ],
                Stages::BEFORE_INSTALL  => [
                    'stage' => StageState::WAITING,
                    'steps' => 0,
                    'step' => 0,
                ],
                Stages::INSTALL         => [
                    'stage' => StageState::WAITING,
                    'steps' => 1,
                    'step' => 0,
                ],
                Stages::BEFORE_SCRIPT   => [
                    'stage' => StageState::WAITING,
                    'steps' => 0,
                    'step' => 0,
                ],
                Stages::SCRIPT          => [
                    'stage' => StageState::WAITING,
                    'steps' => 0,
                    'step' => 0,
                ],
                Stages::BEFORE_CACHE    => [
                    'stage' => StageState::WAITING,
                    'steps' => 0,
                    'step' => 0,
                ],
                Stages::AFTER_SUCCESS   => [
                    'stage' => StageState::WAITING,
                    'steps' => 0,
                    'step' => 0,
                ],
                Stages::AFTER_FAILURE   => [
                    'stage' => StageState::WAITING,
                    'steps' => 0,
                    'step' => 0,
                ],
                Stages::AFTER_SCRIPT    => [
                    'stage' => StageState::WAITING,
                    'steps' => 0,
                    'step' => 0,
                ],
                Stages::DONE            => [
                    'stage' => StageState::WAITING,
                    'steps' => 0,
                    'step' => 0,
                ],
            ],
            $state->getSummary()
        );

        $state = $state->withCurrentStage(Stages::INSTALL)->
            withCurrentAction($action);

        self::assertSame(
            [
                Stages::NOT_STARTED_YET => [
                    'stage' => StageState::COMPLETED,
                    'steps' => 0,
                    'step' => 0,
                ],
                Stages::BEFORE_INSTALL  => [
                    'stage' => StageState::COMPLETED,
                    'steps' => 0,
                    'step' => 0,
                ],
                Stages::INSTALL         => [
                    'stage' => StageState::RUNNING,
                    'steps' => 1,
                    'step' => 1,
                ],
                Stages::BEFORE_SCRIPT   => [
                    'stage' => StageState::WAITING,
                    'steps' => 0,
                    'step' => 0,
                ],
                Stages::SCRIPT          => [
                    'stage' => StageState::WAITING,
                    'steps' => 0,
                    'step' => 0,
                ],
                Stages::BEFORE_CACHE    => [
                    'stage' => StageState::WAITING,
                    'steps' => 0,
                    'step' => 0,
                ],
                Stages::AFTER_SUCCESS   => [
                    'stage' => StageState::WAITING,
                    'steps' => 0,
                    'step' => 0,
                ],
                Stages::AFTER_FAILURE   => [
                    'stage' => StageState::WAITING,
                    'steps' => 0,
                    'step' => 0,
                ],
                Stages::AFTER_SCRIPT    => [
                    'stage' => StageState::WAITING,
                    'steps' => 0,
                    'step' => 0,
                ],
                Stages::DONE            => [
                    'stage' => StageState::WAITING,
                    'steps' => 0,
                    'step' => 0,
                ],
            ],
            $state->getSummary()
        );
    }

    public function testGetCurrentStep()
    {
        $action = new Action('composer install');
        $state = (new State())->
            withStage(Stages::INSTALL, $action)->
            withCurrentStage(Stages::INSTALL)->
            withCurrentAction($action);

        self::assertSame(1, $state->getCurrentStep());
    }
}
