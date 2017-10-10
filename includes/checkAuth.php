<?php

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\ValidationData;

function getNewToken($private=null, $issuedAt=null)
{
	$now = time();

	if( is_null($issuedAt) )
		$issuedAt = $now;

	$signer = new Sha256();

	$token = (new Builder())
		->setIssuer(JWT_ISSUER)
		->setAudience(JWT_AUDIENCE)
		->setId(JWT_ID)
		->setIssuedAt($issuedAt)
		->setNotBefore($now)
		->setExpiration($now + JWT_SESSION_LIFETIME)
		->set('private', serialize($private))
		->sign($signer, JWT_SECRET)
		->getToken();

	Header('Token: '.(string) $token);

	unset($signer);
	unset($token);
}

function clearToken()
{
	Header('Token: deleteme');
}


function getTokenFromHeader()
{
	$token=null;
	$headers=array();

	foreach (getallheaders() as $k => $v)
		$headers[strtolower($k)] = $v;

	try
	{
		if( array_key_exists('authorization', $headers) )
		{
			$token = (new Parser())->parse((string) $headers['authorization']);

			if( !is_null( $token ) )
			{
				$data = new ValidationData(); // It will use the current time to validate (iat, nbf and exp)
				$data->setIssuer(JWT_ISSUER);
				$data->setAudience(JWT_AUDIENCE);
				$data->setId(JWT_ID);

				if( !$token->validate($data) )
					$token = null;

				unset($data);
			}

			if( !is_null( $token ) )
			{
				$signer = new Sha256();

				if( ! $token->verify($signer, JWT_SECRET) )
					$token = null;
				unset($signer);
			}
		}
	}
	catch(Exception $e)
	{
		$token = null;
		throw new appException(500, $e);
	}
	unset($headers);

	return $token;
}

function getToken()
{
	static $TOKEN = null;

	if( is_null($TOKEN) )
		$TOKEN = getTokenFromHeader();

	return $TOKEN;
}

function getTokenPrivate()
{
	static $PRIVATE = null;

	if( is_null($PRIVATE) )
	{
		$token = getToken();
		if( !is_null($token) )
			$PRIVATE = unserialize($token->getClaim('private'));
		unset($token);
	}

	return $PRIVATE;
}

function checkTokenValidity()
{
	return !is_null(getToken());
}

function renewToken()
{
	getNewToken(getTokenPrivate(), getToken()->getClaim('iat'));
}
