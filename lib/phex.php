<?php

class phex
{
	
	private static $VERSION = "0.0.1";
	private static $ACCEPTED_METHODS = array("GET", "POST");
	private static $_PHEX;
	private static $_ROUTES;
	private static $loaded = false;
	
	
	private static function load()
	{
		if(!self::$loaded)
		{
			self::$_PHEX = array(
				'VERSION' => self::$VERSION,
				'AUTOLOAD' => "autoload/"
			);
			self::$loaded = true;
		}
	}
	
	/**
		Provides isMETHOD and routeMETHOD.
		METHOD could be one of the defined http ACCEPTED_METHODS
	**/
	public static function __callStatic($method, $arguments) {
		if(substr($method,0,2) == "is" && 
			in_array(
				substr($method,2,strlen($method)), 
				self::$ACCEPTED_METHODS))
		{
			$http_method = substr($method,2,strlen($method));
			return $http_method == $_SERVER['REQUEST_METHOD'] ? true : false;
		}
		elseif(substr($method,0,5) == "route" && 
			in_array(
				substr($method,5,strlen($method)), 
				self::$ACCEPTED_METHODS))
		{
			$http_method = substr($method,5,strlen($method));
			if ($http_method == $_SERVER['REQUEST_METHOD'])
			{
				self::route($arguments[0], $arguments[1]);
			}
		} 
	}
	
	/**
	
	**/
	public static function route($req_route, $callback)
	{
		if(isset(self::$_ROUTES[0]) && isset(self::$_ROUTES[0]['CALLBACK']))
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
				self::$_ROUTES[0]['CALLBACK'] = $callback;
				self::$_ROUTES[0]['PARAMS'] = null;
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
				self::$_ROUTES[$num_parameters]['PARAMS'] = $candidate_route; // Should save also some other data
				self::$_ROUTES[$num_parameters]['CALLBACK'] = $callback;
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
	public static function run()
	{
		self::load();
		global $_PHEX;
		$_PHEX =& self::$_PHEX;
		
		if(sizeof(self::$_ROUTES) == 0)
		{
			self::error(404);
		}
		
		$autoloads = explode(",", self::$_PHEX['AUTOLOAD']);
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
		
		ksort(self::$_ROUTES);
		$_PHEX['ROUTES'] = self::$_ROUTES;
		foreach(self::$_ROUTES as $route)
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
					if(is_object($callback) && ($callback instanceof Closure))
					{
						$callback();
					}
					else
					{
						$callback = trim($callback);
						self::launchCallback($callback);
					}
				}
			}
			
			$_PHEX['ROUTE'] = $route;
			break;
		}
	}
	
	/**
	
	**/
	private static function launchCallback($callback)
	{
		if(is_callable($callback))
		{
			eval($callback."();");	
		}
		elseif(strpos($callback, "->") > 0)
		{
			try
			{
				$calldata = explode("->", $callback);
				$callclass = $calldata[0];
				$callmethod = $calldata[1];
				if(substr($callclass,0,1) == "$")
				{
					eval("global ".$callclass.";");
					$var = eval("return ".$callclass.";");
					if (!is_object($var))
					{
						throw new Exception('Cannot find declared object: '.$callclass);
					}
				}
				else
				{
					if(!class_exists($callclass))
					{
						throw new Exception('Cannot load class: '.$callclass);
					}
					$var = new $callclass();					
				}
				$var->$callmethod();
			}
			catch(Exception $e) {
				trigger_error($e->getMessage());
				return;
			}
		}
		else
		{
			trigger_error("Invalid callback");
		}
	}
	
	/**
		Retrieve saved var
			@param item string
	**/
	public static function get($item)
	{
		if (isset(self::$_PHEX[$item]))
		{
			return self::$_PHEX[$item];
		}
		else
		{
			return null;
		}
	}
	
	/**
		Set var
			@param item string
			@param value mixed
	**/
	public static function set($item, $value)
	{
		self::$_PHEX[$item] = $value;
	}
	
	/**
	
	**/
	public static function error($code)
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
