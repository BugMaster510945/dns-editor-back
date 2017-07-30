<?php

define('PATH_BASE', dirname(dirname(__FILE__)));

require_once(PATH_BASE.'/includes/common.php');

$path = '/';
if( array_key_exists('PATH_INFO', $_SERVER) )
	$path = $_SERVER['PATH_INFO'];

$MAPPER->map($path, $_SERVER['REQUEST_METHOD']);
