<?php

/**
 * @SWG\Get(
 *   path="/zones",
 *   summary="Retrieve zone list",
 *   tags={ "zones" },
 *   @SWG\Parameter(
 *     name="Authorization",
 *     in="header",
 *     required=true,
 *     type="string",
 *     description="token access"
 *   ),
 *   @SWG\Response(
 *     response=200,
 *     description="successful operation",
 *     @SWG\Schema(ref="#/definitions/zoneList"),
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
