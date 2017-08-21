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
 *     @SWG\Schema(ref="#/definitions/simpleAPIError")
 *   ),
 *   @SWG\Response(
 *     response=401,
 *     description="authorization required",
 *     @SWG\Schema(ref="#/definitions/simpleAPIError")
 *   ),
 *   @SWG\Response(
 *     response=403,
 *     description="zone access is not allowed",
 *     @SWG\Schema(ref="#/definitions/simpleAPIError")
 *   ),
 *   @SWG\Response(
 *     response=500,
 *     description="internal error",
 *     @SWG\Schema(ref="#/definitions/simpleAPIError")
 *   ),
 *   @SWG\Response(
 *     response=504,
 *     description="zone access is not allowed by dns server",
 *     @SWG\Schema(ref="#/definitions/simpleAPIError")
 *   ),
 *   @SWG\Response(
 *     response="default",
 *     description="unknown error",
 *     @SWG\Schema(ref="#/definitions/simpleAPIError")
 *   )
 * )
 */

$bad_parameter = false;
$errors = array();
if( count($URLMapper_data) != 3 )
	throw new appException(400, array( sprintf(_('Require %d parameters', 2)) ) );

getPOST();

$filter_args = array(
	'ttl'     => array('filter' => FILTER_VALIDATE_INT, 'options' => array('min_range' => 1, 'max_range' => 2147483647)),
	'type'    => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '/^[A-Z]+/') ),
	'data'    => array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => '/^.+/') )
);

if( !array_key_exists('new', $_POST) )
	throw new appException(400, array( sprintf(_('Field %s: is required'), 'new') ) );

if( !is_array($_POST['new']) )
	throw new appException(400, array( sprintf(_('Field %s: must be an array'), 'new') ) );

$_POST['new'] = filter_var_array_errors($_POST['new'], $filter_args, $errors, false, 'new.');

if( !array_key_exists('type', $_POST['new']) )
	$errors[] = sprintf(_('Field %s: is required'), 'new.type');
if( !array_key_exists('data', $_POST['new']) )
	$errors[] = sprintf(_('Field %s: is required'), 'new.data');

if( array_key_exists('old', $_POST) && !is_array($_POST['old']) )
	throw new appException(400, array( sprintf(_('Field %s: must be an array', 'old')) ) );

if( array_key_exists('old', $_POST) )
	$_POST['old'] = filter_var_array_errors($_POST['old'], $filter_args, $errors, false, 'old.');
else
	$_POST['old'] = null;

if( count($errors) != 0 )
	throw new appException(400, $errors);

$data = getTokenPrivate();
$zone = Zones::getZone($URLMapper_data[1], $data, true);

if( is_null($zone) )
	throw new appException(403);

#$newEntry = Net_DNS2_RR::fromString(implode($dnsEntry, ' '));

list($ret, $msg) = $zone->updateEntry($URLMapper_data[2], $_POST['new'], $_POST['old'] );
if( array_key_exists('old', $_POST) )
{
}
else

var_dump($newEntry);
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
