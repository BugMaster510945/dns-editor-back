<?php

$data=getTokenPrivate();

$reponse = array('status' => 200, 'detail' => Zones::getListZones($data) );

sendJSON( $reponse );

return true;
