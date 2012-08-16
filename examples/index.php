<?php

require_once('../lib/phex.php');

function test()
{
	echo "This is a test";
}

phex::set('TEST.ARRAY.VAR.VAR2', "valore");
phex::set('NOARRAY', "valore");

phex::route("/", function(){echo "OK";});
phex::route("/@test1/ciao/test2", 'test');
phex::route("/@test1/@plutone/@test2", 'test');
phex::route("/@test1/ciao/@test2", 'test');
phex::run();
echo $_PHEX['TEST']['ARRAY']['VAR']['VAR2'];
echo phex::get('TEST.ARRAY.VAR.VAR2');
?>