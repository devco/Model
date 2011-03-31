<?php

ini_set('memory_limit', '512M');
error_reporting(E_ALL ^ E_STRICT);
ini_set('display_errors', 'on');

require dirname(__FILE__) . '/../lib/Habitat/Autoloader.php';
require dirname(__FILE__) . '/../lib/Testes/Autoloader.php';
\Habitat\Autoloader::register();
Testes_Autoloader::register(dirname(__FILE__) . '/../tests');

$bench = new Bench;
echo $bench->run();