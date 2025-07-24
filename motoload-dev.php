<?php
require_once __DIR__ . '/src/Loader.php';

Moto\Autoload\Loader::register([
    'Moto\\Autoload\\' => __DIR__ . '/tests/'
]);
