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

webp-on-demand.php : можно настроить использование nginx ($useNginx = true;), а именно заголовок X-Accel-Redirect

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
  RewriteRule ^(.*)\.(jpe?g|png)$ /other-includ/webp/webp-on-demand.php [NC,L]

Пример с nginx:

# check supporting webp/avif
map $http_accept $image_nextgen_format {
  default   "";
  "~*avif"  ".avif";
  "~*webp"  ".webp";
}
map $http_accept $image_nextgen_path {
  default   "";
  "~*avif"  "/avif";
  "~*webp"  "/webp";
}

server{
  location / {
    ...
    # просто добавляем тип для avif
    location ~* .avif$ {
      types {
        image/avif avif;
      }
      default_type image/avif;
      expires 365d;
      try_files $uri $uri/;
    }
    # рулим правилами для преобразования
    location ~* ^.+\.(jpe?g|png)$ {
      expires 365d;
      default_type image/avif; # небезопасно! Переопределение дефолта, но по идее должно отрабатывать корректно
      try_files $image_nextgen_path$uri$image_nextgen_format /bitrix/modules/hdden.outputmodifier/core_scripts/webp-on-demand.php;
    }
    ...
  }
}
