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

// Options for serving
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
		'png' => [
			//'skip' => true,
			'gd-skip' => true,
			'converters' => ['cwebp', 'vips', 'imagick', 'gmagick', 'imagemagick', 'graphicsmagick', 'wpc', 'ewww'],
		],
		'encoding' => 'lossy',

		'converter-options' => [
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
 * Include WebpConvert
 */
require('_settings.php'); // path to core folder
$webpconvert_autoload_path = $_SERVER["DOCUMENT_ROOT"] . '/'.trim($webp_core_fallback_location, '/').'/libs/vendor/autoload.php';
require_once $webpconvert_autoload_path;

use WebPConvert\WebPConvert;

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
    	give_file($source.'.webp', $useNginx);
    }
}

?>