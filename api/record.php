<?php

/**
 * @SWG\Get(
 *   path="/record",
 *   summary="Retrieve popular record type",
 *   tags={ "record" },
 *   security={{"token":{}}},
 *   @SWG\Response(
 *     response=200,
 *     description="successful operation",
 *     @SWG\Schema(
 *       type="array",
 *       @SWG\Items(
 *         type="object",
 *         @SWG\Property(
 *           property="name",
 *           description="record type name",
 *           type="string"
 *         ),
 *         @SWG\Property(
 *           property="regexp",
 *           description="Regexp to validate entry ercord type",
 *           type="string"
 *         ),
 *       )
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
 *     @SWG\Schema(ref="#/definitions/simpleAPIError")
 *   ),
 *   @SWG\Response(
 *     response="default",
 *     description="unknown error",
 *     @SWG\Schema(ref="#/definitions/simpleAPIError")
 *   )
 * )
 */
$data=getTokenPrivate();

$reponse = array(
	array('name' => 'A', 'regexp' => ''),
	array('name' => 'CNAME', 'regexp' => ''),
	array('name' => 'AAAA', 'regexp' => ''),
	array('name' => 'PTR', 'regexp' => ''),
	array('name' => 'SRV', 'regexp' => ''),
	array('name' => 'NS', 'regexp' => ''),
	array('name' => 'MX', 'regexp' => ''),
	array('name' => 'TXT', 'regexp' => '')
);

sendJSON( $reponse );

return true;
