<?php declare(strict_types=1);

namespace WyriHaximus\Travis\LogParser;

final class StageState
{
    const WAITING   = 'waiting';
    const RUNNING   = 'running';
    const COMPLETED = 'completed';
}
