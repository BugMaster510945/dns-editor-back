<?php

if( !checkTokenValidity() )
{
	header('WWW-Authenticate: Token');
	throw new appException(401);
}

renewToken();

return false;
