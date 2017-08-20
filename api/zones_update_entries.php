<?php

//TODO:
/**
 * @SWG\Patch(
 *   path="/zones/{name}/entries/{entry}",
 *   summary="Update an entry record",
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
 *     name="entry",
 *     in="path",
 *     required=true,
 *     type="string",
 *     description="entry name"
 *   ),
 *   @SWG\Parameter(
 *     name="body",
 *     in="body",
 *     required=true,
 *     @SWG\Schema(
 *       type="object",
 *       @SWG\Property(
 *         property="old",
 *         description="Old value to replace, if none, all entry will be replaced",
 *         ref="#/definitions/zoneEntry",
 *       ),
 *       @SWG\Property(
 *         property="new",
 *         required={"type", "data"},
 *         description="New value to set",
 *         ref="#/definitions/zoneEntry",
 *       ),
 *     ),
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
$errors = array();
$bad_parameter = $bad_parameter || count($URLMapper_data) != 3;

getPOST();

$filter_args = array(
	'ttl'     => array('filter' => FILTER_VALIDATE_INT, 'options' => array('min_range' => 1, 'max_range' => 2147483647)),
	'type'    => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '/^[A-Z]+/') ),
	'data'    => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '/^.+/') )
);

$bad_parameter = $bad_parameter || !array_key_exists('new', $_POST) || !is_array($_POST['new']);
if( !$bad_parameter )
		$_POST['new'] = filter_var_array_errors($_POST['new'], $filter_args, $errors, $bad_parameter, 'new.');

$bad_parameter = $bad_parameter ||  is_null($_POST['new']['type']) || is_null($_POST['new']['data']);
if( is_null($_POST['new']['type']) )
	$errors[] = sprintf(_('Field %s: is required'), 'new.type');
if( is_null($_POST['new']['data']) )
	$errors[] = sprintf(_('Field %s: is required'), 'new.ttl');

if( array_key_exists('old', $_POST) )
{
	if( is_array($_POST['old']) )
		$_POST['old'] = filter_var_array_errors($_POST['old'], $filter_args, $errors, $bad_parameter, 'old.');
	else
		unset($_POST['old']);
}

if( $bad_parameter )
{
	http_response_code(400);
	sendJSON( array('info' => _('Bad Request'), 'detail' => _('The server cannot or will not process the request due to an apparent client error'), 'errors' => $errors ) );
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

$dnsEntry = array('name');
if( is_null($_POST['new']['ttl']) )
{
	$soa = $zone->getSOA();
	if( is_null($soa) )
	{
		http_response_code(504);
		sendJSON( array('info' => _('Gateway Time-out'), 'detail' => _('The server was acting as a gateway or proxy and did not receive a timely response from the upstream server') ));
		return true;
	}
	$dnsEntry[] = $soa->minimum;
}
else
	$dnsEntry[] = $_POST['new']['ttl'];

$dnsEntry[] = $_POST['new']['type'];
$dnsEntry[] = $_POST['new']['data'];

Net_DNS2_RR::fromString(implode($dnsEntry, ' '));
var_dump(implode($dnsEntry, ' '));
exit;

	if( !array_key_exists($_POST['new']['type'], Net_DNS2_Lookups::$rr_types_by_name) )
	{
		$bad_parameter = true;
		$errors[] = sprintf(_('Field %s: %s is not a supported dns type'), 'new.key', $_POST['new']['type']);
	}
	else
		$DNSClass = Net_DNS2_Lookups::$rr_types_id_to_class[
			Net_DNS2_Lookups::$rr_types_by_name[ $_POST['new']['type'] ]
		];

if( isset($DNSClass) )
{
	$DNSObject = new $DNSClass;
	#TODO: $DNSObject->name = "";
	$DNSObject->class = 'IN'; # Hardcoded, yes but is this really hurt?, TODO: do a better job
	$DNSObject->ttl = 3600; // TODO

	if ($DNSObject->rrFromString($_POST['new']['data']) === false)
	{
		$bad_parameter = true;
		$errors[] = sprintf(_('Field %s: data is not valid for dns type %s'), 'new.data', $_POST['new']['type']);
	}
}

var_dump($type);exit;

$data = getTokenPrivate();
$zone = Zones::getZone($URLMapper_data[1], $data, true);

if( is_null($zone) )
{
	http_response_code(403);
	sendJSON( array('info' => _('Forbidden'), 'detail' => _('The server is refusing action. The user might not have the necessary permissions for a resource') ));
	return true;
}

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
