<?php
if (!defined('PHPBRUTE_APP_DIR')) {
    define('PHPBRUTE_APP_DIR', __DIR__);
}

spl_autoload_register(function ($class_name) {
    $file = PHPBRUTE_APP_DIR . '/' . str_replace('\\', '/', $class_name) . '.php';
    if (file_exists($file)) {
        include $file;
    }
});
