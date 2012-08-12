<?php

require_once('../lib/phex.php');

class prova
{
	
	public $ciao = "CICCIO";
	
	static function callstatic()
	{
		echo "OK CALL STATIC";
	}
	
	public function call()
	{
		echo "OK CALL";
	}
}

function test()
{
	echo "This is a test";
}

$ciccio = new prova();

phex::routeGET("/", array('\nested\example2::test', function() {echo "TUTTO OK";}));
phex::route("/@test1/ciao/test2", '$ciccio->call');
phex::route("/@test1/@plutone/@test2", 'test');
phex::route("/@test1/ciao/@test2", 'test');
phex::run();

?>