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


phex::route("/", '\nested\example2::test');
phex::route("/@test1/ciao/test2", function() {echo "TUTTO OK";});
phex::route("/@test1/@plutone/@test2", 'test');
phex::route("/@test1/ciao/@test2", 'test');
phex::run();

?>