<?php

/**
 * @SWG\Post(
 *   path="/login",
 *   summary="Logs user into the system",
 *   tags={ "auth" },
 *   @SWG\Parameter(
 *     name="body",
 *     in="body",
 *     required=true,
 *     @SWG\Schema(
 *       type="object",
 *       @SWG\Property(
 *         property="user",
 *         description="The user name for login",
 *         type="string"
 *       ),
 *       @SWG\Property(
 *         property="password",
 *         description="The password for login in clear text",
 *         type="string"
 *       )
 *     )
 *   ),
 *   @SWG\Response(
 *     response=201,
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

getPOST();

if( array_key_exists('user', $_POST) &&
    array_key_exists('password', $_POST) )
{
	$user = Users::login($_POST['user'], $_POST['password']);
	if( !is_null($user) )
	{
		http_response_code(201);
		Header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		Header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date dans le passé
		Header('Pragma: no-cache'); 

		getNewToken($user);

		#$reponse = array('status' => 201, 'detail' => 'Created');
		#$reponse = array('info' => _('Created'), 'detail' => _('The request has been fulfilled, resulting in the creation of a new user\'s session'));

		sendJSON(array());

		return true;
	}
}

return false;
