<?php

/**
 * @SWG\Swagger(
 *   schemes={ HTTP_SCHEME },
 *   host=HTTP_HOST,
 *   basePath=URL_API_BASE_PATH,
 *   consumes={ "application/json" },
 *   produces={ "application/json" },
 *   @SWG\Info(
 *     title="DNS Editor Backend API",
 *     version=VERSION,
 *     description="API to edit DNS Zones",
 *     @SWG\Contact(
 *       name="Frédéric Planchon",
 *       email="github@planchon.org",
 *       url="http://www.planchon.org"
 *     )
 *   ),
 *   @SWG\SecurityScheme(
 *     securityDefinition="token",
 *     type="apiKey",
 *     name="Authorization",
 *     in="header"
 *   )
 * )
 */

#Header('Access-Control-Allow-Origin: *');
#Header('Access-Control-Allow-Methods: GET, POST, DELETE, PUT, PATCH, OPTIONS');
#Header('Access-Control-Allow-Headers: Content-Type, api_key, Authorization');


$url = parse_url(URL_BASE.URL_API_BASE_PATH);
define('HTTP_HOST', $url['host']. (isset($parsed_url['port']) ? ':' . $parsed_url['port'] : ''));
define('HTTP_SCHEME', $url['scheme']);
unset($url);

$doCache = defined('CACHE_DIR') && is_dir(CACHE_DIR);

$exclude = array();
$includeDirectory = array('api', 'classes', 'includes');
$stack = array();

$files = array();
$last_modif = 0;

foreach ($includeDirectory as $entry)
{
	$stack[] = PATH_BASE.DIRECTORY_SEPARATOR.$entry;
}

while( count($stack) > 0 )
{
	$path = array_pop($stack);

	if( $dh = opendir($path) )
	{
		while (false !== ($entry = readdir($dh)))
		{
			if( $entry == '.' || $entry == '..' )
				continue;

			if( in_array($entry, $exclude) )
				continue;

			$fullpath = $path.DIRECTORY_SEPARATOR.$entry;
			if( is_dir($fullpath) )
			{
				$stack[] = $fullpath;
				continue;
			}

			$extension = strtolower(pathinfo($fullpath, PATHINFO_EXTENSION));
			if( $extension !== 'php' )
				continue;

			$files[] = $fullpath;
			$last_modif = max($last_modif, @filemtime($fullpath));
		}
		closedir($dh);
	}
}

$swagger = null;
if( $doCache )
{
	if( is_readable(CACHE_DIR.'/docs.json') && ($last_modif <= @filemtime(CACHE_DIR.'/docs.json')) )
		$swagger = unserialize(file_get_contents(CACHE_DIR.'/docs.json'));
	if( ! $swagger instanceof Swagger\Annotations\Swagger ) 
		$swagger = null;
}

if( is_null($swagger) )
{
	$analyser = new \Swagger\StaticAnalyser();
	$analysis = new \Swagger\Analysis();
	$processors = \Swagger\Analysis::processors();

	// Crawl directory and parse all files
	sort($files);
	foreach ($files as $file)
	{
		$analysis->addAnalysis($analyser->fromFile($file));
	}
	// Post processing
	$analysis->process($processors);
	// Validation (Generate notices & warnings)
	$analysis->validate();
	$swagger = $analysis->swagger;

	if( $doCache )
	{
		@file_put_contents(CACHE_DIR.'/docs.json', serialize($swagger));
	}
}

sendJSON($swagger);

return true;
