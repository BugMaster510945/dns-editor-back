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
 *     description="Backend d'edition de zones DNS",
 *     @SWG\Contact(
 *       name="Frédéric Planchon",
 *       email="github@planchon.org",
 *       url="http://www.planchon.org"
 *     )
 *   )
 * )
 */

$url = parse_url(URL_BASE.URL_API_BASE_PATH);
define('HTTP_HOST', $url['host']. (isset($parsed_url['port']) ? ':' . $parsed_url['port'] : ''));
define('HTTP_SCHEME', $url['scheme']);
unset($url);

$doCache = defined('CACHE_DIR') && is_dir(CACHE_DIR);

$stack = array( PATH_BASE );
$exclude = array('vendor', 'old', 'dns-editor-front', 'named-zone', 'netdns2');

$files = array();
$last_modif = 0;

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
var_dump($files);

exit;


$need_extract = true;

if( $doCache )
{
	$data = null;
	if( is_readable(CACHE_DIR.'/docs.json') )
		$data = unserialize(file_get_contents(CACHE_DIR.'/docs.json'));
	if( !is_array($data) ) 
		$data = array();

	if( array_key_exists('files', $data) )
	{
		$last_modif = 0;
		foreach ($data['files'] as $file)
		{
			$last_modif = max($last_modif, @filemtime($file));
		}
		$need_extract = $data['filemtime'] < $last_modif;
	}
	
}

if( $need_extract )
{
	$data = array( 'files' => [] );
	$analyser = new \Swagger\StaticAnalyser();
	$analysis = new \Swagger\Analysis();
	$processors = \Swagger\Analysis::processors();

	// Crawl directory and parse all files
	$finder = \Swagger\Util::finder(PATH_BASE, array('vendor', 'old', 'dns-editor-front', 'named-zone', 'netdns2'));
	foreach ($finder as $file)
	{
		$f = $file->getPathname();
		$data['files'][] = $f;
		$analysis->addAnalysis($analyser->fromFile($f));
	}
	// Post processing
	$analysis->process($processors);
	// Validation (Generate notices & warnings)
	$analysis->validate();
	$swagger = $analysis->swagger;
	if( $doCache )
	{
		$data['filemtime'] = time();
		$data['swagger'] = $swagger;
		file_put_contents(CACHE_DIR.'/docs.json', serialize($data));
	}
}
else
{
	$swagger = $data['swagger'];
}

sendJSON($swagger);

return true;
