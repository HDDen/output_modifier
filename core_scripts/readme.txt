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

############################################################################
#### Set webp-custom path                                               ####
############################################################################
RewriteRule .* - [E=WEBP_CUSTOM_PATH]
#RewriteRule .* - [E=WEBP_CUSTOM_PATH:/webp]
############################################################################
#### Redirect to existing converted image (under appropriate circumstances) 
############################################################################
RewriteCond %{HTTP_COOKIE} !^.*webpactive=false.*$ [NC]
RewriteCond %{HTTP_REFERER} !admin [NC]
RewriteCond %{HTTP_ACCEPT} image/webp [OR]
RewriteCond %{HTTP_COOKIE} ^.*webpactive=true.*$ [NC]
RewriteCond %{DOCUMENT_ROOT}%{WEBP_CUSTOM_PATH}e/$1.$2.webp -f
RewriteRule ^\/?(.*)\.(jpe?g|png)$ %{WEBP_CUSTOM_PATH}e/$1.$2.webp [NC,T=image/webp,L]

############################################################################
#### Redirect images to webp-on-demand.php (if browser supports webp)   ####
############################################################################
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
      try_files $image_nextgen_path$uri$image_nextgen_format /php/webp/core_scripts/webp-on-demand.php;
    }
    ...
  }
}





Немного иной пример, проверяем сначала avif-версию, затем webp, а затем отправляемся в php. А в предыдущем мы искали только самый максимальный формат, а webp отдавали средствами php

# check supporting webp/avif
map $http_accept $avif_format {
  default   "";
  "~*avif"  ".avif";
}
map $http_accept $webp_format {
  default   "";
  "~*webp"  ".webp";
}

server{

  set $avif_path /avif;
  set $webp_path /webp;

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
      types {
        image/jpeg jpeg jpg;
        image/png png;
        image/avif avif;
        image/webp webp;
      }
      expires 365d;
      #default_type image/avif; # небезопасно! Переопределение дефолта, но по идее должно отрабатывать корректно
      try_files  $avif_path$uri$avif_format $webp_path$uri$webp_format /php/webp/core_scripts/webp-on-demand.php;
    }
    ...
  }
}



И третий вариант; частный случай, полезен, если нужно передавать управление куда-то еще.

# проверяем поддержку webp/avif
map $http_accept $avif_format {
  default   "";
  "~*avif"  ".avif";
}
map $http_accept $webp_format {
  default   "";
  "~*webp"  ".webp";
}

server{

  # установка путей
  set $avif_path /avif;
  set $webp_path /webp;

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
    types {
      image/jpeg jpeg jpg;
      image/png png;
      image/avif avif;
      image/webp webp;
    }
    expires 365d;
    try_files  $avif_path$uri$avif_format $webp_path$uri$webp_format @webp_gen;
  }
  # локейшн генератора
  location @webp_gen {
    error_page   404  =  @shop_thumb;
    include /etc/nginx/fastcgi_params;
    fastcgi_intercept_errors on;
    fastcgi_pass unix:/run/php/php7.2-fpm-ersh-svet.sock;
    # fastcgi_pass unix:/run/php/php5.6-fpm-ersh.sock;
    fastcgi_param  SCRIPT_NAME  /php/webp/webp-on-demand.php;
    fastcgi_param  SCRIPT_FILENAME  $document_root/php/webp/webp-on-demand.php;
  }
  # сюда уйдёт управление в случае ошибки
  location @shop_thumb {
    include /etc/nginx/fastcgi_params;
    fastcgi_pass unix:/run/php/php7.2-fpm-ers.sock;
    # fastcgi_pass unix:/run/php/php5.6-fpm-ers.sock;
    fastcgi_param  SCRIPT_NAME  /products/thumb.php;
    fastcgi_param  SCRIPT_FILENAME  $document_root/products/thumb.php;
  }
}
