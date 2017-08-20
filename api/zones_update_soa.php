<?php

/**
 * @SWG\Patch(
 *   path="/zones/{name}/soa",
 *   summary="Update SOA record",
 *   tags={ "zones" },
 *   security={{"token":{}}},
 *   @SWG\Parameter(
 *     name="name",
 *     in="path",
 *     required=true,
 *     type="string",
 *     description="zone name"
 *   ),
 *   @SWG\Parameter(
 *     name="body",
 *     in="body",
 *     required=true,
 *     @SWG\Schema(ref="#/definitions/zoneSOA"),
 *   ),
 *   @SWG\Response(
 *     response=200,
 *     description="successful operation",
 *     @SWG\Schema(ref="#/definitions/zoneSOA"),
 *     @SWG\Header(
 *       header="Token",
 *       type="string",
 *       description="updated credentials"
 *     )
 *   ),
 *   @SWG\Response(
 *     response=400,
 *     description="missing or invalid parameter",
 *     @SWG\Schema(ref="#/definitions/simpleApiResponse")
 *   ),
 *   @SWG\Response(
 *     response=401,
 *     description="authorization required",
 *     @SWG\Schema(ref="#/definitions/simpleApiResponse")
 *   ),
 *   @SWG\Response(
 *     response=403,
 *     description="zone access is not allowed",
 *     @SWG\Schema(ref="#/definitions/simpleApiResponse")
 *   ),
 *   @SWG\Response(
 *     response=500,
 *     description="internal error",
 *     @SWG\Schema(ref="#/definitions/simpleApiResponse")
 *   ),
 *   @SWG\Response(
 *     response=504,
 *     description="zone access is not allowed by dns server",
 *     @SWG\Schema(ref="#/definitions/simpleApiResponse")
 *   ),
 *   @SWG\Response(
 *     response="default",
 *     description="unknown error",
 *     @SWG\Schema(ref="#/definitions/simpleApiResponse")
 *   )
 * )
 */

$bad_parameter = false;
$bad_parameter = $bad_parameter || count($URLMapper_data) != 2;

getPOST();

$filter_args = array(
	'responsible' => FILTER_VALIDATE_EMAIL,
	'refresh'     => array('filter' => FILTER_VALIDATE_INT, 'options' => array('min_range' => 1, 'max_range' => 2147483647)),
	'retry'       => array('filter' => FILTER_VALIDATE_INT, 'options' => array('min_range' => 1, 'max_range' => 2147483647)),
	'expire'      => array('filter' => FILTER_VALIDATE_INT, 'options' => array('min_range' => 1, 'max_range' => 2147483647)),
	'minimum'     => array('filter' => FILTER_VALIDATE_INT, 'options' => array('min_range' => 1, 'max_range' => 2147483647))
);
$_POST = filter_var_array ($_POST, $filter_args, true);

foreach($_POST as $key => $value)
	$bad_parameter = $bad_parameter || ($value === false);

if( $bad_parameter )
{
	http_response_code(400);
	sendJSON( array('info' => _('Bad Request'), 'detail' => _('The server cannot or will not process the request due to an apparent client error')) );
	return true;
}

$data = getTokenPrivate();
$zone = Zones::getZone($URLMapper_data[1], $data, true);

if( is_null($zone) )
{
	http_response_code(403);
	sendJSON( array('info' => _('Forbidden'), 'detail' => _('The server is refusing action. The user might not have the necessary permissions for a resource') ));
	return true;
}

$soa = $zone->getSOA();
if( is_null($soa) )
{
	http_response_code(504);
	sendJSON( array('info' => _('Gateway Time-out'), 'detail' => _('The server was acting as a gateway or proxy and did not receive a timely response from the upstream server') ));
	return true;
}

$_POST['rname'] = $_POST['responsible'];
unset($_POST['responsible']);

$soa->rname = Zones::getSOAEmail( $soa->rname );

foreach($_POST as $key => $value)
	if( is_null($value) )
		$_POST[$key] = $soa->$key;

// https://github.com/dotse/zonemaster/tree/master/docs/specifications/tests/Zone-TP
$filter_args = array(
	'rname'       => array('filter' => FILTER_VALIDATE_EMAIL),
	'refresh'     => array('filter' => FILTER_VALIDATE_INT, 'options' => array('min_range' => max(14400, $_POST['retry']+1), 'max_range' => $_POST['expire'] ) ),
	'retry'       => array('filter' => FILTER_VALIDATE_INT, 'options' => array('min_range' => 3600, 'max_range' => $_POST['refresh']-1) ),
	'expire'      => array('filter' => FILTER_VALIDATE_INT, 'options' => array('min_range' => max(604800, $_POST['refresh']), 'max_range' => 2147483647)),
	'minimum'     => array('filter' => FILTER_VALIDATE_INT, 'options' => array('min_range' => 300, 'max_range' => 86400))
);

$_POST = filter_var_array ($_POST, $filter_args, true);

$errors=array();
foreach($_POST as $key => $value)
{
	if( $value === false )
	{
		if( $filter_args[$key]['filter'] == FILTER_VALIDATE_EMAIL )
			$errors[] = sprintf(_('Field %s: must be a valid email address'), $key);
		if( $filter_args[$key]['filter'] == FILTER_VALIDATE_INT )
			$errors[] = sprintf(_('Field %s: must be an integer between %d and %d'), $key, $filter_args[$key]['options']['min_range'], $filter_args[$key]['options']['max_range']);
	}
	else
		$soa->$key = $value;
}

if( count($errors) != 0 )
{
	http_response_code(400);
	sendJSON( array('info' => _('Bad Request'), 'detail' => _('The server cannot or will not process the request due to an apparent client error'), 'errors' => $errors ) );
	return true;
}

$soa->serial += 1;
$soa->rname = Zones::getSOArname($soa->rname);

list($ret, $msg) = $zone->setSOA($soa);

if( $ret )
{
	$soa = $zone->getSOAObject();
	if( is_null($soa) )
	{
		http_response_code(500);
		sendJSON( array('info' => _('Internal Server Error'), 'detail' => _('The server is unable to get SOA of an updated zone') ));
		return true;
	}
	sendJSON( $soa );
	return true;
}

http_response_code(500);
if( is_null($msg) )
{
	sendJSON( array('info' => _('Internal Server Error'), 'detail' => _('The server is unable to update SOA') ));
	return true;
}
sendJSON( array('info' => _('Internal Server Error'), 'detail' => _('The server is unable to update SOA'), 'errors' => array($msg) ));
return true;
