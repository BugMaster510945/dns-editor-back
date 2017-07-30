<?php

function URLMapper_getSchemeHost($s, $use_forwarded_host=false)
{
    $ssl      = ( ! empty( $s['HTTPS'] ) && $s['HTTPS'] == 'on' );
    $sp       = strtolower( $s['SERVER_PROTOCOL'] );
    $protocol = substr( $sp, 0, strpos( $sp, '/' ) ) . ( ( $ssl ) ? 's' : '' );
    $port     = $s['SERVER_PORT'];
    $port     = ( ( ! $ssl && $port=='80' ) || ( $ssl && $port=='443' ) ) ? '' : ':'.$port;
    $host     = ( $use_forwarded_host && isset( $s['HTTP_X_FORWARDED_HOST'] ) ) ? $s['HTTP_X_FORWARDED_HOST'] : ( isset( $s['HTTP_HOST'] ) ? $s['HTTP_HOST'] : null );
    $host     = isset( $host ) ? $host : $s['SERVER_NAME'] . $port;
    return $protocol . '://' . $host;
}

function URLMapper_getFullUrl($s, $use_forwarded_host= false)
{
    return URLMapper_getSchemeHost($s, $use_forwarded_host) . $s['REQUEST_URI'];
}

function URLMapper_include($URLMapper_file, $URLMapper_data, $URLMapper_path=null, $URLMapper_method=null)
{
	$ret = false;
	if( is_readable($URLMapper_file) )
		$ret = include($URLMapper_file);

	return $ret === true || $ret === 1;
}

function URLMapper_Redirect($destination, $regex, $permanent, $path, $method)
{
	$destination = preg_replace($regex, $destination, $path);
	if( is_null(parse_url($destination, PHP_URL_SCHEME)) )
	{
		$destination = http_build_url(URLMapper_getFullUrl($_SERVER),
			array('path' => $destination));
	}

	Header('Location: '.$destination, true, $permanent?'301':'307');
	return true;
}

class URLMapper
{
	protected $__mapping;

	public function __construct()
	{
		$this->__mapping = array();
	}

	public function addMapping($regex, $function, $method=null, $needPath=false)
	{
		$this->__mapping[] = array('regex' => $regex, 'func' => $function, 'method' => $method, 'needPath' => $needPath);
	}

	public function addRedirectMapping($regex, $destination, $permanent=false, $method=null)
	{
		$this->__mapping[] = array('regex' => $regex, 'func' => 
			function($data, $path, $method) use($regex, $destination, $permanent)
			{
				return URLMapper_Redirect($destination, $regex, $permanent, $path, $method);
			}
			, 'method' => $method, 'needPath' => true);
	}

	public function map($path, $method)
	{
		foreach( $this->__mapping as $lmap )
		{
			$data = null;
			$msg = sprintf('Checking path "%s" to regex "%s" : ', $path, $lmap['regex']);
			if( preg_match($lmap['regex'], $path, $data) )
			{
				doLog($msg."Matched");
				if( is_null($method)
				 || is_null($lmap['method'])
				 || !is_string($method)
				 || (is_string($lmap['method']) && $method == $lmap['method'])
				 || (is_array($lmap['method']) && in_array($method, $lmap['method'])) 
				)
				{
					$param = array($data);
					if( $lmap['needPath'] )
					{
						$param[] = $path;
						$param[] = $method;
					}
					if( is_string($lmap['func']) && (substr($lmap['func'], 0, 1) === '/') )
					{ // File template
						if( call_user_func_array('URLMapper_include', array_merge(array($lmap['func']), $param)) )
							return true;
					}
					else
					{ // Fonction call
						if( call_user_func_array($lmap['func'], $param) )
							return true;
					}
					unset($param);
				}
			}
			else
				doLog($msg."Mismatch");
		}
		return false;
	}
}

