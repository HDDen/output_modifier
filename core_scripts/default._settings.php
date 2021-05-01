<?php

// Здесь расположены настройки, которые могут понадобиться в разнообразных частях скрипта, и которые нужно менять от проекта к проекту
// Переименуйте в _settings.php

// путь к папке модификатора относительно корня сайта
$webp_core_fallback_location = 'other-includ/webp';
// автовычисление пути
if (!file_exists($_SERVER['DOCUMENT_ROOT'].'/'.$webp_core_fallback_location)){
    $outputmod__docrootlength = strlen(rtrim($_SERVER['DOCUMENT_ROOT'], '/')) + 1; // docroot + slash
    $webp_core_fallback_location = substr(__DIR__, $outputmod__docrootlength);
    unset($outputmod__docrootlength);
}
// и зафиксируем в константе
if (!defined('OUTPUTMOD_WEBP_CORE_FALLBACK_LOCATION')){
    define('OUTPUTMOD_WEBP_CORE_FALLBACK_LOCATION', $webp_core_fallback_location);
}

// дефолтные настройки модификатора
$default_params = array(
    'webp' => array(
        'img' => false, // convert to webp
        'img_webpstore_attr' => 'srcset', // where store webp-version
        'by_selector' => false, // false or comma-separated css-selectors, e.g. '.img, .img-resp'. Other imgs will be ignored
        'allowed_extensions' => false, // false or comma-separated extensions without dots
        'additional_tags' => false, // false or comma-separated css-selectors
        'ignore_webp_on' => false, // false or comma-separated css-selectors
        'force' => false, // force pushing webp-version
        'store_converted_in' => false, // false or relative path, which will be added to orig path structure
        'quality' => 87,
        'jpeg_quality' => 87, // 'auto' / integer
        'jpeg_max_quality' => 87,
        'jpeg_defaultquality' => 87,
        'cwebp' => array(
            'commandline_options' => false, // false / string. Can add '-sharp_yuv', for example
            'cwebp_try_precompiled' => true, // try precompiled cwebp-binaries, if isnt operable in system
            'cwebp_use_precompiled_as_main' => false, // true or false. True disables using system version of cwebp
            'relative_path' => false, // false / string. Custom relative path to binaries of cwebp from Cwebp.php
        ),
        'wpc' => array(
            'crypt_key' => false,
            'key' => '',
            'url' => '',
        ),
    ),
    'avif' => array(
        'enabled' => false, // search and store avif-version
        'process_on' => '', // empty or comma-separates selectors
        'path_prefix' => false, // false or prefix for original path structure, e.g. 'avif'. No slashes at begin or end 
    ),
    'lazyload' => array( // array of tags or selectors
        'img' => array( // parameters for others is equal, copy and change tagname
            'lazy' => false, // shortcut and option for quickly disable
            'class_add' => 'lazyload', // add classes, comma-separated (or just string with spaces? TODO!)
            'attr_store_orig' => 'data-srcset', // attr to store original, lazy-loaded img src
            'inline_preloader_picture' => "data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%20874%20589'%3E%3C/svg%3E", // svg is free to resize
            'expand_preload_area' => true, // expand image load area before it displays
            'expand_attr' => 'data-expand', // from where read 'expand_preload_area' parameter
            'expand_range' => '500', // default for expanding
            'use_native' => false, // false/true. Use only loading="lazy"
        ),
        'div' => array(
            'lazy' => false, // dont process tag globally
            'class_add' => 'lazyload', // add classes, comma-separated (or just string with spaces? TODO!)
            'attr_store_orig' => 'data-background-image', // attr to store original, lazy-loaded img src
            'inline_preloader_picture' => "data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%20874%20589'%3E%3C/svg%3E",
            'expand_preload_area' => true, // expand image load area before it displays
            'expand_attr' => 'data-expand', // from where read 'expand_preload_area' parameter
            'expand_range' => '500', // default for expanding
        ),
        'iframe' => array(
            'lazy' => false, // disabled by default, recommend use lazy+use_chromelazy_instead to prevent disabling analytics iframe
            'add_chromelazy' => 'lazy', // just add loading="lazy" attr, false / value (auto|lazy|eager)
            'use_chromelazy_instead' => false, // use loading="lazy" attr instead of js-plugin
        ),
        'iframe[src*="youtube.com"]' => array( // disabled, but pre-configured
          'lazy' => false,
          'use_chromelazy_instead' => false,
        ),
    ),
    'additional_operations' => array( // additional useful and specific operations for DIDOM.
        'instantpage' => array(
          'enabled' => false,
          'mode' => false, // false for whitelist (its more safe), 'regular' for regular.
          'whitelist_selectors' => false, // false or string with selectors for prefetching
        ),
    ),
    'cdn' => array( // opt for supporting CDN. Now this is limited to the adding cdn-domain to relative paths
        'enabled' => false, // false / true
        'domain' => false, // false or domain like "https://cdn.example.ru"
        'external' => false, // false/true for abspaths/subdomains
        'base_host' => false, // false or base domain like 'example.co.uk'
    ),
    'strip_html' => false, // return whole <html> document or only <body> inner
    'ignore_lazy' => false, // selectors for ignoring lazyload. In fact, these elems will be lazied, then unlazied :-/
    'add_chromelazy_img' => false, // add loading="lazy" attr to img, false or attr value (auto|lazy|eager)
    'caching' => false, // opt for enabling/disabling caching
    'debug' => false, // opt for enabling/disabling debug headers
    'place_log' => false, // path for output_modifier logfile
    'asyncimg' => false, // false/true, will add attr "decoding=async" to all <img>
    'img_setsize' => false, // false / integer, mode for adding width/height attributes to al <img>
    'fallback_alt' => false, // add empty 'alt'-attribute if doesnt exists
);