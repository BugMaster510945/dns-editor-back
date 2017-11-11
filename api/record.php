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

$hexa = '[\da-fA-F]';
$quadhexa = $hexa .'{1,4}';


$reponse = array(
	array('name' => 'A', 'regexp' => '(?:(?:25[0-5]|2[0-4]\d|[01]?\d\d?)\.){3}(?:25[0-5]|2[0-4]\d|[01]?\d\d?)'),
	array('name' => 'CNAME', 'regexp' => '(?:[^.]{1,63}\.)+'),
	array('name' => 'AAAA', 'regexp' => "(?:(?:$quadhexa:){7}$quadhexa)|(?:(?:$quadhexa:){6}:$quadhexa})|(?:(?:$quadhexa:){5}:(?:$quadhexa:)?$quadhexa)|(?:(?:$quadhexa:){4}:(?:$quadhexa:){0,2}$quadhexa)|(?:(?:$quadhexa:){3}:(?:$quadhexa:){0,3}$quadhexa)|(?:(?:$quadhexa:){2}:(?:$quadhexa:){0,4}$quadhexa)|(?:$quadhexa::(?:$quadhexa:){0,5}$quadhexa)|(?:::(?:$quadhexa:){0,6}$quadhexa)|(?:(?:$quadhexa:){1,7}:)"),
	array('name' => 'PTR', 'regexp' => '(?:[^.]{1,63}\.)+'),
	array('name' => 'SRV', 'regexp' => '(?:(?:6553[0-5]|655[0-2]\d|65[0-4]\d\d|6[0-4]\d{1,3}|[0-5]?\d{1,4})\s+){3}(?:[^.]{1,63}\.)+'),
	array('name' => 'NS', 'regexp' => '(?:[^.]{1,63}\.)+'),
	array('name' => 'MX', 'regexp' => '(?:6553[0-5]|655[0-2]\d|65[0-4]\d\d|6[0-4]\d{1,3}|[0-5]?\d{1,4})\s+(?:[^.]{1,63}\.)+'),
	array('name' => 'TXT', 'regexp' => '(?:"[^"]{1,255}"){1,255}')
);

sendJSON( $reponse );

return true;
