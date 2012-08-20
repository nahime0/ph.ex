<?php

require_once('../lib/phex.php');

function test()
{
	echo "This is a test";
}

phex::set('TEST.ARRAY.VAR.VAR2', "valoresss");
phex::set('NOARRAY', "valore");

//phex::config('config.ini');

phex::routeGET("test", "/", 
	function() {
		phex_tpl::serve("template.html");
	}
);
//phex::route("test", "/", "example::test");
phex::route("test", "/@test1/ciao/test2", 'test');
phex::route("test", "/@test1/@plutone/@test2", 'test');
phex::route("test", "/@test1/ciao/@test2", 'test');
phex::run();

//echo phex::get('INI_ARRAY.TEST');

?>