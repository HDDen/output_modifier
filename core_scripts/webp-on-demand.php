<?php

/*
cache-control: public, max-age=31536000
content-encoding: gzip
content-length: 21016
content-type: image/webp
date: Mon, 14 Oct 2019 22:21:18 GMT
last-modified: Mon, 14 Oct 2019 18:05:02 GMT
server: nginx/1.16.1
status: 200
strict-transport-security: max-age=31536000;
vary: Accept-Encoding
x-content-type-options: nosniff
x-powered-by: PHP/7.0.33
x-webp-convert-log: Serving converted file

//("Last-Modified: " . gmdate("D, d M Y H:i:s", @filemtime($filename)) ." GMT");
//addHeader('Vary: Accept');
//setHeader('Cache-Control: ' . $options['cache-control-header']);
//setHeader('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + intval($seconds)));
//setHeader('Content-Length: ' . filesize($filename));
*/

$docRoot = rtrim($_SERVER["DOCUMENT_ROOT"], '/');
$requestUriNoQS = explode('?', $_SERVER['REQUEST_URI'])[0];
$relativeSrcPath = urldecode($requestUriNoQS);
$source = $docRoot . $relativeSrcPath;           // Absolute file path to source file. Comes from the .htaccess
$destination = $source . '.webp';     // Store the converted images besides the original images (other options are available!)
$useNginx = false; // если есть nginx, то не устанавливаем headers и не используем readfile();

/**
 * Функция отдачи файла
 */
function give_file($file, $useNginx = false){
	if ($useNginx){
		header('X-Accel-Redirect: '.$file);
	} else {
		$imglength = filesize($file);
		header('Content-Length: '.$imglength);
		header('Content-type: '.mime_content_type($file));
		header('Cache-Control: max-age=31536000');
		header('Last-Modified: ' . gmdate("D, d M Y H:i:s", filemtime($file)) ." GMT");
		header('Vary: Accept-Encoding');
		header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 31536000));
		readfile($file);
	}
}

/**
 * Настройки обслуживания запросов
 */
$servingOpts = array(
	'fail' => 'original',     // If failure, serve the original image (source). Other options include 'throw', '404' and 'report'
	//'show-report' => true,  // Generates a report instead of serving an image

	'serve-image' => [
		'headers' => [
			'cache-control' => true,
			'content-length' => true,
			'content-type' => true,
			'expires' => false,
			'last-modified' => true,
			'vary-accept' => false
		],
		'cache-control-header' => 'public, max-age=31536000',
	],

	'convert' => [
		'quality' => 90, // all convert option can be entered here (ie "quality")
		'jpeg' => [],
		'png' => [
			//'skip' => true,
			'gd-skip' => true,
			'converters' => ['cwebp', 'vips', 'imagick', 'gmagick', 'imagemagick', 'graphicsmagick', 'wpc', 'ewww'],
		],
		'encoding' => 'lossy',

		'converter-options' => [
			'cwebp' => [],
			'wpc' => [
				'api-version' => 1,
			],
			/*'wpc' => [
				'crypt-api-key-in-transfer' => false,
				'api-key' => 'use-any-phrase',
				'api-url' => 'https://example.com/other-includ/webp-cloud/wpc.php',
				'api-version' => 1,
			],*/
			/*'cwebp' => [
				//'command-line-options' => '-sharp_yuv',
				//'try-common-system-paths' => false,
				//'cwebp-try-cwebp' => false,
				//'try-discovering-cwebp' => false,
			],*/
		],
	],

	//'reconvert' => true,
);

/**
 * Наследование общих настроек проекта
 */
include('_settings.php'); // path to core folder
if (!isset($default_params)){
	include('default._settings.php');
}
if (!isset($default_params)){
	// настройки не подтянулись - отдаём оригинал
	give_file($source, $useNginx);
	die();
}

// определение кастомного пути для webp
if ($default_params['webp']['store_converted_in']){
	$destination = $docRoot . '/' . trim($default_params['webp']['store_converted_in'], '/') . $relativeSrcPath . '.webp';
}
// quality params
if ($default_params['webp']['quality']){
	$servingOpts['convert']['quality'] = $default_params['webp']['quality'];
}
if ($default_params['webp']['jpeg_max_quality']){
	$servingOpts['convert']['jpeg']['max-quality'] = $default_params['webp']['jpeg_max_quality'];
}
if ($default_params['webp']['jpeg_defaultquality']){
	$servingOpts['convert']['jpeg']['default-quality'] = $default_params['webp']['jpeg_defaultquality'];
}
// cwebp-converter params
if ($default_params['webp']['cwebp']['commandline_options']){
	$servingOpts['convert']['converter-options']['cwebp']['command-line-options'] = $default_params['webp']['cwebp']['commandline_options'];
}
if ($default_params['webp']['cwebp']['cwebp_try_precompiled']){	
	$servingOpts['convert']['cwebp-try-supplied-binary-for-os'] = true;
	if ($default_params['webp']['cwebp']['cwebp_use_precompiled_as_main']){
		$servingOpts['convert']['converter-options']['cwebp']['try-common-system-paths'] = false;
		$servingOpts['convert']['converter-options']['cwebp']['cwebp-try-cwebp'] = false;
		$servingOpts['convert']['converter-options']['cwebp']['try-discovering-cwebp'] = false;
	}
}
// wpc params
if ($default_params['webp']['wpc']['crypt_key']){	
	$servingOpts['convert']['converter-options']['wpc']['crypt-api-key-in-transfer'] = $default_params['webp']['wpc']['crypt_key'];
}
if ($default_params['webp']['wpc']['key']){	
	$servingOpts['convert']['converter-options']['wpc']['api-key'] = $default_params['webp']['wpc']['key'];
}
if ($default_params['webp']['wpc']['url']){	
	$servingOpts['convert']['converter-options']['wpc']['api-url'] = $default_params['webp']['wpc']['url'];
}
// converters stack
if (isset($global_converters)){
	$servingOpts['convert']['converters'] = $global_converters;
}
if (isset($jpg_converters)){
	$servingOpts['convert']['jpeg']['converters'] = $jpg_converters;
}
if (isset($png_converters)){
	$servingOpts['convert']['png']['converters'] = $png_converters;
}


/**
 * Include WebpConvert
 */
$webpconvert_autoload_path = $_SERVER["DOCUMENT_ROOT"] . '/'.trim($webp_core_fallback_location, '/').'/libs/vendor/autoload.php';
require_once $webpconvert_autoload_path;

use WebPConvert\WebPConvert;

if (!file_exists($destination)){ // если webp не существует - конвертим, а если нет конвертера - отдаём источник
	WebPConvert::serveConverted($source, $destination, $servingOpts);
} else {
	// проверим метку времени. Если webp старее оригинала - обновим.
	$timestampSource = filemtime($source);
    $timestampDestination = filemtime($destination);
    if (($timestampSource !== false) &&
        ($timestampDestination !== false) &&
        ($timestampSource > $timestampDestination)) {
			$servingOpts['reconvert'] = true;
            WebPConvert::serveConverted($source, $destination, $servingOpts);
    } else {
    	give_file($destination, $useNginx);
    }
}

?>