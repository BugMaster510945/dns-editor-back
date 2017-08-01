<?php

$doCache = defined('CACHE_DIR') && is_dir(CACHE_DIR);

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
