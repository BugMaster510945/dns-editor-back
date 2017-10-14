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
	array('name' => 'A', 'regexp' => '(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?).(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?).(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?).(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)'),
	array('name' => 'CNAME', 'regexp' => '(?:[^.]{1,63}\.)+'),
	array('name' => 'AAAA', 'regexp' => '((([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}:[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){5}:([0-9A-Fa-f]{1,4}:)?[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){4}:([0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){3}:([0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){2}:([0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}((b((25[0-5])|(1d{2})|(2[0-4]d)|(d{1,2}))b).){3}(b((25[0-5])|(1d{2})|(2[0-4]d)|(d{1,2}))b))|(([0-9A-Fa-f]{1,4}:){0,5}:((b((25[0-5])|(1d{2})|(2[0-4]d)|(d{1,2}))b).){3}(b((25[0-5])|(1d{2})|(2[0-4]d)|(d{1,2}))b))|(::([0-9A-Fa-f]{1,4}:){0,5}((b((25[0-5])|(1d{2})|(2[0-4]d)|(d{1,2}))b).){3}(b((25[0-5])|(1d{2})|(2[0-4]d)|(d{1,2}))b))|([0-9A-Fa-f]{1,4}::([0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})|(::([0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){1,7}:))'),
	array('name' => 'PTR', 'regexp' => '(?:[^.]{1,63}\.)+'),
	array('name' => 'SRV', 'regexp' => '(?:(?:6553[0-5]|655[0-2]\d|65[0-4]\d\d|6[0-4]\d{1,3}|[0-5]?\d{1,4})\s+){3}(?:[^.]{1,63}\.)+'),
	array('name' => 'NS', 'regexp' => '(?:[^.]{1,63}\.)+'),
	array('name' => 'MX', 'regexp' => '(?:6553[0-5]|655[0-2]\d|65[0-4]\d\d|6[0-4]\d{1,3}|[0-5]?\d{1,4})\s+(?:[^.]{1,63}\.)+'),
	array('name' => 'TXT', 'regexp' => '')
);

sendJSON( $reponse );

return true;
