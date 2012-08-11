<?php

class phex
{
	
	private $VERSION = "0.0.1";
	private $_PHEX;
	public $_ROUTES;
	
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
	public function routeGET($req_route)
	{
		if($_SERVER['REQUEST_METHOD'] == "GET")
		{
			$this->route($req_route);
		}
	}
	
	/**
	
	**/
	public function isGET()
	{
		return $_SERVER['REQUEST_METHOD'] == "GET" ? true : false;
	}
	
	/**
	
	**/
	public function isPOST()
	{
		return $_SERVER['REQUEST_METHOD'] == "POST" ? true : false;
	}
	
	/**
	
	**/
	public function routePOST($req_route)
	{
		if($_SERVER['REQUEST_METHOD'] == "POST")
		{
			$this->route($req_route);
		}
	}
	
	/**
	
	**/
	public function route($req_route)
	{
		if(strpos($req_route, "@") === false)
		{
			if($_SERVER['REQUEST_URI'] !== $req_route)
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
				$this->_ROUTES[$num_parameters] = $candidate_route; // Should save also some other data
			}
			else
			{
				echo "INCOMPATIBLE_C";
				return;
			}
		}
	}
	
	/**
		If flush is true and the item is stored persistent it will be deleted after being retrieved.
			@param item string
			@param flush boolean
	**/
	public function get($item, $flush = false)
	{
		if (isset($this->_PHEX[$item]))
		{
			$return = $this->_PHEX[$item];
			if ($flush)
			{
				unset($this->_PHEX[$item]);
			}
		}
		return $return;
	}
	
	/**
		If $ttl is more than 0 the value will be stored persistently for the amount of time specified.
			@param item string
			@param value mixed
			@param ttl integer
	**/
	public function set($item, $value, $ttl = 0)
	{
		if(!$ttl)
		{
			$this->_PHEX[$item] = $value;
		}
	}
	
	/**
	
	**/
	public function run()
	{
		ksort($this->_ROUTES);
		// Take the first router, throw away the others
		global $_PHEX;
		$_PHEX = $this->_PHEX;
		$_PHEX['ROUTES'] = $this->_ROUTES;
	}
}
?>
