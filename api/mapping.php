<?php

$MAPPER = new URLMapper();


#$UUID_Regex = '[a-f\d]{8}(?:-[a-f\d]{4}){3}-[a-f\d]{12}';
# Unauthenticated page
#$MAPPER->addMapping('@^(?:/('.$UUID_Regex.')(?:/('.$UUID_Regex.'))?)?/sms/send$@', PATH_BASE.'/pages/smssend.php');


define('URL_API_BASE_PATH', URL_PATH_BASE.'api/v'.explode('.', VERSION)[0]);

$MAPPER->addMapping('@^'.URL_API_BASE_PATH.'/docs.json$@', PATH_BASE.'/api/docs.php');

# Managing Session & Credential
$MAPPER->addMapping('@^'.URL_API_BASE_PATH.'/login$@', PATH_BASE.'/api/login.php', 'POST');
$MAPPER->addMapping('@^'.URL_API_BASE_PATH.'/logout$@', PATH_BASE.'/api/logout.php');
$MAPPER->addMapping('@^@', PATH_BASE.'/api/checkAuth.php');

# Authenticated page
$MAPPER->addMapping('@^'.URL_API_BASE_PATH.'/zones$@', PATH_BASE.'/api/zones_list.php');
#$MAPPER->addMapping('@^'.URL_API_BASE_PATH.'/zones/([^/]+)$@', PATH_BASE.'/api/zones_entries.php');
$MAPPER->addMapping('@^'.URL_API_BASE_PATH.'/zones/([^/]+)/entries$@', PATH_BASE.'/api/zones_list_entries.php', 'GET');
$MAPPER->addMapping('@^'.URL_API_BASE_PATH.'/zones/([^/]+)/soa$@', PATH_BASE.'/api/zones_update_soa.php', 'PATCH');
$MAPPER->addMapping('@^'.URL_API_BASE_PATH.'/zones/([^/]+)/entries/([^/]+)$@', PATH_BASE.'/api/zones_update_entries.php', 'PATCH');

#$MAPPER->addMapping('@^/system/(?:users/)?(?:list)?$@', PATH_BASE.'/pages/system.users.r.php');
#$MAPPER->addMapping('@^/system/(?:users/)?create$@', PATH_BASE.'/pages/system.users.c.php');
#$MAPPER->addMapping('@^/system/(?:users/)?delete/([^/]+)$@', PATH_BASE.'/pages/system.users.d.php');
#$MAPPER->addMapping('@^/system/(?:users/)?update/([^/]+)$@', PATH_BASE.'/pages/system.users.u.php');
#$MAPPER->addMapping('@^/system/(?:users/)?(list|create|edit|delete|)(?:/([^/]+))?$@', PATH_BASE.'/pages/system.users.php');
#$MAPPER->addMapping('@^(?:/(\w+))?/metadata$@', PATH_BASE.'/pages/metadata.php');

$MAPPER->addMapping('@^@', PATH_BASE.'/api/404.php');
