<?php

class phex
{
	
	private $VERSION = "0.0.1";
	private $_PHEX;
	public $ROUTES;
	
	/**
	
	**/
	public function __construct()
	{
		$this->_PHEX = array(
			'VERSION' => $this->VERSION
		);
	}

	/**
	
	**/
	public function __destruct()
	{
		
	}
	
	/**
	
	**/
	public function route($req_route)
	{
		if (strpos($req_route, "@") === false)
		{
			if ($_SERVER['REQUEST_URI'] !== $req_route)
			{
				echo "INCOMPATIBLE_A";
				return;
			}
			else
			{
				// Put this route in the list of routes
			}
		}
		else
		{
			$req_route_data = explode("/", $req_route);
			$req_uri_data = explode("/", $_SERVER['REQUEST_URI']);
			$num_parameters = substr_count($req_route, "@"); // Check that there are no other routes with this number of parameters
			if (sizeof($req_route_data) == sizeof($req_uri_data))
			{
				$candidate_route = array();
				for($i = 0; $i < sizeof($req_route_data); $i++)
				{
					$token_route = $req_route_data[$i];
					$token_uri = $req_uri_data[$i];
					if(substr($token_route,0,1) !== "@" && $token_route !== $token_uri)
					{
						echo "INCOMPATIBLE_B";
						return;
					}
					elseif(substr($token_route,0,1) == "@")
					{
						$candidate_route[substr($token_route,1,strlen($token_route))] = $token_uri;
					}
				}
				$this->ROUTES[$num_parameters] = $candidate_route; // Should save also some other data
			}
			else
			{
				echo "INCOMPATIBLE_C";
				return;
			}
		}
	}
	
	/**
	
	**/
	public function run()
	{
		global $_PHEX;
		$_PHEX = $this->_PHEX;
	}
}
?>
