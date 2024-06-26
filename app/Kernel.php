<?php

namespace App;

class Kernel
{
    static private function read_classes() {
        foreach(glob(dirname(__FILE__).'/Classes/*.php') as $file) {
            include_once($file);
        }
    }

    static private function read_services() {
        foreach(glob(dirname(__FILE__).'/Services/*.php') as $file) {
            include_once($file);
        }
    }

    static private function read_configs() {
        global $settings;
        foreach(glob(dirname(__FILE__).'/../configs/*.php') as $file) {
            $name = strtolower(explode('.',basename($file))[0]);
            $settings[$name] = include($file);
        }
    }

    static private function read_envs() {
        global $argv;

        $envFile = dirname(__FILE__).'/../.env';

        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }

                if (strpos($line, '=') !== false) {
                    list($name, $value) = explode('=', $line, 2);

                    $name = trim($name);
                    $value = trim($value);

                    $_ENV[$name] = $value;
                    putenv("$name=$value");
                }
            }
        }

        $runInTerminal = false;
        if (isset($argv) && $argv && sizeof($argv) > 1) {
            $runInTerminal = true;
        }
        putenv("APP_RUN_IN_TERMINAL=$runInTerminal");

    }

    static public function run() {
        self::read_envs();
        self::read_configs();
        self::read_classes();
        self::read_services();
    }

}

Kernel::run();