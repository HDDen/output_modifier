Скрипты из этой папки допускается использовать вне друпала, т.к. модуль - лишь интерфейс для настройки.

Возможен запуск только с html-разметкой, без опций. Подтянутся дефолтные.
Пример:

// webp
ob_start();
// <html>...</html>
$modified_output = ob_get_clean();
include_once $_SERVER['DOCUMENT_ROOT'] . '/php/webp/output_modifier.php';
if (function_exists('modifyImagesWebp')){
  $modified_output = modifyImagesWebp($modified_output);
}
echo $modified_output;
// end webp

Если используем lazyload, нужно подключить к сайту библиотеку lazysizes (staff/js/lazysizes)

lazysizes.min.js отвечает за поддержку на img, lazysizes.unveilhooks.min.js - на остальных блоках, где фоновое изображение прописано в инлайновом стиле

Пример запуска:

function process_html($html_string) {

  require('_settings.php'); // настройки по-умолчанию
	include_once $_SERVER['DOCUMENT_ROOT'] . '/'.trim($webp_core_fallback_location, '/').'/output_modifier.php';
  if (function_exists('modifyImagesWebp')){

    $params = array(
      'webp' => array(
        'img' => true, // convert to webp
        'img_webpstore_attr' => 'srcset', // where store webp-version
        'by_selector' => false, // false or comma-separated css-selectors, e.g. '.img, .img-resp'. Other imgs will be ignored
        'allowed_extensions' => false, // false or comma-separated extensions without dots
        'additional_tags' => false, // false or comma-separated css-selectors
        'ignore_webp_on' => false, // false or comma-separated css-selectors
        'force' => false, // force pushing webp-version
      ),
      'lazyload' => array( // array of tags or selectors
        'img' => array( // parameters for others is equal, copy and change tagname
          'lazy' => false, // shortcut and option for quickly disable
          'class_add' => 'lazyload', // add classes, comma-separated (or just string with spaces? TODO!)
          'attr_store_orig' => 'data-srcset', // attr to store original, lazy-loaded img src
          'inline_preloader_picture' => 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==', // inline variant of plug-preview
          'expand_preload_area' => true, // expand image load area before it displays
          'expand_attr' => 'data-expand', // from where read 'expand_preload_area' parameter
          'expand_range' => '500', // default for expanding
        ),
        'div' => array(
          'lazy' => false, // dont process tag globally
          'class_add' => 'lazyload', // add classes, comma-separated (or just string with spaces? TODO!)
          'attr_store_orig' => 'data-bg', // attr to store original, lazy-loaded img src
          'inline_preloader_picture' => 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==', // inline variant of plug-preview
          'expand_preload_area' => true, // expand image load area before it displays
          'expand_attr' => 'data-expand', // from where read 'expand_preload_area' parameter
          'expand_range' => '500', // default for expanding
              ),
              'iframe' => array(
                'lazy' => false, // disabled by default, recommend use lazy+use_chromelazy_instead to prevent disabling analytics iframe
                'add_chromelazy' => 'lazy', // just add loading="lazy" attr, false / value (auto|lazy|eager)
                'use_chromelazy_instead' => true, // use loading="lazy" attr instead of js-plugin
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
      'ignore_lazy' => false, // selectors for ignoring lazyload. In fact, these elems will be lazied, then unlazied :-/
      'add_chromelazy_img' => false, // add loading="lazy" attr to img, false or attr value (auto|lazy|eager)
      'caching' => false, // opt for enabling/disabling caching
      'debug' => false, // opt for enabling/disabling debug headers
      'place_log' => false, // path for output_modifier logfile
    );

    $modified_output = modifyImagesWebp($html_string, $params);
    // или $modified_output = modifyImagesWebp($html_string)

    return $modified_output;
  }
}

webp-on-demand-proxy.php : можно настроить использование nginx ($useNginx = true;), а именно заголовок X-Accel-Redirect



Также можно конвертировать изображение без прибегания к парсеру, напрямую через апач (пример взят из документации к webpconvert):

# Redirect to existing converted image (under appropriate circumstances)
  #RewriteCond %{HTTP_COOKIE} !^.*webpactive=false.*$ [NC]
  #RewriteCond %{HTTP_REFERER} !admin [NC]
  #RewriteCond %{HTTP_ACCEPT} image/webp [OR]
  #RewriteCond %{HTTP_COOKIE} ^.*webpactive=true.*$ [NC]
  #RewriteCond %{DOCUMENT_ROOT}/$1.$2.webp -f
  #RewriteRule ^\/?(.*)\.(jpe?g|png)$ /$1.$2.webp [NC,T=image/webp,L]

  # Redirect images to webp-on-demand.php (if browser supports webp)
  #RewriteCond %{HTTP_COOKIE} ^.*deb=true.*$ [NC]
  #RewriteCond %{HTTP_HOST} ^(.*)\.site\.ru$ [NC] # для конкретного сайта / поддомена
  RewriteCond %{HTTP_COOKIE} !^.*webpactive=false.*$ [NC]
  RewriteCond %{HTTP_REFERER} !admin [NC]
  #RewriteCond %{REQUEST_URI} !^/admin(.*)$ [NC]
  #RewriteCond %{REQUEST_URI} !^admin(.*)$ [NC]
  RewriteCond %{REQUEST_URI} \.(jpg|jpeg|png)$ [NC]
  RewriteCond %{REQUEST_FILENAME} -f
  RewriteCond %{HTTP_COOKIE} ^.*webpactive=true.*$ [NC,OR]
  RewriteCond %{HTTP_ACCEPT} image/webp [NC,OR]
  RewriteCond %{HTTP_USER_AGENT} Chrome [OR]
  RewriteCond %{HTTP_USER_AGENT} "Google Page Speed Insights"
  RewriteRule ^(.*)\.(jpe?g|png)$ /other-includ/webp/webp-on-demand-proxy.php [NC,L]