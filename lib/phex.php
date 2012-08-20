<?php

/**
	ph.ex Framework
	
	Copyright (c) 2012
	Vincenzo Petrucci (vincenzo.petrucci@gmail.com)
	http://www.vincenzopetrucci.it/ph.ex/
	https://github.com/nahime/ph.ex
	
		@version 0.0.2
**/

class phex
{
	public static $P = array();

	/**
		Provides isMETHOD and routeMETHOD.
		METHOD could be one of the defined http ACCEPTED_METHODS
	**/
	static function __callStatic($method, $arguments) {
		if(substr($method,0,2) == "is" &&
			in_array(
				substr($method,2,strlen($method)),
				self::$P['ACCEPTED_METHODS']))
		{
			$http_method = substr($method,2,strlen($method));
			return $http_method == $_SERVER['REQUEST_METHOD'] ? true : false;
		}
		elseif(substr($method,0,5) == "route" &&
			in_array(
				substr($method,5,strlen($method)),
				self::$P['ACCEPTED_METHODS']))
		{
			$http_method = substr($method,5,strlen($method));
			if ($http_method == $_SERVER['REQUEST_METHOD'])
			{
				self::route($arguments[0], $arguments[1], $arguments[2]);
			}
		}
	}

	/**
		Loads vars from ini file
			@param $file string
	**/
	static function config($file)
	{
		if(is_file($file))
		{
			$d = parse_ini_file($file);
			foreach($d as $k => $v)
			{
				self::set($k, $v);
			}
		}
		else
		{
			trigger_error("Non existing config file");
		}
	}

	/**
		Define a route
			@param $name string
			@param $route string
			@param $callback mixed
	**/
	static function route($name, $route, $callback)
	{
		self::$P['URLS'][$name] = str_replace('@', '?', $route);
		if(isset(self::$P['ROUTES'][0]) && isset(self::$P['ROUTES'][0]['CALLBACK']))
		{
			/*
			* Still exists an exact match route, we can skip all others.
			*/
			return;
		}
		if(strpos($route, "@") === false)
		{
			if($_SERVER['REQUEST_URI'] == $route)
			{
				self::$P['ROUTES'][0]['NAME'] = $name;
				self::$P['ROUTES'][0]['CALLBACK'] = $callback;
				self::$P['ROUTES'][0]['PARAMS'] = null;
			}
		}
		else
		{
			$route_data = explode("/", trim($route, '/'));
			$uri_data = explode("/", trim($_SERVER['REQUEST_URI'], '/'));
			$n_pars = substr_count($route, "@");
			if (sizeof($route_data) == sizeof($uri_data))
			{
				$candidate_route = array();
				for($i = 0; $i < sizeof($route_data); $i++)
				{
					$token_route = $route_data[$i];
					$token_uri = $uri_data[$i];
					if(substr($token_route,0,1) !== "@" && $token_route !== $token_uri)
					{
						return;
					}
					elseif(substr($token_route,0,1) == "@")
					{
						$candidate_route[substr($token_route,1,strlen($token_route))] = $token_uri;
					}
				}
				self::$P['ROUTES'][$n_pars]['NAME'] = $name; 
				self::$P['ROUTES'][$n_pars]['PARAMS'] = $candidate_route; // Should save also some other data
				self::$P['ROUTES'][$n_pars]['CALLBACK'] = $callback;
			}
		}
	}
	
	/**
		Redirects the user
			@param $loc string
	**/
	static function reroute($loc)
	{
		header("Location: ".$loc);
		exit();
	}

	/**
		Starts the system
		Implement call function and run it with callback arguments
		call must work also with files.
	**/
	static function run()
	{
		if(sizeof(self::$P['ROUTES']) == 0)
		{
			self::error(404);
		}
		$autoloads = explode(";", self::$P['AUTOLOAD']);
		$al_paths = array();
		foreach($autoloads as $autoload)
		{
			$al_paths[] = trim($autoload);
		}
		if(sizeof($al_paths) > 0)
		{
			set_include_path(
				get_include_path().
				PATH_SEPARATOR.
				implode(PATH_SEPARATOR, $al_paths).
				PATH_SEPARATOR.
				str_replace('phex.php', '', __FILE__)."mods/"
			);
		}
		spl_autoload_extensions(".php");
		spl_autoload_register();
		ob_start();
		ksort(self::$P['ROUTES']);
		foreach(self::$P['ROUTES'] as $route)
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
					elseif(is_callable($callback))
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
			} /* if(is_array($callbacks)) */
			self::$P['ROUTE'] = $route;
			break;
		} /* foreach(self::$P['ROUTES] as $route) */
		$response = ob_get_clean();
		echo $response;
	}

	/**
		Retrieve saved var
			@param $item string
	**/
	static function get($item)
	{
		return eval('
			return isset(self::$P[\''.str_replace('.','\'][\'', $item).'\']) ?
				self::$P[\''.str_replace('.','\'][\'', $item).'\'] :
				null;
		');
	}

	/**
		Set var
			@param $item string
			@param $value mixed
			@param $recursive boolean
	**/
	static function set($item, $value, $recursive = false)
	{
		if(strpos($item, '.') > 0)
		{
			$item = explode('.', $item, 2);
			$thisitem = $item[0];
			$nextitem = $item[1];
			return $recursive ?
				array($thisitem => self::set($nextitem, $value, true)) :
				self::$P[$thisitem] = self::set($nextitem, $value, true);
		}
		else
		{
			return $recursive ? array($item => $value) : (self::$P[$item] = $value);
		}
	}

	/**
		Show an error
			@param $code integer
	**/
	static function error($code)
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

/**
	Default configuration
**/
phex::set('AUTOLOAD', '');
phex::set('VERSION', '0.0.2');
phex::set('ACCEPTED_METHODS', array('GET', 'POST'));
?>