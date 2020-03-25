<?php

/**
 * @SWG\Get(
 *   path="/users",
 *   summary="Retrieve user list",
 *   tags={ "users" },
 *   security={{"admin_token":{}}},
 *   @SWG\Response(
 *     response=200,
 *     description="successful operation",
 *     @SWG\Schema(
 *       type="array",
 *       @SWG\Items(ref="#/definitions/user")
 *     ),
 *     @SWG\Header(
 *       header="Token",
 *       type="string",
 *       description="updated credentials"
 *     )
 *   ),
 *   @SWG\Response(
 *     response="default",
 *     description="unknown error",
 *     @SWG\Schema(ref="#/definitions/simpleAPIError")
 *   )
 * )
 */
$user = getTokenPrivate();
if( $user == null || !$user->isAdmin() )
{
	throw new appException(403);
}

$reponse = Users::loadAllUsers();

sendJSON( $reponse );

return true;
