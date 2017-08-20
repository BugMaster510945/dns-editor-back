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
 *     response="default",
 *     description="unknown error",
 *     @SWG\Schema(ref="#/definitions/simpleApiResponse")
 *   )
 * )
 */
if( count($URLMapper_data) != 2 )
{
	http_response_code(400);
	sendJSON( array('info' => _('Bad Request'), 'detail' => _('The server cannot or will not process the request due to an apparent client error')) );
	return true;
}

$data = getTokenPrivate();
$zone = Zones::getZone($URLMapper_data[1], $data);

if( is_null($zone) )
{
	http_response_code(403);
	sendJSON( array('info' => _('Forbidden'), 'detail' => _('The server is refusing action. The user might not have the necessary permissions for a resource') ));
	return true;
}

$reponse=$zone->getZoneEntriesObject();
//$reponse=$zone::sortEntries($zone->getFilteredEntries());

//Zones::getZone()
//$reponse = array('status' => 200, 'detail' => Zones::getListZones($data) );

sendJSON( $reponse );
//sendJSON( array('entries' => $reponse) );

return true;
