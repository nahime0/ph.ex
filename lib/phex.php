<?php

class phex
{
	
	private $VERSION = "0.0.1";
	private $_PHEX;
	public $_ROUTES;
	
	
	public function __construct()
	{
		$this->_PHEX = array(
			'VERSION' => $this->VERSION,
			'AUTOLOAD' => "autoload/"
		);
	}
	
	/**
	
	**/
	public function __call($method, $arguments) {
		
	}
	
	/**
		Move next 4 methods into __call method
	**/
	public function routeGET($req_route, $callback)
	{
		if($_SERVER['REQUEST_METHOD'] == "GET")
		{
			$this->route($req_route, $callback);
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
	public function routePOST($req_route, $callback)
	{
		if($_SERVER['REQUEST_METHOD'] == "POST")
		{
			$this->route($req_route, $callback);
		}
	}
	
	/**
	
	**/
	public function route($req_route, $callback)
	{
		if(isset($this->_ROUTES[0]) && isset($this->_ROUTES[0]['CALLBACK']))
		{
			/*
			* Still exists an exact match route, we can skip all others.
			*/
			return;
		}
		if(strpos($req_route, "@") === false)
		{
			if($_SERVER['REQUEST_URI'] !== $req_route)
			{
				//echo "INCOMPATIBLE_A";
				return;
			}
			else
			{
				$this->_ROUTES[0]['CALLBACK'] = $callback;
				$this->_ROUTES[0]['PARAMS'] = null;
			}
		}
		else
		{
			$req_route_data = explode("/", trim($req_route, '/'));
			$req_uri_data = explode("/", trim($_SERVER['REQUEST_URI'], '/'));
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
						//echo "INCOMPATIBLE_B";
						return;
					}
					elseif(substr($token_route,0,1) == "@")
					{
						$candidate_route[substr($token_route,1,strlen($token_route))] = $token_uri;
					}
				}
				$this->_ROUTES[$num_parameters]['PARAMS'] = $candidate_route; // Should save also some other data
				$this->_ROUTES[$num_parameters]['CALLBACK'] = $callback;
			}
			else
			{
				//echo "INCOMPATIBLE_C";
				return;
			}
		}
	}
	
	/**
	
	**/
	public function run()
	{
		global $_PHEX;
		$_PHEX =& $this->_PHEX;
		
		if(sizeof($this->_ROUTES) == 0)
		{
			$this->error(404);
		}
		
		$autoloads = explode(",", $this->_PHEX['AUTOLOAD']);
		$al_paths = array();
		foreach($autoloads as $autoload)
		{
			$autoload = trim($autoload);
			if(strlen($autoload) > 0)
			{
				$al_paths[] = $autoload;
			}
		}
		if(sizeof($al_paths) > 0)
		{
			set_include_path(get_include_path().PATH_SEPARATOR.implode(PATH_SEPARATOR, $al_paths));	
		}
		spl_autoload_extensions(".php");
		spl_autoload_register();
		
		ksort($this->_ROUTES);
		$_PHEX['ROUTES'] = $this->_ROUTES;
		foreach($this->_ROUTES as $route)
		{
			$callbacks = $route['CALLBACK'];
			if(is_object($callbacks) && ($callbacks instanceof Closure))
			{
				$callbacks();
			}
			elseif(is_string($callbacks))
			{
				$callbacks = explode(";", $callbacks);
			}
			
			if(is_array($callbacks))
			{
				foreach($callbacks as $callback)
				{
					$callback = trim($callback);
					$this->launchCallback($callback);
				}
			}
			
			$_PHEX['ROUTE'] = $route;
			break;
		}
	}
	
	/**
	
	**/
	private function launchCallback($callback)
	{
		if(is_callable($callback))
		{
			eval($callback."();");
		}
		elseif(strpos($callback, "->") > 0)
		{
			$calldata = explode("->", $callback);
			$callclass = $calldata[0];
			$callmethod = $calldata[1];
			if (substr($callclass,0,1) == "$")
			{
				eval("global ".$callclass.";");
				$var = eval("return ".$callclass.";");
			}
			else
			{
				$var = new $callclass();
			}
			$var->$callmethod();
		}
		else
		{
			// Count the callbacks correctly run and give 404 only if no callback have been executed
			$this->error(404);
		}
	}
	
	/**
		If flush is true and the item is stored persistent it will be deleted after being retrieved.
			@param item string
	**/
	public function get($item)
	{
		if (isset($this->_PHEX[$item]))
		{
			return $this->_PHEX[$item];
		}
		else
		{
			return null;
		}
	}
	
	/**
		If $ttl is more than 0 the value will be stored persistently for the amount of time specified.
			@param item string
			@param value mixed
	**/
	public function set($item, $value)
	{
		$this->_PHEX[$item] = $value;
	}
	
	/**
	
	**/
	public function error($code)
	{
		switch($code)
		{
			case 404:
				header('HTTP/1.0 404 Not Found');
				echo "NOT FOUND";
			break;
			default:
			break;
		}
		exit();
	}
}
?>
