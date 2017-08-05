<?php

/**
 * @SWG\Post(
 *   path="/authenticate",
 *   summary="Genere un token nécessaire au requetages de l'API",
 *   tags={ "Authentication" },
 *   @SWG\Parameter(
 *     name="user",
 *     in="body",
 *     description="Nom d'utilisateur",
 *     required=true,
 *     @SWG\Schema(
 *       type="object",
 *       @SWG\Property(
 *         property="user",
 *         type="string"
 *       )
 *     )
 *   ),
 *   @SWG\Response(
 *     response=200,
 *     description="desc"
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

		$reponse = array('status' => 201, 'detail' => 'Created');
		$reponse = array('info' => _('Created'), 'detail' => _('The request has been fulfilled, resulting in the creation of a new user\'s session'));

		sendJSON($reponse);

		return true;
	}
}

return false;
