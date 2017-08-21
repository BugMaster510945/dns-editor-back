<?php

	function return_bytes ($size_str)
	{
		switch (substr ($size_str, -1))
		{
			case 'M': case 'm': return (int)($size_str * 1048576);
			case 'K': case 'k': return (int)($size_str * 1024);
			case 'G': case 'g': return (int)($size_str * 1073741824);
			default: return $size_str;
		}
	}

	function doLog($msg)
	{
		if( defined('DEBUG') && DEBUG )
		{
			$stack = debug_backtrace();
			array_shift($stack); // doLog
			if(count($stack) > 1)
				array_shift($stack); // customFunction
			$msg = sprintf("%s:%d:%s\n", $stack[0]['file'], $stack[0]['line'], $msg);
			unset($stack);
			error_log($msg, 0);
		}
	}

	function userErrorHandler ($errno, $errmsg, $filename, $linenum, $vars = array(), $exit = true)
	{
		if (!($errno & error_reporting())) return false;

		$errortype = array (
			/*    1 */ E_ERROR           => 'Error',
			/*    2 */ E_WARNING         => 'Warning',
			/*    4 */ E_PARSE           => 'Parsing Error',
			/*    8 */ E_NOTICE          => 'Notice',
			/*   16 */ E_CORE_ERROR      => 'Core Error',
			/*   32 */ E_CORE_WARNING    => 'Core Warning',
			/*   64 */ E_COMPILE_ERROR   => 'Compile Error',
			/*  128 */ E_COMPILE_WARNING => 'Compile Warning',
			/*  256 */ E_USER_ERROR      => 'User Error',
			/*  512 */ E_USER_WARNING    => 'User Warning',
			/* 1024 */ E_USER_ERROR      => 'User Notice'
		);

		$response = array(
			'message'  => 'Internal Server Error',
			'details'  => array($errortype[$errno].': '.$errmsg),
			'filename' => str_replace(PATH_BASE.'/', '', $filename),
			'fileline' => $linenum
		);

		if( defined('DEBUG') && DEBUG )
		{
			//$myvars=$vars;

			$liste_global=array('HTTP_SERVER_VARS', '_SERVER', 'HTTP_ENV_VARS', '_ENV', 'sensitive_data');

			foreach ($liste_global as $globalname)
			{
				if( array_key_exists($globalname, $vars) && is_array($vars[$globalname]) )
				{
					$liste_keys=array_keys($vars[$globalname]);
					foreach ( $liste_keys as $key_entry)
					{
						unset($vars[$key_entry]);
					}
					unset($vars[$globalname]);
				}
			}

			foreach( array('HTTP_POST_VARS', 'HTTP_GET_VARS', 'HTTP_COOKIE_VARS', 'HTTP_POST_FILES', '_REQUEST', 'GLOBALS') as $key )
			{
				if( array_key_exists($key, $vars) )
					unset($vars[$key]);
			}

			foreach( array('_POST', '_GET', '_COOKIE', '_FILES') as $key )
			{
				if( array_key_exists($key, $vars) && !count($vars[$key]) )
					unset($vars[$key]);
			}

			$response['vars'] = $vars;

			if( function_exists('debug_backtrace') )
			{
				$stack = debug_backtrace();
				array_shift($stack);
				if( count($stack) > 0 && $stack[0]['function'] == 'userErrorHandlerShutdown' )
					$stack = array();
						//$args = print_r($args, true);
				$response['stacktrace'] = $stack;
			}
		}

		while (@ob_end_clean());

		http_response_code(500);
		Header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		Header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date dans le passé
		Header('Pragma: no-cache');
		header('Content-Type: application/json');

		print json_encode($response);

		if( $exit ) exit;
	}


	function userErrorHandlerShutdown()
	{
		$error = error_get_last();

		if( !is_null($error) && is_array($error) && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING) ) )
			userErrorHandler($error['type'], $error['message'], $error['file'], $error['line'], array(), false );
	}

	set_error_handler('userErrorHandler');
	register_shutdown_function('userErrorHandlerShutdown');

