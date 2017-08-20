<?php

/**
 * @SWG\Get(
 *   path="/zones",
 *   summary="Retrieve zone list",
 *   tags={ "zones" },
 *   security={{"token":{}}},
 *   @SWG\Response(
 *     response=200,
 *     description="successful operation",
 *     @SWG\Schema(
 *       type="array",
 *       @SWG\Items(ref="#/definitions/zone")
 *     ),
 *     @SWG\Header(
 *       header="Token",
 *       type="string",
 *       description="updated credentials"
 *     )
 *   ),
 *   @SWG\Response(
 *     response=401,
 *     description="authorization required",
 *     @SWG\Schema(ref="#/definitions/simpleApiResponse")
 *   ),
 *   @SWG\Response(
 *     response="default",
 *     description="unknown error",
 *     @SWG\Schema(ref="#/definitions/simpleApiResponse")
 *   )
 * )
 */
$data=getTokenPrivate();

$reponse = Zones::getListZones($data);

sendJSON( $reponse );

return true;
