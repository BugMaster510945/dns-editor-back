<?php

if( !checkTokenValidity() )
{
	http_response_code(401);
	Header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
	Header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date dans le passé
	Header('Pragma: no-cache');

	header('WWW-Authenticate: Token');
	$reponse = array('info' => _('Unauthorized'), 'detail' => _('The requested resource need credentials to be accessed'));

	sendJSON($reponse);

	return true;
}

renewToken();

return false;
