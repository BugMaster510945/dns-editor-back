<?php

#require_once('Twig/Autoloader.php'); # Twig must be installed on system
require_once(PATH_BASE.'/includes/errorHandler.php');
require_once(PATH_BASE.'/includes/compatibility.php');
require_once(PATH_BASE.'/locale/locale.php');
require_once(PATH_BASE.'/vendor/autoload.php');

if( is_readable( PATH_BASE.'/conf.php') )
	require_once(PATH_BASE.'/conf.php');

require_once(PATH_BASE.'/includes/config.php');
require_once(PATH_BASE.'/includes/i18n.php');
require_once(PATH_BASE.'/includes/functions.php');
#require_once(PATH_BASE.'/includes/Twig.php');
require_once(PATH_BASE.'/includes/URLMapper.php');

#require_once(PATH_BASE.'/external/notorm/NotORM.php');
require_once(PATH_BASE.'/includes/database.php');
#require_once(PATH_BASE.'/includes/multiCurl.php');
require_once(PATH_BASE.'/classes/Users.php');
require_once(PATH_BASE.'/classes/Zones.php');

#require_once(PATH_BASE.'/includes/cleanSession.php');

require_once(PATH_BASE.'/includes/checkAuth.php');
require_once(PATH_BASE.'/api/mapping.php');
