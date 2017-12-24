<?php

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
 *     response=204,
 *     description="successful operation",
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
	throw new appException(400, array( sprintf(_('Require %d parameters'), 2)) );

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

$zone->updateEntrySimple($URLMapper_data[2], $_POST['new'], $_POST['old'] );

header(204);

return true;
