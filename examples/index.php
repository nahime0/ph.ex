<?php

require_once('../lib/phex.php');

$phex = new phex();
$phex->route("/@test1/ciao/test2");
$phex->route("/@test1/@plutone/@test2");
$phex->route("/@test1/ciao/@test2");
$phex->run();

var_dump($phex->ROUTES);

?>