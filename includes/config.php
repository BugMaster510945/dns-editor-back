<?php

if( php_sapi_name() === 'cli' )
{
	define('URL_PATH_BASE', $_SERVER['SCRIPT_NAME']);
	define('URL_BASE', '');
}
else
{
	$base_url = array(
		'scheme' =>
			(array_key_exists('HTTPS', $_SERVER) && 
			($_SERVER['HTTPS'] !== 'off')) ? 'https' : 'http',
		'host' => $_SERVER['HTTP_HOST'],
		'path' => empty($_SERVER['SCRIPT_NAME']) ? '/' : $_SERVER['SCRIPT_NAME']
	);

	if( ( ($base_url['scheme'] == 'http')  && ($_SERVER['SERVER_PORT'] !=  80) ) ||
	    ( ($base_url['scheme'] == 'https') && ($_SERVER['SERVER_PORT'] != 443) ) )
	{
		$base_url['port'] = $_SERVER['SERVER_PORT'];
	}

	if( strripos($base_url['path'], '.php', 0) === (strlen($base_url['path']) - 4)) # 4 = strlen('.php')
		$base_url['path'] = dirname($base_url['path']);

	if( strrpos($base_url['path'], '/', 0) !== (strlen($base_url['path']) - 1)) # 1 = strlen('/')
		$base_url['path'] = $base_url['path'].'/';

	define('URL_PATH_BASE', $base_url['path']);
	define('URL_BASE', http_build_url('',$base_url));
	unset($base_url);
}

$default_globals = array(
	'DEFAULT_THEME' => 'default',
	'TITLE' => 'Bind',
	'JWT_SESSION_GRACETIME' => 300, // 5 minutes
	'JWT_SESSION_LIFETIME' => 1200, // 20 minutes
	'JWT_ISSUER' => URL_BASE,
	'JWT_AUDIENCE' => URL_BASE,
);

foreach($default_globals as $key => $value)
{
	if( !defined($key) )
	{
		$real_value = preg_replace_callback('/\{%\s*(\$?)([a-zA-Z0-9_]+)\s*%\}/', 
			function ($matches)
			{
				$key = $matches[2];
				$value = null;
				if( strlen($matches[1]) > 0
				 && array_key_exists($key, $_GLOBALS) )
					$value = $_GLOBALS[$key];
				elseif( defined($key) )
					$value = constant($key);
				
				if( !is_null($value) )
					return $value;

				return '';
			}, $value);
		define($key, $real_value);
	}
}
