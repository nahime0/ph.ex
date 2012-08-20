<?php

/**
	ph.ex Framework
	
	Copyright (c) 2012
	Vincenzo Petrucci (vincenzo.petrucci@gmail.com)
	http://www.vincenzopetrucci.it/ph.ex/
	https://github.com/nahime/ph.ex
	
		@version 0.0.2
**/

class phex_tpl
{
	static $text;
	static $otag = '{{';
	static $etag = '}}';
	
	/**
		Load a tpl file and parse its content
			@param $file string
	**/
	static function serve($file)
	{
		if(is_file($file))
		{
			self::$text = file_get_contents($file);
			echo self::parse();
		}
		else
		{
			phex::error(404);
		}
	}
	
	/**
		Parse the current content of self::$text variable.
		If text is provided it will be set as self::$text and evaluated.
			@param $text string
	**/
	static function parse($text = null)
	{
		$lotag = strlen(self::$otag);
		$letag = strlen(self::$etag);
		$text = $text === null ? self::$text : $text;
		$ret = "?>";
		$pos = 0;
		$bal = 0;
		$len = strlen($text);
		$i = 0;
		while($pos < $len && $i < 10)
		{
			$i++;
			/* Look for opening tag */
			if(($opos = strpos($text, self::$otag, $pos)) === false)
			{
				$ret .= substr($text, $pos);
				$pos = $len;
			}
			else
			{
				/* Previous "outside tag" text */
				$ret .= substr($text, $pos, ($opos - $pos));
				/* Look for ending tag */
				if(($epos = strpos($text, self::$etag, $opos)) === false)
				{
					/* Unbalanced Tag */
				}
				else
				{
					$tdata = substr($text, ($opos + $lotag), ($epos - ($opos + $lotag)));
					$pdata = self::parseTag($tdata);
					if(is_array($pdata))
					{
						/* Block */
						if($pdata['open'])
						{
							$bal++;
						}
						else
						{
							$bal--;
						}
						$ret .= $pdata['data'];
					}
					else
					{
						/* String result */
						$ret .= $pdata;
					}
					$pos = ($epos + $letag);
				}
			}
		}
		if($bal == 0)
		{
			$ret .= "<?";
			//echo $ret;
			return eval($ret);
		}
		else
		{
			/* Unbalanced Three */
			echo "ERROR, UNBALANCED";
		}
	}
	
	/**
	
	**/
	static function parseTag($tdata)
	{
		$tdata = trim($tdata);
		if(substr($tdata,0,1) == "/")
		{
			return array('open' => false, 'data' => '<? } ?>');
		}
		if(substr($tdata,0,6) == 'repeat')
		{
			$group = $value = $key = array();
			preg_match('/group="([^"]*)"/', $tdata, $group);
			preg_match('/value="([a-zA-Z0-9.]*)"/', $tdata, $value);
			preg_match('/key="([a-zA-Z0-9.]*)"/', $tdata, $key);
			if(sizeof($group) == 0 || sizeof($value) == 0)
			{
				/* Missing mandatory parameters */
				echo "ERROR";
				return;
			}
			$key = isset($key[1]) ? $key[1] : null;
			$group = preg_replace('/@([a-zA-Z0-9.]*)/', "phex::get('\\1')", $group[1]);
			$value = $value[1];
			$vname = 'vn'.rand(10,99).rand(100,999).rand(10,99);
			$kname = 'kn'.rand(10,99).rand(100,999).rand(10,99);
			if($key === null)
			{
				$tdata = 
					'<? foreach('.$group.' as $'.$vname.') {'.
					'phex::set("'.$value.'", $'.$vname.'); ?>';
			}
			else
			{
				$tdata = 
					'<? foreach('.$group.' as $'.$kname.' => $'.$vname.') {'.
					'phex::set("'.$key.'", $'.$kname.');'.
					'phex::set("'.$value.'", $'.$vname.'); ?>';
			}
			return array('open' => true, 'data' => $tdata);
		}
		else
		{
			$tdata = preg_replace('/@([a-zA-Z0-9.]*)/', "phex::get('\\1')", $tdata);
			return "<?=".$tdata."?>";
		}
	}
}
?>