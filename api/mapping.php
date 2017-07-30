<?php

$MAPPER = new URLMapper();

#$UUID_Regex = '[a-f\d]{8}(?:-[a-f\d]{4}){3}-[a-f\d]{12}';
# Unauthenticated page
#$MAPPER->addMapping('@^(?:/('.$UUID_Regex.')(?:/('.$UUID_Regex.'))?)?/sms/send$@', PATH_BASE.'/pages/smssend.php');

# Managing Session & Credential
$MAPPER->addMapping('@^/api/authenticate$@', PATH_BASE.'/api/login.php', 'POST');
$MAPPER->addMapping('@^/api/logout$@', PATH_BASE.'/api/logout.php');
$MAPPER->addMapping('@^@', PATH_BASE.'/api/checkAuth.php');

# Authenticated page
$MAPPER->addMapping('@^/api/zones$@', PATH_BASE.'/api/zones.php');
$MAPPER->addMapping('@^/api/zones/([^/]+)/entries$@', PATH_BASE.'/api/zones_entries.php');

#$MAPPER->addMapping('@^/system/(?:users/)?(?:list)?$@', PATH_BASE.'/pages/system.users.r.php');
#$MAPPER->addMapping('@^/system/(?:users/)?create$@', PATH_BASE.'/pages/system.users.c.php');
#$MAPPER->addMapping('@^/system/(?:users/)?delete/([^/]+)$@', PATH_BASE.'/pages/system.users.d.php');
#$MAPPER->addMapping('@^/system/(?:users/)?update/([^/]+)$@', PATH_BASE.'/pages/system.users.u.php');
#$MAPPER->addMapping('@^/system/(?:users/)?(list|create|edit|delete|)(?:/([^/]+))?$@', PATH_BASE.'/pages/system.users.php');
#$MAPPER->addMapping('@^(?:/(\w+))?/metadata$@', PATH_BASE.'/pages/metadata.php');

$MAPPER->addMapping('@^@', PATH_BASE.'/api/404.php');
