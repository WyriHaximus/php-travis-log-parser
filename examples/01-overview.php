<?php declare(strict_types=1);

use WyriHaximus\Travis\LogParser\Parser as LogParser;
use WyriHaximus\Travis\ConfigParser\Parser as ConfigParser;
use WyriHaximus\Travis\LogParser\Stages;
use WyriHaximus\Travis\LogParser\State;

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

LogParser::fromString(
    file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'log.txt'),
    ConfigParser::parse(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '.travis.yml'))
)->subscribeCallback(
    function (State $state) {
        sleep(1);
        $table = '+-----------------------------------+' . PHP_EOL;
        $output = $table;
        foreach ($state->getSummary() as $stage => $details) {
            $output .= '| ' . str_pad($stage, 15, ' ') . ' | ' . $details['step'] . '/' . $details['steps'] . ' | ' . str_pad($details['stage'], 9, ' ') . ' |' . PHP_EOL;
        }
        $output .= $table;

        if ($state->getCurrentStage() !== Stages::NOT_STARTED_YET) {
            $output = "\033[" . (count($state->getSummary()) + 2) . "A" . $output;
        }

        echo $output;
    },
    function ($error) {
        echo (string)$error;
        die();
    },
    function () {
        echo 'Done!', PHP_EOL;
    }
);
