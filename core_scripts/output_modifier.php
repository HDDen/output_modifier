<?php

// detect settings file
if (file_exists(__DIR__ . '/_settings.'.$_SERVER['SERVER_NAME'].'.php')){
	include('_settings.'.$_SERVER['SERVER_NAME'].'.php');
} else if (file_exists(__DIR__ . '/_settings.php')){
	include('_settings.php');
} else {
	include('default._settings.php');
}

if (!isset($default_params)){
	return false; // если не подключили параметры даже по запасному пути, уходим
}
//

define('HOMEDIR', get_homedir()); // ищем папку сайта ( /var/site.com ).

// Определяем путь до output_modifier.php от корня сайта. Используется, например, в логгере
if (file_exists($_SERVER['DOCUMENT_ROOT'].'/'.OUTPUTMOD_WEBP_CORE_FALLBACK_LOCATION.'/output_modifier.php')){
	define('WEBPPROJECT', $_SERVER['DOCUMENT_ROOT'].'/'.OUTPUTMOD_WEBP_CORE_FALLBACK_LOCATION);
} else {
	define('WEBPPROJECT', __DIR__);
}


// подключение библиотек
require_once WEBPPROJECT.'/libs/vendor/autoload.php';
include_once WEBPPROJECT.'/staff/php/OutputmodMinify.php';
include_once WEBPPROJECT.'/staff/php/logger.php';
if (file_exists(WEBPPROJECT.'/additional_works.'.$_SERVER['SERVER_NAME'].'.php')){
	include_once WEBPPROJECT.'/additional_works.'.$_SERVER['SERVER_NAME'].'.php';
} else if (file_exists(WEBPPROJECT.'/additional_works.php')){
	include_once WEBPPROJECT.'/additional_works.php';
}

use DiDom\Document;
use DiDom\Element;
use WebPConvert\Convert\Converters\Stack;
// use OutputmodMinify;

//$reconvert = forced reconvert, $trusted = dont check availability & file existence
function convertWebpDem($source = false, $destination = false, $reconvert = false, $trusted = false){

    if (defined('WEBP_DEBUGMODE') && (WEBP_DEBUGMODE)){
        if (function_exists('writeLog')){
            writeLog('  convertWebpDem(): старт');
        }
    }

    if (!$trusted){
        // проверка на $source
        if (!$source || !(file_exists($source))){

            if (defined('WEBP_DEBUGMODE') && (WEBP_DEBUGMODE)){
                if (function_exists('writeLog')){
                    writeLog('  convertWebpDem(): $source не передан или физически отсутствует');
                    writeLog('  convertWebpDem(): $source = '.$source);
                }
            }

            return false;
        }

        // проверка на возможность использования webp
        // Сначала проверяем наличие куки. Куки мы ставим или из js-хелпера, или из апача, или из шаблона.
        $canUse = false;
        if (!isset($_COOKIE['webpactive'])) {
            if( (strpos( $_SERVER['HTTP_ACCEPT'], 'image/webp' ) !== false) || (strpos( $_SERVER['HTTP_USER_AGENT'], ' Chrome/' ) !== false) ) {
                $canUse = true;

                //Неплохо было бы еще и установить куку
                setcookie('webpactive', 'true', time()+60*60*24*7, '/', $_SERVER['SERVER_NAME']);
            } else {
                setcookie('webpactive', 'false', time()+60*60*24*7, '/', $_SERVER['SERVER_NAME']); // кука в false, если нет поддержки
            }
        } else {
            if ($_COOKIE['webpactive'] === 'true'){
                $canUse = true;
            }
        }

        if (defined('WEBP_DEBUGMODE') && (WEBP_DEBUGMODE)){
            if (function_exists('writeLog')){
                writeLog('  convertWebpDem(): $canUse = '.var_export($canUse, true));
            }
        }

        if (!$canUse){
            return false;
        }

        if (!$destination){
            $destination = strtok($source, '?') . '.webp';
        }

    }

	// проверка на переконвертирование
	if (file_exists($destination)){
		if (!$reconvert){

			if (defined('WEBP_DEBUGMODE') && (WEBP_DEBUGMODE)){
				if (function_exists('writeLog')){
					writeLog('  convertWebpDem(): Файл уже существует, переконвертирование не разрешено');
				}
			}

			return true;
		}
	}

    // проверка на тип файла - пропускаем только jpeg и png. Должна проверяться даже если стоит флаг trusted
    $src_mimetype = mime_content_type(strtok($source, '?'));
    if ( ($src_mimetype != 'image/jpeg') && ($src_mimetype != 'image/png') ){
        if (defined('WEBP_DEBUGMODE') && (WEBP_DEBUGMODE)){
            if (function_exists('writeLog')){
                writeLog('  convertWebpDem(): Тип изображения не пригоден для конвертирования. '.PHP_EOL.'  $source = '.$source.PHP_EOL.'  $src_mimetype = '.$src_mimetype);
            }
        }

        return false;
    }

    if (defined('WEBP_DEBUGMODE') && (WEBP_DEBUGMODE)){
        if (function_exists('writeLog')){
            writeLog('  convertWebpDem(): Начинаем конвертирование');
        }
    }

    // проверка на версию
    $php_version = explode('.', phpversion());

    if (intval($php_version[0]) == 5){
        if (isset($php_version[1]) && (intval($php_version[1]) < 6)){

            if (defined('WEBP_DEBUGMODE') && (WEBP_DEBUGMODE)){
                if (function_exists('writeLog')){
                    writeLog('Версия PHP = '.phpversion().'. Минимальная - 5.6! Отказ.');
                }
            }

            return false;
        }
    } else if (intval($php_version[0]) <= 5){
        if (defined('WEBP_DEBUGMODE') && (WEBP_DEBUGMODE)){
            if (function_exists('writeLog')){
                writeLog('Версия PHP = '.phpversion().'. Минимальная - 5.6! Отказ.');
            }
        }
    }

    // default options
    $options = array(
        // PS: only set converters if you have strong reasons to do so
        'converters' => [
            'cwebp', 'vips', 'imagick', 'gmagick', 'imagemagick', 'graphicsmagick', 'wpc', 'gd'
        ],

        'metadata' => 'none',
        'quality' => 90,
        'encoding' => 'lossy',
        //'near-lossless' => 75, // default is 60. Todo
        //'cwebp-try-supplied-binary-for-os' => true, // true by default
        //'cwebp-rel-path-to-precompiled-binaries' => '', // location. Commented to prevent override defaults

        'png' => [
            'gd-skip' => true,
            'converters' => ['cwebp', 'vips', 'imagick', 'gmagick', 'imagemagick', 'graphicsmagick', 'wpc'],
        ],

        'jpeg' => [
            'quality' => 'auto',      /* Set to same as jpeg (requires imagick or gmagick extension, not necessarily compiled with webp) */
            'max-quality' => 95,      /* Only relevant if quality is set to "auto" */
            'default-quality' => 90,  /* Fallback quality if quality detection isnt working */
        ],

        // As an alternative to prefixing, you can use "converter-options" to set a whole bunch of overrides in one go:
        'converter-options' => [
            'wpc' => [
                'crypt-api-key-in-transfer' => false,
                'api-key' => '', // 'string-type-key'
                'api-url' => '', //https://example.com/webp-cloud/wpc.php',
                'api-version' => 1,
            ],
            /*'cwebp' => [
                //'command-line-options' => '-sharp_yuv',
                //'try-common-system-paths' => false,
                //'cwebp-try-cwebp' => false,
                //'try-discovering-cwebp' => false,
            ],*/
        ],
    );

    // overriding options based on drupal module values
    // global quality
    if (defined('WEBP_QUALITY') && WEBP_QUALITY){
        $options['quality'] = intval(WEBP_QUALITY);
    }
    // jpeg quality
    if (defined('WEBP_JPEG_QUALITY') && WEBP_JPEG_QUALITY){
        if (WEBP_JPEG_QUALITY == 'auto'){
            $options['jpeg']['quality'] = 'auto';
        } else {
            $options['jpeg']['quality'] = intval(WEBP_JPEG_QUALITY);
        }
    }
    // jpeg max-quality
    if (defined('WEBP_JPEG_MAXQUALITY') && WEBP_JPEG_MAXQUALITY){
        $options['jpeg']['max-quality'] = intval(WEBP_JPEG_MAXQUALITY);
    }
    // jpeg def-quality
    if (defined('WEBP_JPEG_DEFQUALITY') && WEBP_JPEG_DEFQUALITY){
        $options['jpeg']['default-quality'] = intval(WEBP_JPEG_DEFQUALITY);
    }

    // wpc options
    if (defined('WEBP_WPC_CRYPT') && WEBP_WPC_CRYPT){
        $options['converter-options']['wpc']['crypt-api-key-in-transfer'] = true;
    }

    if (defined('WEBP_WPC_KEY') && WEBP_WPC_KEY){
        $options['converter-options']['wpc']['api-key'] = WEBP_WPC_KEY;
    }

    if (defined('WEBP_WPC_URL') && WEBP_WPC_URL){
        $options['converter-options']['wpc']['api-url'] = WEBP_WPC_URL;
    }

    // precompiled options
    if (defined('WEBP_TRY_PRECOMPILED')){
    	if (WEBP_TRY_PRECOMPILED === 'force'){
            $options['cwebp-try-supplied-binary-for-os'] = true;
            $options['converter-options']['cwebp']['try-common-system-paths'] = false;
            $options['converter-options']['cwebp']['cwebp-try-cwebp'] = false;
            $options['converter-options']['cwebp']['try-discovering-cwebp'] = false;
        } else if (WEBP_TRY_PRECOMPILED){
    		$options['cwebp-try-supplied-binary-for-os'] = true;
    	} else {
    		$options['cwebp-try-supplied-binary-for-os'] = false;
    	}
    }
    if (defined('WEBP_PRECOMPILED_PATH') && WEBP_PRECOMPILED_PATH){
        $options['cwebp-rel-path-to-precompiled-binaries'] = WEBP_PRECOMPILED_PATH;
    }

    // custom cwebp commandline
    if (defined('WEBP_CWEBP_COMMAND') && WEBP_CWEBP_COMMAND){
        $options['converter-options']['cwebp']['command-line-options'] = WEBP_CWEBP_COMMAND;
    }

	// override based on _settings

	// detect settings file
	if (file_exists(__DIR__ . '/_settings.'.$_SERVER['SERVER_NAME'].'.php')){
		include('_settings.'.$_SERVER['SERVER_NAME'].'.php');
	} else if (file_exists(__DIR__ . '/_settings.php')){
		include('_settings.php');
	} else {
		include('default._settings.php');
	}

	if (!isset($default_params)){
		//return false; // если не подключили параметры даже по запасному пути, уходим
		if (isset($global_converters)){
			$options['converters'] = $global_converters;
		}

		if (isset($jpg_converters)){
			$options['jpeg']['converters'] = $jpg_converters;
		}

		if (isset($png_converters)){
			$options['png']['converters'] = $png_converters;
		}
	}
	//

    // debug options
    if (defined('WEBP_DEBUGMODE') && (WEBP_DEBUGMODE)){
        if (function_exists('writeLog')){
            writeLog('  callWebp: масив options[]:');
            writeLog(print_r($options, true));
        }
    }

	try {
		Stack::convert($source, $destination, $options, $logger=null);
	} catch (Exception $e){
		if (isset($e)){
			if (defined('WEBP_DEBUGMODE') && (WEBP_DEBUGMODE)){
				if (function_exists('writeLog')){
					writeLog('  convertWebpDem(): Возвращаем false - закралась ошибка');
					writeLog('  convertWebpDem(): '.$e->getMessage());
				}
			}
			return false;
		}
	}

    if (defined('WEBP_DEBUGMODE') && (WEBP_DEBUGMODE)){
        if (function_exists('writeLog')){
            writeLog('  convertWebpDem(): Возвращаем true - предполагается успех');
        }
    }

    return true;
}

function callWebp($source = false, $destination = false){

    if (defined('WEBP_DEBUGMODE') && (WEBP_DEBUGMODE)){
        if (function_exists('writeLog')){
            writeLog('  callWebp: стартовали');
        }
    }

    // проверка на $source
    if (!$source || !(file_exists($source))){

        if (defined('WEBP_DEBUGMODE') && (WEBP_DEBUGMODE)){
            if (function_exists('writeLog')){
                writeLog('  callWebp: отсутствует путь к $source или отсутствует физически');
                writeLog('  callWebp: полученный: '.$source);
            }
        }
        return false;
    }

    // проверка destination
    if (!$destination){
        $destination = strtok($source, '?') . '.webp';
    }

    // умолчание
    $result = false;
    // проверка на поддержку webp
    $canUse = false;

    if (defined('WEBP_FORCE_CONVERSION') && (WEBP_FORCE_CONVERSION == true)){
        $canUse = true; // только форсируем. Куки не ставим, для избежания проблем с IE и фоновыми картинками в webp
    } else if (!isset($_COOKIE['webpactive'])) {

        // несколько способов верификации
        // заголовок accept
        $accept_verification = false;
        // юзер-агент хрома
        $is_chrome = false;
        // IE
        $is_ie = false;

        if (strpos( $_SERVER['HTTP_ACCEPT'], 'image/webp' ) !== false){
            $accept_verification = true;
        } else if (strpos( $_SERVER['HTTP_USER_AGENT'], ' Chrome/' ) !== false){
            $is_chrome = true;
        } else if (strpos( $_SERVER['HTTP_USER_AGENT'], 'Trident' ) !== false){
            $is_ie = true;
        }

        if ( ($accept_verification || $is_chrome) && (!$is_ie)){
            $canUse = true;

            //Неплохо было бы еще и установить куки
            setcookie('webpactive', 'true', time()+60*60*24*7, '/', $_SERVER['SERVER_NAME']);
        }
    } else {
        if ($_COOKIE['webpactive'] === 'true'){
            $canUse = true;
        }
    }

    // проверка на существование конечного файла - чтобы не напрягать лишний раз серв
    if ($canUse){

        // проверка по флагу reconvert
        $reconvert = false;
        // проверяем по временной метке
        if (defined('WEBP_RECONVERT_BYTIMESTAMP') && WEBP_RECONVERT_BYTIMESTAMP && file_exists($destination)){
            clearstatcache();
            $timestamp = filemtime($destination);
            $target_timestamp = intval(WEBP_RECONVERT_BYTIMESTAMP);

            if ($target_timestamp > $timestamp){
                $reconvert = true;

                if (defined('WEBP_DEBUGMODE') && (WEBP_DEBUGMODE)){
                    if (function_exists('writeLog')){
                        writeLog('  callWebp: будет выполнена переконвертация по дате');
                    }
                }
            }
        }

        if (!file_exists($destination) || $reconvert){

            $result = convertWebpDem($source, $destination, $reconvert, true); // можно добавить к вызову аргумент $reconvert = true, тогда будет принудительное переконвертирование. $trusted - все проверки выполнены, не перепроверять

            if (defined('WEBP_DEBUGMODE') && (WEBP_DEBUGMODE)){
                if (function_exists('writeLog')){
                    writeLog('  callWebp: результат обращения к convertWebpDem() = '.var_export($result, true));
                }
            }
        } else {

            if (defined('WEBP_DEBUGMODE') && (WEBP_DEBUGMODE)){
                if (function_exists('writeLog')){
                    writeLog('  callWebp: webp-версия уже существует');
                }
            }

            $result = true; // webp-версия существует, поднимаем флаг
        }
    }

    // ответ, использовать webp или исходное изображение
    if ($result){

        if (defined('WEBP_DEBUGMODE') && (WEBP_DEBUGMODE)){
            if (function_exists('writeLog')){
                writeLog('  callWebp: успех');
            }
        }

        return true; //Конвертирование успешное! Отдаем webp
    } else {

        if (defined('WEBP_DEBUGMODE') && (WEBP_DEBUGMODE)){
            if (function_exists('writeLog')){
                writeLog('  callWebp: отказ');
            }
        }

        return false; // Что-то пошло не так, используем оригинальный файл
    }
}

function get_homedir(){
	if (defined('DRUPAL_ROOT')){
		return DRUPAL_ROOT;
	} else {
		return rtrim($_SERVER['DOCUMENT_ROOT'], '/');
	}
}

function check_debugmode($params = false){
	// Включает режим дебага, добавляет заголовки
	if ($params !== false){
		if (isset($params['debug']) && ($params['debug'] == true) ){
			header('X-outputmodifier-used: used');
		}
		return true;
	} else {
		return false;
	}
}

function add_debugheader($header = false, $value = false){
	// Добавляет дебаг-заголовки
	if (!$header){
		$header = 'Timer';
	}
	if (!$value){
		$value = microtime(true);
	}
	header('X-outputmodifier-'.$header.': '.$value);
}

function add_img_sizes(&$elem, $mode){
	if (WEBP_DEBUGMODE){
		writeLog('add_img_sizes(): Зашли в функцию');
	}

	if (!$mode){
		if (WEBP_DEBUGMODE){
			writeLog('add_img_sizes(): Не передан режим, ничего не делаем');
		}
		return false;
	}

	// проверяем, установлены ли атрибуты, чтобы не перевычислять
	if ( !is_null($elem->getAttribute('width')) || !is_null($elem->getAttribute('height')) ){
		if (WEBP_DEBUGMODE){
			writeLog('add_img_sizes(): Изображению уже назначены атрибуты width или height');
		}
		return true;
	}

	$src = $elem->getAttribute('src');
	if (is_null($src)){
		if (WEBP_DEBUGMODE){
			writeLog('add_img_sizes(): src не обнаружен, выход.');
		}

		return false;
	}

	$img_server_abspath = parsePathFromSrc($src);

	// Проверка существования
	if (!file_exists($img_server_abspath)){
		if (WEBP_DEBUGMODE){
			writeLog('add_img_sizes(): Картинки по пути '.$img_server_abspath.' не существует. Выход.');
		}

		return false;
	}

	// получаем данные об изображении
	$sizes = false;
	if ($img_server_abspath){
		$sizes = getimagesize($img_server_abspath);
	} else {
		if (WEBP_DEBUGMODE){
			writeLog('add_img_sizes(): Не смогли получить серверный путь до картинки.'.PHP_EOL.'$src = '.$src.PHP_EOL.' Выход.');
		}

		return false;
	}

	// 0 => t('No'),
    // 1 => t('Width+Height'),
    // 2 => t('Width only'),
    // 3 => t('Height only'),
    // 4 => t('Allowlist'), <- не реализовано, todo !
    if ($sizes && ($sizes[0] > 0) && ($sizes[1] > 0) ){
    	switch ($mode){
			case 1:
				$elem->setAttribute('width', $sizes[0]);
				$elem->setAttribute('height', $sizes[1]);
				break;
			case 2:
				$elem->setAttribute('width', $sizes[0]);
				break;
			case 3:
				$elem->setAttribute('height', $sizes[1]);
				break;
		}
    }
}

function add_fallback_alt(&$elem, &$params){
	$old_alt = $elem->getAttribute('alt');
	if (is_null($old_alt)){
		$elem->setAttribute('alt', '');
	}
}

// Смешиваем полученные параметры с дефолтными
function mix_params($params = false){

	if (file_exists(__DIR__ . '/_settings.'.$_SERVER['SERVER_NAME'].'.php')){
		include('_settings.'.$_SERVER['SERVER_NAME'].'.php');
	} else if (file_exists(__DIR__ . '/_settings.php')){
		include('_settings.php');
	} else {
		include('default._settings.php');
	}
	
	if (!isset($default_params)){
		return false; // если не подключили параметры даже по запасному пути, уходим
	}

	if ($params){
		$mixed_params_array = array_replace_recursive($default_params, $params);
		return $mixed_params_array;
	} else {
		return $default_params;
	}
}

function normalize_commaseparated($input){
	$temporary_array = explode(',', $input);
	$result = '';
	foreach ($temporary_array as $value) {
		$result .= trim($value).',';
	}
	unset($temporary_array);
	$result = trim($result, ',');
	return $result;
}

function is_selector($input){
	if (mb_strpos($input, ',')){
		return true;
	} else {
		return false;
	}
}

function addClass(&$elem, $class){
	if (!$class){
		return false;
	}

	$classList = $elem->getAttribute('class');
	if ($classList){
		if (mb_strpos($classList, $class) !== false){
			return true;
		} else {
			$classList = $classList . ' '.$class;
			$elem->setAttribute('class', $classList);
		}
	} else {
		$elem->setAttribute('class', $class);
	}

	return true;
}
function removeClass(&$elem, $class){
	if (!$class){
		return false;
	}

	$classList = $elem->getAttribute('class');
	if ($classList){
		if (mb_strpos($classList, $class) !== false){
			$classList = str_replace($class, '', $classList);
			$elem->setAttribute('class', $classList);
		}
	}

	return true;
}
function toggleClass(&$elem, $class){
	if (!$class){
		return false;
	}

	$classList = $elem->getAttribute('class');
	if ($classList){
		if (mb_strpos($classList, $class) !== false){
			$classList = str_replace($class, '', $classList);
			$elem->setAttribute('class', $classList);
		} else {
			$classList = $classList . ' '.$class;
			$elem->setAttribute('class', $classList);
		}
	} else {
		$elem->setAttribute('class', $class);
	}

	return true;
}

function process_webp(&$document, &$params = false){

	$module_time = false;
	if (WEBP_DEBUGMODE){
		$module_time = microtime(true);
		writeLog('process_webp(): Зашли в функцию');
	}

	$home_dir = get_homedir();

	// Собираем массив селекторов для webp, получаем элементы DOM, и по очереди обрабатываем их

	if (!$params){
		return false;
	}

	// Чёрный лист - исключающие селекторы
	if ($params['webp']['ignore_webp_on'] != false){
		if (WEBP_DEBUGMODE){
			writeLog('process_webp(): Имеются селекторы для игнорирования конвертирования');
		}
		// преобразуем список селекторов в массив, ниже будем проходить его в цикле
		$excluded_selectors_array = explode(',', normalize_commaseparated($params['webp']['ignore_webp_on']));
	}

	// соберем селекторы в массив, соберем его в строку, получим все элементы и начнём обработку, даже не разделяя на img и на обычный инлайн
	$process_on = array();
	$filter_by_specific_extensions = false; // фильтр по расширениям отключен. Если включен, здесь окажется строка с расширениями

	// включено для изображений
	if ($params['webp']['img']){

		// ...но с фильтром ли по селекторам?
		if ($params['webp']['by_selector']){
			$imgs_by_selector = explode(',', normalize_commaseparated($params['webp']['by_selector'])); // массив селекторов изображений, его надо добавить в массив $process_on
			$process_on = array_merge($process_on, $imgs_by_selector);
			unset($imgs_by_selector);
		} else if ($params['webp']['allowed_extensions']) { // или же с фильтром по расширениям?
			$filter_by_specific_extensions = explode(',', normalize_commaseparated($params['webp']['allowed_extensions']));
			foreach ($filter_by_specific_extensions as $extension){
				// DiDOM is case-sensitive, so we're must keep in mind all variants
				$process_on[] = 'img[src*='.mb_strtolower($extension).']';
				$process_on[] = 'img[src*='.mb_strtoupper($extension).']';
			}
		} else {
			$process_on[] = 'img'; // просто все img
		}
	}

	// теперь добавляем все остальные теги
	if ($params['webp']['additional_tags']){
		$webp_in_others = explode(',', normalize_commaseparated($params['webp']['additional_tags']));
		$process_on = array_merge($process_on, $webp_in_others);
		unset($webp_in_others);
	}



	if (!empty($process_on)){
		// собрали массив проходимого, теперь объединяем это в строку и получаем набор элементов
		$process_on = implode(', ', $process_on);
		$process_on = $document->find($process_on);
	} else {
		if (WEBP_DEBUGMODE){
			writeLog('process_webp(): массив $process_on пуст');
			writeLog('process_webp(): работали '. (microtime(true) - $module_time) . ' сек.');
		}
		return false;
	}

	// Установим флаг для форсированной конвертации
	if (isset($params['webp']['force']) && ($params['webp']['force'])){
		if (!defined('WEBP_FORCE_CONVERSION')){
			if (WEBP_DEBUGMODE){
				writeLog('process_webp(): включено принудительное конвертирование, устанавливаем константу');
			}

			define('WEBP_FORCE_CONVERSION', true);
		}
	}

	// определяем параметры для конвертеров
	// cwebp
	if ($params['webp']['cwebp']['cwebp_try_precompiled']){
		if (!defined('WEBP_TRY_PRECOMPILED')){
			if ($params['webp']['cwebp']['cwebp_use_precompiled_as_main']){
				define('WEBP_TRY_PRECOMPILED', 'force');
			} else {
				define('WEBP_TRY_PRECOMPILED', true);
			}
		}
	} else {
		define('WEBP_TRY_PRECOMPILED', false);
	}
		// commandline
	if ($params['webp']['cwebp']['commandline_options']){
		if (!defined('WEBP_CWEBP_COMMAND')){
			define('WEBP_CWEBP_COMMAND', $params['webp']['cwebp']['commandline_options']);
		}
	}
		// path for searching precompiled
	if ($params['webp']['cwebp']['relative_path']){
		if (!defined('WEBP_PRECOMPILED_PATH')){
			$webp_precompiled_path = $home_dir.'/'.trim($params['webp']['cwebp']['relative_path'], '/');
			define('WEBP_PRECOMPILED_PATH', $webp_precompiled_path);
		}
	}
		// overall quality
	if (!defined('WEBP_QUALITY')){
		$webpdrupal7_webp_quality = intval($params['webp']['quality']);
		define('WEBP_QUALITY', $webpdrupal7_webp_quality);
	}
		// jpeg-specific quality. Number or auto
	if (!defined('WEBP_JPEG_QUALITY')){
		$webpdrupal7_webp_jpeg_quality = $params['webp']['jpeg_quality'];
		if ($webpdrupal7_webp_jpeg_quality != 'auto'){
			$webpdrupal7_webp_jpeg_quality = intval($webpdrupal7_webp_jpeg_quality);
		}
		define('WEBP_JPEG_QUALITY', $webpdrupal7_webp_jpeg_quality);
	}
		// jpeg max-quality (if WEBP_JPEG_QUALITY = auto)
	if (!defined('WEBP_JPEG_MAXQUALITY')){
		$webpdrupal7_webp_jpeg_maxquality = intval($params['webp']['jpeg_max_quality']);
		define('WEBP_JPEG_MAXQUALITY', $webpdrupal7_webp_jpeg_maxquality);
	}
		// jpeg fallback quality
	if (!defined('WEBP_JPEG_DEFQUALITY')){
		$webpdrupal7_webp_jpeg_defquality = intval($params['webp']['jpeg_defaultquality']);
		define('WEBP_JPEG_DEFQUALITY', $webpdrupal7_webp_jpeg_defquality);
	}
	// wpc
		// crypt key
	if (!defined('WEBP_WPC_CRYPT')){
		define('WEBP_WPC_CRYPT', $params['webp']['wpc']['crypt_key']);
	}
		// key
	if (!defined('WEBP_WPC_KEY')){
		define('WEBP_WPC_KEY', $params['webp']['wpc']['key']);
	}
		// url
	if (!defined('WEBP_WPC_URL')){
		define('WEBP_WPC_URL', $params['webp']['wpc']['url']);
	}

	// добавим атрибут в разметку для определения префикса папки webp
	// без этого unwebp.js будет убирать только расширение
	if ($params['webp']['store_converted_in']){
		// ищем body
		$unwebpmarker = $document->first('body');
		if (!$unwebpmarker){
			$unwebpmarker = $document->first('div');
		}
		if ($unwebpmarker){
			$unwebpmarker->setAttribute('data-webpprefix', '/'.trim($params['webp']['store_converted_in'], '/'));
		}
		unset($unwebpmarker);
	}

	// начинаем обработку
	foreach ($process_on as $elem){

		// Но! сначала проверим, не игнорируем ли мы этот элемент по селектору
		if (isset($excluded_selectors_array)){
			foreach ($excluded_selectors_array as $exclude_selector) {
				if ($elem->matches($exclude_selector)){
					if (WEBP_DEBUGMODE){
						writeLog('process_webp(): игнорирован элемент "'.$exclude_selector.'"');
					}
					continue 2;
				}
			}
		}

		// сброс lazy
		unlazy($elem, $params);

		// дебаг
		if (WEBP_DEBUGMODE){
			writeLog(PHP_EOL.'-----'.PHP_EOL.'Webp-процессинг ' . $elem . ': ');
		}
		// нужно получить источник изображения, преобразовать в webp
		// а затем, когда будем встраивать webp, смотреть есть ли lazy
		$webp_version = generate_webp($elem, $filter_by_specific_extensions, $params['webp']['store_converted_in']); // false или путь

		// дебаг
		if (WEBP_DEBUGMODE){
			writeLog('Результат процессинга: '.var_export($webp_version, true).PHP_EOL.'-----');
		}

		// Поддержка CDN
		$cdn_webp_version = false;
		if ($params['cdn']['enabled'] && $params['cdn']['domain']){
			if (WEBP_DEBUGMODE){
				writeLog('Конвертирование $webp_version в CDN');
			}

			$cdn_webp_version = convertUriToCDN($webp_version, $params); // false / path
			if ($cdn_webp_version){
				if (WEBP_DEBUGMODE){
					writeLog('Успешно обращено в CDN');
				}
			} else {
				if (WEBP_DEBUGMODE){
					writeLog('Не обращено в CDN');
				}
			}
		}

		// Мы не будем делать сразу процессинг с lazy-loading, т.к. не сможем позже проигнорировать этот элемент в общем процессе process_lazy()
		// просто строим webp-версию, не заморачиваемся.

		if ($webp_version){

			// определяем, куда положить webp-версию
			$tagname = $elem->tag;
			if ($tagname == 'img'){
				if ($params['webp']['img_webpstore_attr']){
					$store_webp_in = $params['webp']['img_webpstore_attr'];
				} else {
					$store_webp_in = 'src';
				}

				if ($cdn_webp_version){
					$elem->setAttribute($store_webp_in, $cdn_webp_version);
				} else {
					$elem->setAttribute($store_webp_in, $webp_version);
				}

			} else if ($tagname == 'video') {
				if ($cdn_webp_version){
					$elem->setAttribute('poster', $cdn_webp_version);
				} else {
					$elem->setAttribute('poster', $webp_version);
				}
			} else {
				// если не img, то только в style, задав background-image
				// заменим через str_replace в инлайновом стиле
				$style = $elem->getAttribute('style');
				$bg_img_src = parseBackgroundImgUri($style);

				// Поддержка CDN
				if ($cdn_webp_version){
					$style = str_replace($bg_img_src, $cdn_webp_version, $style);
				} else {
					$style = str_replace($bg_img_src, $webp_version, $style);
				}
				$elem->setAttribute('style', $style);
			}

		}

	}

	if (WEBP_DEBUGMODE){
		writeLog('process_webp(): работали '. (microtime(true) - $module_time) . ' сек.');
	}

}

function process_avif(&$document, &$params = false){
	$module_time = false;
	if (WEBP_DEBUGMODE){
		$module_time = microtime(true);
		writeLog('process_avif(): Зашли в функцию');
	}
	if (!$params['avif']['enabled'] || !$params['avif']['process_on']){
		if (WEBP_DEBUGMODE){
			writeLog('process_avif(): процессинг не включен в настройках! Выход.');
			writeLog('process_avif(): работали '. (microtime(true) - $module_time) . ' сек.');
		}

		return false;
	}

	// Нужно собрать селекторы и сделать выборку
	// Если элементы присутствуют, отправляем по-одному в функцию одиночной обработки
	$selectors = normalize_commaseparated($params['avif']['process_on']);
	$elems = $document->find($selectors);
	if (count($elems)){
		foreach ($elems as $index => $elem) {
			if (WEBP_DEBUGMODE){
				writeLog(PHP_EOL.'-----'.PHP_EOL.'Avif-процессинг ' . $elem . ': ');
			}
			$result = process_avif_once($elem, $params);
			if (WEBP_DEBUGMODE){
				writeLog('Результат процессинга: '.( $result ? 'успех!' : 'отказ').PHP_EOL.'-----');
			}
		}
	}

	if (WEBP_DEBUGMODE){
		writeLog('process_avif(): обработали весь массив для avif');
		writeLog('process_avif(): работали '. (microtime(true) - $module_time) . ' сек.');
	}
}

function process_avif_once(&$elem, &$params){
	// проверяем, что еще не обрабатывали элемент
	if ($elem->hasAttribute('data-avif')){
		if (WEBP_DEBUGMODE){
			writeLog('  process_avif_once(): этот элемент уже содержит атрибут "data-avif"');
		}
		return true;
	}
	
	// получаем элемент - определяем его тип
	$tagname = $elem->tag;

	// итоговая переменная, будем присваивать вычисенный потенциальный путь к avif ей
	$patch_to_check = false;
	// серверный путь к document root
	$home_dir = get_homedir();
	// кастомная папка для avif
	$custom_path_prefix = trim($params['avif']['path_prefix']);
	if ($custom_path_prefix){
		$custom_path_prefix = '/' . $custom_path_prefix;
	}

	if (WEBP_DEBUGMODE){
		writeLog('  process_avif_once(): $custom_path_prefix = "'.$custom_path_prefix.'"');
	}

	// определяем путь для проверки
	// должен заканчиваться на .avif и содержать оригинальное расширение файла
	// вычисляем серверный путь *.*.avif, затем проверяем наличие. Если есть - пушим атрибут. Если нет - не пушим.
	if ($tagname == 'img'){

		// проверяем src. Разбираем на относительный путь, формируем серверный путь для проверки с учётом custom location.
		if ($elem->hasAttribute('src')){
			$src = $elem->getAttribute('src');
			$relative_uri = getRelativeUri($src, $home_dir); // false or path
			if ($relative_uri){
				$patch_to_check = $home_dir . $custom_path_prefix . $relative_uri . '.avif';
			}
		} else {
			if (WEBP_DEBUGMODE){
				writeLog('  process_avif_once(): элемент - img, но не содержит src');
			}
		}
		
	} else if ($tagname == 'video') {
		// парсим poster
		if ($elem->hasAttribute('poster')){
			$poster = $elem->getAttribute('poster');
			$relative_uri = getRelativeUri($poster, $home_dir); // false or path
			if ($relative_uri){
				$patch_to_check = $home_dir . $custom_path_prefix . $relative_uri . '.avif';
			}
		}
	} else {
		// нужно парсить из стиля
		if ($elem->hasAttribute('style')){
			$style = $elem->getAttribute('style');
			$relative_uri = getRelativeUri(parseBackgroundImgUri($style), $home_dir); // false or path
			if ($relative_uri){
				$patch_to_check = $home_dir . $custom_path_prefix . $relative_uri . '.avif';
			}
		} else {
			if (WEBP_DEBUGMODE){
				writeLog('  process_avif_once(): элемент не img и не содержит style');
			}
		}
	}

	// проверяем наличие
	if (!$patch_to_check){
		if (WEBP_DEBUGMODE){
			writeLog('  process_avif_once(): не смогли вычислить путь для проверки');
		}
		return false;
	}

	if (file_exists($patch_to_check)){
		// всё ок, добавляем атрибут
		$elem->setAttribute('data-avif', $custom_path_prefix . $relative_uri . '.avif');
		if (WEBP_DEBUGMODE){
			writeLog('  process_avif_once(): успешно добавили data-avif="'. $custom_path_prefix . $relative_uri . '.avif"');
		}
		return true;
	} else {
		if (WEBP_DEBUGMODE){
			writeLog('  process_avif_once(): файла '. $patch_to_check . ' не существует');
		}
		return false;
	}

}

function process_lazy(&$document, &$params = false){
	$module_time = false;
	if (WEBP_DEBUGMODE){
		$module_time = microtime(true);
	}

	if (isset($params['lazyload']) && (!empty($params['lazyload']))){
		$process_on = array(); // массив общий

		// "Чёрный лист" - исключающие селекторы
		if ($params['ignore_lazy'] != false){
			if (WEBP_DEBUGMODE){
				writeLog('process_lazy(): Имеются селекторы для игнорирования lazyload');
				writeLog('process_lazy(): "'.$params['ignore_lazy'].'"');
			}
			// преобразуем список селекторов в массив, ниже будем проходить его в цикле
			$excluded_selectors_array = explode(',', normalize_commaseparated($params['ignore_lazy']));
		}

		foreach ($params['lazyload'] as $key => $value) {
			if (isset($value['lazy']) && ($value['lazy'] === true)){
				$process_on[] = $key; // наполняем массив селекторами
			}
		}

		if (!empty($process_on)){
			// получаем элементы, на которых будем делать ленивую загрузку
			$elems = $document->find(implode(', ', $process_on));

			// теперь
			foreach ($elems as $elem) {

				// проверка на исключающий селектор
				$ignore = false;
				if (isset($excluded_selectors_array)){
					foreach ($excluded_selectors_array as $selector) {
						if ($elem->matches($selector)){
							if (WEBP_DEBUGMODE){
								writeLog('process_lazy(): игнорирован элемент по "'.$selector.'"');
							}
							continue 2;
						}
					}
				}

				process_lazyload_once($elem, $params);
			}
		}

	}

	if (WEBP_DEBUGMODE){
		writeLog('process_lazy(): работали '. (microtime(true) - $module_time) . ' сек.');
	}

}

function process_lazyload_once(&$elem, &$params){
	// процессинг ленивой загрузки для одного конкретного элемента
	// универсальный для img и остальных
	$tagname = $elem->tag;
	$preloader = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=='; // default
	if (isset($params['lazyload'][$tagname]['inline_preloader_picture'])){
		$preloader = $params['lazyload'][$tagname]['inline_preloader_picture'];
	}

	// умолчания - если для селектора нет параметров в настройках
	$rezerv_tagname = 'div';
	if (!isset($params['lazyload'][$tagname])){
		$params['lazyload'][$tagname] =& $params['lazyload'][$rezerv_tagname];
	}

	if ($tagname == 'img'){
		// процессинг как img
		// если img, нам нужно создать srcset, в него запихнуть инлайн-картинку, а в data-srcset засунуть оригинальный src.
		// но если есть webp-версия, то в data-src пихаем её

		// добавляем нативную loading = "lazy"
		// upd. Если мы используем только нативную реализацию lazyloading, то нужно только прописать атрибут
		if ( ($params['add_chromelazy_img'] !== false) || $params['lazyload']['img']['use_native']){
			$native_lazy_mode = $params['add_chromelazy_img'];
			if (!$native_lazy_mode){
				$native_lazy_mode = 'lazy'; // переопределение на случай, если не выбран режим для loading-атрибута
			}
			$elem->setAttribute('loading', $native_lazy_mode);
		}

		if ($params['lazyload']['img']['use_native']){
			return true; // выход
		}

		// также нам нужно проверить, вдруг это изображение содержит корректные атрибуты srcset, которые используются по назначению.
		// также удалить srcset, если там inline-изображение
		$srcset_attr_value = $elem->getAttribute('srcset');
		if (is_null($srcset_attr_value)){
			$elem->setAttribute('srcset', $preloader);
			$move_to_datasrcset = 'src';
		} else {
			// 1. Если в srcset '.webp', переносим srcset в data-srcset
			// 2. Если в srcset ', ' (набор реальных srcset), переносим в data-srcset
			// 3. Если в srcset 'data:', затираем srcset, a в data-srcset попадёт src.
			// По сути, нам надо решить, что помещать в data-srcset
			$move_to_datasrcset = 'srcset'; // дефолт
			if ( mb_substr($srcset_attr_value, 0, 5) == 'data:'){
				$move_to_datasrcset = 'src';
			}
		}

		if ($move_to_datasrcset != 'srcset'){
			$srcset_attr_value = $elem->getAttribute($move_to_datasrcset);
		}

		$elem->setAttribute('data-srcset', $srcset_attr_value);

		$elem->setAttribute('srcset', $preloader);
	} else {
		$is_src_contains = $elem->getAttribute('src');
		if (!is_null($is_src_contains)){
			// Элемент содержит src, и это не изображение. Значит, это js/iframe/source.
			// Можем использовать js-плагин lazysizes, раз уж всё равно его подключили

			// надстроим iframe
			if ($tagname == 'iframe'){
				if ($params['lazyload']['iframe']['use_chromelazy_instead'] == false){
					// добавляем параметры только если не отключили процессинг ленивой загрузки js-плагином
					$elem->setAttribute('data-src', $is_src_contains);
					// затираем изначальный src
					$elem->removeAttribute('src');
				} else {
					$elem->setAttribute('loading', $params['lazyload']['iframe']['add_chromelazy']);
				}
			} else {
				// для всех остальных
				$elem->setAttribute('data-src', $is_src_contains);
				// затираем изначальный src
				$elem->removeAttribute('src');
			}
		} else {
			// нет атрибута src - возможно, video?
			if ($tagname == 'video') {
				// для видео нужно обработать постер и sources
				// пока работаем с постером. Селектор sources задаем отдельно, как video sources
				$video_poster_src = $elem->getAttribute('poster');
				if (!is_null($video_poster_src)){
					//$elem->setAttribute('poster', '');
					$elem->removeAttribute('poster');
					$elem->setAttribute('data-poster', $video_poster_src);
				}
			}
		}


		// процессинг с фоновыми атрибутами
		// получить атрибут style
		// из него выделить url изображения
		//		если там не 'data:'
		// 		поместить его в атрибут data-bg, записать
		// 		в оригинальном style заменить url на инлайновую
		$style = $elem->getAttribute('style');
		if (!is_null($style)){
			$src = parseBackgroundImgUri($style);
			if ($src && (mb_substr($src, 0, 5) !== 'data:')){ //check if src exists and not 'data:image'
				$store_orig = 'data-bg';
				if (isset($params['lazyload'][$tagname]['attr_store_orig'])){
					$store_orig = $params['lazyload'][$tagname]['attr_store_orig'];
				}
				$elem->setAttribute($store_orig, $src);

				$style = str_replace($src, $preloader, $style);
				$elem->setAttribute('style', $style);
			}
		} else {
			// инлайнового стиля нет, значит мы намерены обрабатывать ленивую загрузку через CSS - переключением классов lazyloading/lazyloaded
		}
	}

	if (($tagname == 'iframe') && ($params['lazyload']['iframe']['use_chromelazy_instead'] === true) ){
		// если iframe и мы используем только нативную реализацию lazyloading, ничего не делаем
	} else {
		// добавим класс
		// поддержка <source> - в этом случае, класс нужно добавлять к родителю
		if ($tagname == 'source'){
			if (WEBP_DEBUGMODE){
				writeLog('process_lazy(): у нас элемент source, нужно назначать класс родителю');
			}
			$classlist = $elem->parent()->getAttribute('class');
		} else {
			$classlist = $elem->getAttribute('class');
		}
		if (is_null($classlist)){
			// сейчас берем параметры, основываясь на теге
			// т.е. чтобы получить параметры для '.image-resp', мы должны определить параметры для всего тега 'img'.
			// надо поправить это неверное костыльное решение
			if (!isset($params['lazyload'][$tagname]) || !isset($params['lazyload'][$tagname]['class_add'])){
				$classlist = $params['lazyload'][$rezerv_tagname]['class_add'];
			} else {
				$classlist = $params['lazyload'][$tagname]['class_add'];
			}

		} else {
			if (!isset($params['lazyload'][$tagname]) || !isset($params['lazyload'][$tagname]['class_add'])){
				$addclass = $params['lazyload'][$rezerv_tagname]['class_add'];
			} else {
				$addclass = $params['lazyload'][$tagname]['class_add'];
			}

			// проверка, есть ли этот класс уже в массиве
			if (mb_strpos($classlist, $addclass) === false){
				$classlist.= ' '.$addclass;
			}
		}
		// продолжаем поддержку <source> - назначаем не самому элементу, а родителю
		if ($tagname == 'source'){
			if (WEBP_DEBUGMODE){
				writeLog('process_lazy(): у нас элемент source, нужно назначать класс родителю');
			}
			$elem->parent()->setAttribute('class', $classlist);
		} else {
			if (WEBP_DEBUGMODE){
				writeLog('process_lazy(): у нас обычный элемент, применяем класс к нему');
			}
			$elem->setAttribute('class', $classlist);
		}

		// добавим атрибут area-expand
		if (isset($params['lazyload'][$tagname]) && isset($params['lazyload'][$tagname]['expand_preload_area']) && $params['lazyload'][$tagname]['expand_preload_area']){
			if ($tagname == 'source'){
				$elem->parent()->setAttribute($params['lazyload'][$tagname]['expand_attr'], $params['lazyload'][$tagname]['expand_range']);
			} else {
				$elem->setAttribute($params['lazyload'][$tagname]['expand_attr'], $params['lazyload'][$tagname]['expand_range']);
			}
		} else {
			// иначе удалим
			// Захардкодил! Нужно бы исправить... todo
			$expand_attr = 'data-expand';
			if (isset($params['lazyload'][$tagname]) && isset($params['lazyload'][$tagname]['expand_attr'])){
				$expand_attr = $params['lazyload'][$tagname]['expand_attr'];
			}

			if ($tagname == 'source'){
				$elem->parent()->removeAttribute($expand_attr);
			} else {
				$elem->removeAttribute($expand_attr);
			}
		}
	}

}

function remove_lazy(&$document, &$params){
	$module_time = false;
	if (WEBP_DEBUGMODE){
		$module_time = microtime(true);
	}
	// удаляем lazyloading с селекторов в массиве параметров
	if ($params['ignore_lazy'] == false){
		if (WEBP_DEBUGMODE){
			writeLog('  remove_lazy(): $params["ignore_lazy"] == false');
		}
		return false;
	}

	$elems = $document->find($params['ignore_lazy']);
	if (count($elems) > 0){
		foreach ($elems as $elem) {
			unlazy($elem, $params);
		}
	}

	if (WEBP_DEBUGMODE){
		writeLog('  remove_lazy(): убралу lazy у '.count($elems).' элементов');
		writeLog('remove_lazy(): работали '. (microtime(true) - $module_time) . ' сек.');
	}
}

function unlazy(&$elem, &$params = false){
	// проверяет, установлен ли lazy в этом элементе. Если установлен, удаляет lazy-loading
	// не всегда для lazy может быть селектор по тегу; выборка могла быть по селектору, но параметры берутся по тегу!
	$tagname = $elem->tag;

	if (!isset($params['lazyload'][$tagname])){
		// Если нет умолчаний, назначаем тег 'div'.
		$tagname = 'div';
	}

	// теперь общие шаги
	$lazy_class = $params['lazyload'][$tagname]['class_add'];
	$attr_store_orig = $params['lazyload'][$tagname]['attr_store_orig'];
	$expand_area_attr = false;
	if (isset($params['lazyload'][$tagname]['expand_attr'])){
		$expand_area_attr = $params['lazyload'][$tagname]['expand_attr'];
	}

	// remove class
	$elem_class = $elem->getAttribute('class');
	if (!is_null($elem_class)){
		$elem_class = explode(' ', $elem_class);
		$class_exists = array_search($lazy_class, $elem_class);
		if ($class_exists !== false){
			unset($elem_class[$class_exists]);
		}
		$elem_class = implode(' ', $elem_class);
		$elem->setAttribute('class', $elem_class);
	}

	// transform store-orig
	$unlazied_src = $elem->getAttribute($attr_store_orig);
	if ($tagname == 'img'){
		// moving real image src from data-* to src/srcset attr
		if ($attr_store_orig == 'data-srcset'){
			$attr_store_unlaziedsrc = 'srcset';
		} else if ($attr_store_orig == 'data-src'){
			$attr_store_unlaziedsrc = 'src';
		}

		if (!is_null($unlazied_src)){
			$elem->setAttribute($attr_store_unlaziedsrc, $unlazied_src);
		}

		// remove store target attr
		$elem->removeAttribute($attr_store_orig);
	} else {
		// если это div (фоллбэк), нужно подменить в style заглушку на картинку
		$style = $elem->getAttribute('style');
		if ($style){
			$preloader = $params['lazyload'][$tagname]['inline_preloader_picture'];
			$style = str_replace($preloader, $unlazied_src, $style);
			$elem->setAttribute('style', $style);
		}
	}
	
	// remove expand area attr
	if ($expand_area_attr && $elem->hasAttribute($expand_area_attr)){
		$elem->removeAttribute($expand_area_attr);
	}
}

function generate_webp($elem, $filter_by_specific_extensions = false, $custom_path = false){
	if (WEBP_DEBUGMODE){
		writeLog('  generateWebp(): старт');
	}

	// генерирует webp на сервере
	// вернёт false в случае провала, или url webp-версии
	// $filter_by_specific_extensions - false или массив разрешенных
	$tagname = $elem->tag;

	// set home directory
	$home_dir = get_homedir();

	// trim custom path
	if ($custom_path){
		$custom_path = trim($custom_path, '/');
	}

	$src = false;
	if ($tagname == 'img'){
		$src = $elem->getAttribute('src');
		//remove get-parameters (?itok, for example)
		$src = strtok($src, '?');
	} else if ($tagname == 'video') {
		$src = $elem->getAttribute('poster');
		//remove get-parameters (?itok, for example)
		$src = strtok($src, '?');
	} else {
		$style_attr = $elem->getAttribute('style');
		if (!is_null($style_attr)){
			$src = parseBackgroundImgUri($style_attr);// получаем из инлайнового стиля
		} else {
			return false; // возвращаем false
		}
	}

	if (WEBP_DEBUGMODE){
		writeLog('  generate_webp(): $src = ' . var_export($src, true));
	}

	if ($src){
		// процессинг, с фильтром по расширениям
		$useWebp = false; // default
		if (check_extension_allowed_to_convert($src, $filter_by_specific_extensions)){
			// get abspath on server /var/user/home/http/site/...
			$img_server_abspath = parsePathFromSrc($src, $home_dir);

			if (WEBP_DEBUGMODE){
				writeLog('  generate_webp(): $img_server_abspath = ' . $img_server_abspath);
			}

			// check for 'false' - can be retrieved, if it is external image (retargeting pixel, for example)
			if ($img_server_abspath !== false){

				// set server abspath for generated webp
				if ($custom_path){
					
					// check for absolute link and store domain length
					// will have relative path from root
					$domain_length = mb_strpos($src, $_SERVER['SERVER_NAME']);
					if ($domain_length != false){
						$relative_src_path = substr($src, ($domain_length + strlen($_SERVER['SERVER_NAME'])));
					} else {
						$relative_src_path = $src;
					}

					// form webp abspath with custom prefix folder
					$processed_src_path = '/' . $custom_path . $relative_src_path; // relative with custom folder
					$img_webp_abspath = $home_dir . $processed_src_path . '.webp'; // full

				} else {
					$processed_src_path = $src;
					$img_webp_abspath = $img_server_abspath . '.webp';
				}

				if (WEBP_DEBUGMODE){
					writeLog('  generate_webp(): $img_webp_abspath = ' . $img_webp_abspath);
				}

				// then call converting
				$useWebp = callWebp($img_server_abspath, $img_webp_abspath); // true/false
			}
		}

		if (WEBP_DEBUGMODE){
			writeLog('  generate_webp(): $useWebp = ' . var_export($useWebp, true));
		}

		if ($useWebp){
			return $processed_src_path . '.webp';
		} else {
			return false;
		}

	} else {
		return false;
	}
}

// Принимает абсюлютный/относительный uri, возвращает относительный 
function getRelativeUri($src, $home_dir = false){
	
	if (!$home_dir){
		$home_dir = get_homedir();
	}

	// можно провести оптимизацию - если первый символ "/", то это уже относительный путь
	if ( mb_substr($src, 0, 1) == '/'){
		if (WEBP_DEBUGMODE){
			writeLog('  getRelativeUri(): ранний детект относительного пути - "'.$src.'" уже является таковым!');
		}
		return $src;
	}	

	// Это получит серверный путь оригинала
	$full_server_path = parsePathFromSrc($src, $home_dir); // false / path
		
	if ($full_server_path){
		// теперь можно заменить $home_dir на '' и получить относительный
		$docroot_relative_path = str_replace($home_dir, '', $full_server_path);

		if (WEBP_DEBUGMODE){
			writeLog('  getRelativeUri(): получили относительный "'.$docroot_relative_path.'"');
		}

		return $docroot_relative_path;

	} else {
		// вернулся false
		if (WEBP_DEBUGMODE){
			writeLog('  getRelativeUri(): не распознали серверный путь для $src="'.$src.'"');
		}
		return false;
	}
}

function parseBackgroundImgUri($bg_attr){
	// получаем атрибут 'style', выделяем из него url
	// удалим itok - так мы отбросим всё ненужное после расширения
	$bg_attr = strtok($bg_attr, '?');

	// разобъём стиль, удалим всё до url(
	$bg_attr_array = explode('url(', $bg_attr);

	// проверим, есть ли в инлайн-стиле изображение
	if (isset($bg_attr_array[1])){
		$bg_attr = $bg_attr_array[1];
	} else {
		return false;
	}

	// удаляем закрывающий ) нашего url и попутно избавимся от лишнего
	$bg_attr_array = explode(')', $bg_attr);
	$bg_attr = $bg_attr_array[0];


	// осталось убрать кавычки в начале и конце
	$bg_attr = trim($bg_attr, '\'"');

	return $bg_attr;
}

function check_extension_allowed_to_convert($src, $allowed_extensions = false){

	if (WEBP_DEBUGMODE){
		writeLog('  check_extension_allowed_to_convert(): старт');
	}

	// $allowed_extensions - false or comma-separated extensions
	// if $allowed_extensions == false, only checking for $src doesn't webp already
	// if $allowed_extensions, transform it to array, check if webp, and then check for each of extensions

	// дропнем get-параметры
	$src = strtok($src, '?');

	if (mb_substr($src, -5) == '.webp'){

		if (WEBP_DEBUGMODE){
			writeLog('      уже webp');
		}

		return false; // already webp, skipping
	} else {
		// get extension
		// получаем позицию точки.
		// получаем длину строки, отнимаем ++позицию, получаем количество символов, которое возьмем с конца
		$ext_pos = strripos($src, '.');
		$src_length = strlen($src);
		$ext_length = $src_length - (++$ext_pos);
		$extension = substr($src, -$ext_length);
		$extension = mb_strtolower($extension);
		unset($ext_pos, $src_length, $ext_length);

		// проверяем список разрешенных
		// если списка нет, проверяем чтобы было jpg, jpeg или png
		if ($allowed_extensions !== false){

			// конвертим в массив
			if (is_string($allowed_extensions)){
				$allowed_extensions = normalize_commaseparated($allowed_extensions); // to array
			}

			// ищем текущее расширение в списке разрешённых
			if (in_array($extension, $allowed_extensions)){

				if (WEBP_DEBUGMODE){
					writeLog('  check_extension_allowed_to_convert(): расширение = '.$extension.', разрешено');
				}

				return true;
			}
		} else {
			if ( ($extension == 'jpg') || ($extension == 'jpeg') || ($extension == 'png') ){

				if (WEBP_DEBUGMODE){
					writeLog('  check_extension_allowed_to_convert(): расширение = '.$extension.', разрешено');
				}

				return true;
			} else {

				if (WEBP_DEBUGMODE){
					writeLog('  check_extension_allowed_to_convert(): расширение = '.$extension.', запрещено');
				}

				return false;
			}
		}
	}
}

function parsePathFromSrc($image_orig_uri, $home_dir = false){
//получает серверный путь картинки из URI

	if (WEBP_DEBUGMODE){
		writeLog('  parsePathFromSrc(): стартовали');
		writeLog('      $image_orig_uri = '.$image_orig_uri);
	}

	if (!$home_dir){
		if (WEBP_DEBUGMODE){
			writeLog('      $home_dir не передан, вычисляем');
		}
		$home_dir = get_homedir();
	}

	if (WEBP_DEBUGMODE){
		writeLog('      $home_dir = '.$home_dir);
	}

	// первым делом мы должны проверить, внешний ли это урл, и принадлежит ли это нашему домену.
	// если есть ttp:/ или tps:/ и, при этом, там НЕТ нашего домена, возвращаем false
	if ( ( (strpos($image_orig_uri, 'ttp:/') !== false) || (strpos($image_orig_uri, 'tps:/') !== false) ) && ( strpos($image_orig_uri,$_SERVER['SERVER_NAME']) === false ) ){

		if (WEBP_DEBUGMODE){
			writeLog('      Внешний url, без нашего домена. Отказ.');
		}

		return false;
	}

	// также нужно проверить, чтобы это был не data:image...
	if ( mb_substr($image_orig_uri, 0, 5) == 'data:'){
		if (WEBP_DEBUGMODE){
			writeLog('      data-url. Отказ.');
		}
		return false;
	}

	// Теперь надо отбросить get-параметры
	$image_orig_uri = strtok($image_orig_uri, '?');

	if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443){
		$protocol = 'https';
	} else {
		$protocol = 'http';
	}

	$search_by = $protocol . ':'; // проверяем, с хостом ли путь к картинке ( http<s>: )
	$domain_replace = $protocol . '://' . $_SERVER['SERVER_NAME']; // будем удалять этот домен из uri

	if (strpos($image_orig_uri, $search_by) !== false){
    	$image_orig_abspath = str_replace($domain_replace, $home_dir, $image_orig_uri);
    } else {

    	if (WEBP_DEBUGMODE){
			writeLog('      Не удалось удалить наш расчитанный домен из пути:');
			writeLog('      $image_orig_uri = '.$image_orig_uri.', $search_by = '.$search_by);
		}

    	// принудительная обработка путей - будем всегда считать, что передано от корня. todo
	    if (WEBP_DEBUGMODE){
			writeLog('      Включено принудительное уравнение пути к корню!');
			writeLog('      Например, images/img.jpg будет приведено к /images/img.jpg');
			writeLog('      Изначальная $image_orig_abspath: '.$home_dir.$image_orig_uri);
		}

		$image_orig_uri = trim($image_orig_uri, '/');
		$image_orig_uri = '/'.$image_orig_uri;

    	$image_orig_abspath = $home_dir . $image_orig_uri;

    	if (WEBP_DEBUGMODE){
			writeLog('      Итоговая $image_orig_abspath: '.$image_orig_abspath);
		}
    }

    return $image_orig_abspath;
}

function do_additional_operations(&$document, &$params){
	$module_time = false;
	if (WEBP_DEBUGMODE){
		$module_time = microtime(true);
		writeLog('do_additional_operations(): Старт...');
	}

	if (!isset($params['additional_operations'])){
		if (WEBP_DEBUGMODE){
			writeLog('do_additional_operations(): Список операций пуст, выходим.');
		}
		return false;
	}

	// Вызываем специфические операции
	if (isset($params['additional_operations']['instantpage'])){
		if ($params['additional_operations']['instantpage']['enabled']){
			if (WEBP_DEBUGMODE){
				writeLog('do_additional_operations(): Запуск instantpage-процессора');
			}
			process_instantpage($document, $params);
		}
	}

	if (WEBP_DEBUGMODE){
		writeLog('do_additional_operations(): работали '. (microtime(true) - $module_time) . ' сек.');
	}
}

// раз уж мы всё равно загрузили DiDOM, можем выполнить какие-то операции с готовой разметкой, ради которых без парсера нам бы пришлось лопатить код модулей
// например, можно удалить невидимый и нарушающий иерархию h2 от хлебных крошек
function do_additional_hardcoded_operations(&$document, &$params){
	$module_time = false;
	if (WEBP_DEBUGMODE){
		$module_time = microtime(true);
		writeLog('do_additional_hardcoded_operations(): Старт...');
	}

	/*$hardcoded_code_file = WEBPPROJECT.'/additional_works.php';
	if (!file_exists($hardcoded_code_file) || (filesize($hardcoded_code_file) < 9 )){
		
		// проверка фоллбэка
		if (defined('OUTPUTMOD_WEBP_CORE_FALLBACK_LOCATION')){
			$fallback_path = $_SERVER['DOCUMENT_ROOT'].'/'.OUTPUTMOD_WEBP_CORE_FALLBACK_LOCATION.'/additional_works.php';
			if (file_exists($hardcoded_code_file) && (filesize($hardcoded_code_file) > 8 )){
				$hardcoded_code_file = $fallback_path;
			}
		} else if (WEBP_DEBUGMODE){
			writeLog('do_additional_hardcoded_operations(): '.$hardcoded_code_file.' отсутствует или пуст. Выход...');
		}

		return false;
	}

	if (WEBP_DEBUGMODE){
		writeLog('do_additional_hardcoded_operations(): Подключаем php доп. операций, путь:'.PHP_EOL.$hardcoded_code_file);
	}
	include_once($hardcoded_code_file);*/

	if (function_exists('didom_hardcoded_ops')){
		$didom_hardcoded_result = didom_hardcoded_ops($document, $params);
	} else {
		if (WEBP_DEBUGMODE){
			writeLog('do_additional_hardcoded_operations(): функции didom_hardcoded_ops() не существует. Выход...');
		}
		return false;
	}

	if (WEBP_DEBUGMODE){
		writeLog('do_additional_hardcoded_operations(): Инклуд прошёл, отработали, результат '.($didom_hardcoded_result ? 'true' : 'false'));
		writeLog('do_additional_hardcoded_operations(): работали '. (microtime(true) - $module_time) . ' сек.');
	}
	return true;
}

function process_instantpage(&$document, &$params){
	if ($params['additional_operations']['instantpage']['mode'] == false){
		if (WEBP_DEBUGMODE){
			writeLog('  process_instantpage(): Режим белого списка');
		}
		if ($params['additional_operations']['instantpage']['whitelist_selectors']){
			// setted whitelist-mode - we're must mark links to preload with "data-instant"-attribute
			$whitelist = $params['additional_operations']['instantpage']['whitelist_selectors'];
			$links = $document->find($whitelist);

			if (count($links) > 0){
				foreach ($links as $elem) {
					$elem->setAttribute('data-instant', 'yes');
				}
			}

			// mark body
			$body = $document->first('body');
			if ($body){
				$body->setAttribute('data-instant-whitelist', 'true');
			}
		} else {
			if (WEBP_DEBUGMODE){
				writeLog('  process_instantpage(): селекторы не обнаружены - ничего не пометили');
			}
		}
	} else {
		if (WEBP_DEBUGMODE){
			writeLog('  process_instantpage(): Обычный режим, манипуляции не производим');
		}
	}

	return true;
}

function convertUriToCDN($uri = false, &$params = false){
	// поддержка пути "images/img.jpg", "/images/img.jpg" => https://cdn.example.ru/images/img.jpg

	if ($params == false){
		if (WEBP_DEBUGMODE){
			writeLog('  convertUriToCDN(): Не передан массив параметров');
		}
		return false;
	} else {
		if (WEBP_DEBUGMODE){
			writeLog('  convertUriToCDN(): Старт');
		}
	}

	$cdndomain = false;
	if (isset($params['cdn']['domain'])){
		$cdndomain = trim($params['cdn']['domain']);
	}

	if (WEBP_DEBUGMODE){
		writeLog('  convertUriToCDN(): $uri = '.$uri.', CDN = '.$cdndomain);
	}

	if ($cdndomain == false || $uri == false){
		if (WEBP_DEBUGMODE){
			writeLog('  convertUriToCDN(): Некорректный $cdndomain или $uri');
		}
		return false;
	}

	// default
	$cdn_path = false;

	if (stripos($uri, '://') !== false){

		if ($params['cdn']['external']){
			if (WEBP_DEBUGMODE){
				writeLog('  convertUriToCDN(): Внешний $uri, но включено для поддоменов');
			}

			// Нужно проверить, присутствует ли наш домен в полученном внешнем url.
			// Если присутствует, то отлично

			$parsed_url = parse_url($uri);
			if ($parsed_url['host'] == $_SERVER['SERVER_NAME']){
				if (WEBP_DEBUGMODE){
					writeLog('  convertUriToCDN(): Это наш домен, превращаем в CDN');
				}

				$cdn_path = $cdndomain.$parsed_url['path'];

				if (isset($parsed_url['query'])){
					$cdn_path .= '?'.$parsed_url['query'];
				}


			} else {
				if (WEBP_DEBUGMODE){
					writeLog('  convertUriToCDN(): Внешний домен = '.$parsed_url['host'].', $_SERVER["SERVER_NAME"] = '.$_SERVER['SERVER_NAME']);
				}
			}

		} else {
			if (WEBP_DEBUGMODE){
				writeLog('  convertUriToCDN(): Внешний $uri, возвращаем false');
			}

			$cdn_path = false;
		}

	} else if (mb_substr($uri, 0, 1) == '/'){
		$cdn_path = $cdndomain.$uri;
		if (WEBP_DEBUGMODE){
			writeLog('  convertUriToCDN(): относительный от корня, вернём '.$cdndomain.$uri);
		}
	} else {
		$cdn_path = $cdndomain.$_SERVER['REQUEST_URI'].'/'.$uri;
		if (WEBP_DEBUGMODE){
			writeLog('  convertUriToCDN(): относительный от страницы, вернём '.$cdn_path);
		}
	}

	if (WEBP_DEBUGMODE){
		writeLog('  convertUriToCDN(): возвращаем '.$cdn_path);
	}
	return $cdn_path;
}

function additionalImgOperations(&$document, &$params = false){
	if (!$params){
		return false;
	}

	if (WEBP_DEBUGMODE){
		writeLog('  additionalImgOperations(): зашли в функцию');
	}

	if ( !($params['add_chromelazy_img']) && !($params['asyncimg']) && !($params['img_setsize']) && !($params['fallback_alt']) ){
		if (WEBP_DEBUGMODE){
			writeLog('  additionalImgOperations(): не назначено ни одной доп. операции для <img>, выходим');
		}
		return false;
	}

	$imgs = $document->find('img');

	foreach ($imgs as $elem) {
		// проверка на исключение по фильтру
		if ($params['ignore_imgs']){
			if ($elem->matches($params['ignore_imgs'])){
				continue;
			};
		}

		// подключаем атрибут loading="lazy", нативная реализация lazy load.
		if ($params['add_chromelazy_img'] !== false){
			$elem->setAttribute('loading', $params['add_chromelazy_img']);
		}

		// подключаем атрибут decoding="async"
		if ($params['asyncimg']){
			$elem->setAttribute('decoding', 'async');
		}

		// добавление ширины/высоты
		// просто берём src, и узнаём о нём информацию
		if ($params['img_setsize']){
			add_img_sizes($elem, $params['img_setsize']);
		}

		// Добавление пустого alt, если отсутствует
		if ($params['fallback_alt']){
			add_fallback_alt($elem, $params);	
		}
	}

	if (WEBP_DEBUGMODE){
		writeLog('  additionalImgOperations(): закончили обработку');
	}

	return true;
}

function modifyImagesWebp($output, $params = false){
	if (!$output){
		return $output;
	}

	// mixing received params with defaults
	$params = mix_params($params);
	
	// add debug-headers
	$module_time = false;
	if ($params['debug'] || isset($_GET['outputmoddebug'])){
		$module_time = microtime(true); // замер скорости работы
		define('WEBP_DEBUGMODE', true);
		add_debugheader('used', 'used');
	} else {
		define('WEBP_DEBUGMODE', false);
	}

	// set home directory
	$home_dir = get_homedir();

	if (WEBP_DEBUGMODE){
		writeLog('Стартовали');
	}

	// is enough preparations, let's work!
	// default
	$moddedhtml = $output;

	// flag for detecting $output is contained <html> tag
	// if not, before returning we will remove generated <html><body> tags
	if (WEBP_DEBUGMODE){
		writeLog('Проверка, подрезаем ли html');
	}
	$moddedhtml_startpart = substr($moddedhtml, 0, 32);
	if (stristr($moddedhtml_startpart, '<html')){
		if (WEBP_DEBUGMODE){
			writeLog('Получили $outut с тегом <html>');
		}
		$received_html_tag = true;
	} else {
		if (WEBP_DEBUGMODE){
			writeLog('Мы получали строку без <html>');
		}
		$received_html_tag = false;
	}
	unset($moddedhtml_startpart);

	// Воюем с кодировкой (по необходимости)
	//$moddedhtml = mb_convert_encoding($moddedhtml, 'HTML-ENTITIES', 'UTF-8');
	//$moddedhtml = html_entity_decode($moddedhtml);

	// load DOM
	$document = new Document($moddedhtml);

	// две задачи - пройти webp и пройти lazy loading.
	// сначала проходим webp. запускаем process_webp($document, $params);
	//		будет глобальная функция для прохода всего и вся process_lazy($document, $params)
	//		и отдельная для одного элемента process_lazy_once($elem, $params)
	// после того, как выполнили webp-конвертацию, проходим массив lazyloads.
	// ! если при проходе lazy мы натыкаемся на имеющийся атрибут srcset, его надо переместить в data-srcset
	// иначе тупо перепишем его инлайновой заглушкой, а там мог быть webp

	// Шаг 1. Проходим avif и webp
	// сначала avif, т.к. здесь мы не переписываем например оригинальный атрибут style, и не мешаем детекту оригинального пути
	process_avif($document, $params);
	process_webp($document, $params);

	// Шаг 2. Операции для img
	additionalImgOperations($document, $params);

	// Шаг 3. Проходим LazyLoading
	process_lazy($document, $params);

	// Шаг 4. "Чёрный лист" для lazy-loading
	// Не используем метод matches(), т.к. он не работает со сложными селекторами типа ".parent .child"
	remove_lazy($document, $params);

	// Шаг 5. Дополнительные операции для DiDom, раз уж всё равно загрузили парсер.
	do_additional_operations($document, $params);
	do_additional_hardcoded_operations($document, $params);

	// return processed

	// check, strip html or not
	if ($received_html_tag){
		if (WEBP_DEBUGMODE){
			writeLog('Получен элемент с <html>');
		}
		// получен html
		if ($params['strip_html']){
			if (WEBP_DEBUGMODE){
				writeLog('Включено удаление <html>, вёрнём body->innerHTML');
			}
			$moddedhtml = $document->first('body')->innerHtml();
		} else {
			if (WEBP_DEBUGMODE){
				writeLog('Возвращаем цельный html');
			}
			$moddedhtml = $document->html();
		}
	} else {
		// получен body. Наверное.
		if (WEBP_DEBUGMODE){
			writeLog('Получен элемент без <html>, возвращаем body->innerHTML');
		}

		$check_body_is_exists = $document->first('body');
		if ($check_body_is_exists){
			$moddedhtml = $document->first('body')->innerHtml();
		} else {
			if (WEBP_DEBUGMODE){
				writeLog('Получен элемент без <body>, возвращаем $document->html()');
			}
			$moddedhtml = $document->html(); 
		}
	}

	// Воюем с кодировкой
	if ($params['html_entity_decode']){
		$moddedhtml = html_entity_decode($moddedhtml);
	}

	// Минификация
	if ($params['minify_html']){
		if (WEBP_DEBUGMODE){
			writeLog('Запускаем минификацию');
			$minify_time = microtime(true);
		}
		$moddedhtml = \OutputmodMinify::minify($moddedhtml);
		if (WEBP_DEBUGMODE){
			writeLog('Минификация заняла '. (microtime(true) - $minify_time) . ' сек.');
		}
	}

	if (WEBP_DEBUGMODE){
		writeLog(PHP_EOL.PHP_EOL.'Модификатор отработал'.PHP_EOL.PHP_EOL);
	}

	// конец замеров скорости работы
	if (WEBP_DEBUGMODE){
		$worked_in = microtime(true) - $module_time;
		add_debugheader('worked_in', $worked_in);
		writeLog('Весь модуль: работали '. $worked_in . ' сек.');
	}
	return $moddedhtml;
}

?>