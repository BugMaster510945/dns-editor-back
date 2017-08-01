<?php

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

$reponse=$zone::sortEntries($zone->getFilteredEntries());

//Zones::getZone()
//$reponse = array('status' => 200, 'detail' => Zones::getListZones($data) );

sendJSON( array('entries' => $reponse) );

return true;
