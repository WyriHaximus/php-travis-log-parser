<?php declare(strict_types=1);

namespace WyriHaximus\Travis\LogParser;

final class Stages
{
    const NOT_STARTED_YET = 'not_started_yet';
    const BEFORE_INSTALL  = 'before_install';
    const INSTALL         = 'install';
    const BEFORE_SCRIPT   = 'before_script';
    const SCRIPT          = 'script';
    const BEFORE_CACHE    = 'before_cache';
    const AFTER_SUCCESS   = 'after_success';
    const AFTER_FAILURE   = 'after_failure';
    const AFTER_SCRIPT    = 'after_script';
    const DONE            = 'done';
}
