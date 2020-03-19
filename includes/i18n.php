<?php
	$locale = $appDefaultLanguage
	if( array_key_exists('HTTP_ACCEPT_LANGUAGE', $_SERVER) )
		$locale = locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']);
	if( !array_key_exists($locale, $appSupportedLanguages) )
		$locale = $appDefaultLanguage;
	$locale = $appSupportedLanguages[$locale]['code'];

	putenv('LC_ALL='. $locale);
	setlocale(LC_ALL, $locale);
	bindtextdomain('dns-editor-back', PATH_BASE.'/locale');
	bind_textdomain_codeset('dns-editor-back', 'UTF-8');
	textdomain('dns-editor-back');
	unset($locale);
