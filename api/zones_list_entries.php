<?php

/**
 * @SWG\Get(
 *   path="/zones/{name}/entries",
 *   summary="Retrieve zone entries",
 *   tags={ "zones" },
 *   security={{"token":{}}},
 *   @SWG\Parameter(
 *     name="name",
 *     in="path",
 *     required=true,
 *     type="string",
 *     description="zone name"
 *   ),
 *   @SWG\Response(
 *     response=200,
 *     description="successful operation",
 *     @SWG\Schema(ref="#/definitions/zoneEntries"),
 *     @SWG\Header(
 *       header="Token",
 *       type="string",
 *       description="updated credentials"
 *     )
 *   ),
 *   @SWG\Response(
 *     response=400,
 *     description="missing required parameter",
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
 *     response="default",
 *     description="unknown error",
 *     @SWG\Schema(ref="#/definitions/simpleAPIError")
 *   )
 * )
 */
if( count($URLMapper_data) != 2 )
	throw new appException(400, array( sprintf(_('Require %d parameter', 1)) ) );

$data = getTokenPrivate();
$zone = Zones::getZone($URLMapper_data[1], $data);

if( is_null($zone) )
	throw new appException(403);

$reponse=$zone->getZoneEntriesObject();
//$reponse=$zone::sortEntries($zone->getFilteredEntries());

//Zones::getZone()
//$reponse = array('status' => 200, 'detail' => Zones::getListZones($data) );

sendJSON( $reponse );
//sendJSON( array('entries' => $reponse) );

return true;
