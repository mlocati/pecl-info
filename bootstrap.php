<?php

declare(strict_types = 1);

set_error_handler(
    static function ($errno, $errstr, $errfile, $errline) {
        $msg = "Error {$errno}: {$errstr}\n";
        if ($errfile) {
            $msg .= "File: {$errfile}\n";
            if ($errline) {
                $msg .= "Line: {$errline}\n";
            }
        }

        throw new RuntimeException($msg);
    },
    -1
);

define('DIR_ROOT', rtrim(str_replace(DIRECTORY_SEPARATOR, '/', __DIR__), '/'));
const DIR_XML = DIR_ROOT . '/xml';
const DIR_WEB = DIR_ROOT . '/web';

require_once __DIR__ . '/vendor/autoload.php';
