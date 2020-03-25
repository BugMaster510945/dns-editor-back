<?php

/**
 * @SWG\Get(
 *   path="/logout",
 *   summary="Logout user from the system",
 *   tags={ "auth" },
 *   security={{"token":{}}},
 *   @SWG\Response(
 *     response=204,
 *     description="successful operation",
 *     @SWG\Schema(type="object"),
 *     @SWG\Header(
 *       header="Token",
 *       type="string",
 *       description="Credentials to request system"
 *     )
 *   ),
 *   @SWG\Response(
 *     response=401,
 *     description="authorization required",
 *     @SWG\Schema(ref="#/definitions/simpleAPIError")
 *   )
 * )
 */

http_response_code(204);
Header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
Header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date dans le passé
Header('Pragma: no-cache');

clearToken();

return true;
