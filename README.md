
# output_modifier
Набор php-скриптов для добавления к сайту webp/avif-изображений, LazyLoad (две библиотеки на выбор), и возможности модификации html-разметки. Используются проекты: [rosell-dk/webpconvert](https://github.com/rosell-dk/webp-convert), [Imangazaliev/DiDOM](https://github.com/Imangazaliev/DiDOM), [ApoorvSaxena/lozad.js](https://github.com/ApoorvSaxena/lozad.js), [aFarkas/lazysizes](https://github.com/aFarkas/lazysizes).

# Принцип работы:
1) Output_modifier.php получает html-разметку;
2) DiDOM строит из неё документ, собирает пути к исходным изображениям из указанных в _setings.php селекторов;
3) WebpConvert получает список изображений, подлежащих конвертированию, преобразовывает их, и сообщает о результате;
4) Если всё прошло гладко, DiDOM подставляет src webp-версии в указанный в _settings.php атрибут; далее подключается LazyLoad;
5) Затем выполняются дополнительные модификации, включенные в настройках (добавление width/height и alt к img, подключение предзагрузки навигации), затем определённые пользователем операции, указанные в additional_works.php; затем документ преобразовывается назад в строку;
6) Output_modifier.php возвращает html-разметку.
7) ???????
8) PROFIT, сайт ускорен!

# Быстрый старт:

    include_once $_SERVER['DOCUMENT_ROOT'] . '/php/webp/output_modifier.php';
    if (function_exists('modifyImagesWebp')){
        ob_start();
    }
    // <html>...</html>
    if (function_exists('modifyImagesWebp')){
        $modified_output = ob_get_clean();
        $modified_output = modifyImagesWebp($modified_output);
        echo $modified_output;
    }

Также, в случае использования LazyLoad, нужно подключить библиотеку Lozad.js/LazySizes.js из папки ./staff/js/ .

Avif-файлы не конвертируются "на лету", их нужно подготовить самим и положить либо рядом с оригиналами, либо в отдельную папку, сохранив оригинальную структуру папок, и указав эту подпапку в настройке $params['avif']['path_prefix'].
Для webp тоже можно указать префикс пути, $params['webp']['store_converted_in'].

Также, если нет возможности подключения плагина к сайту, можно использовать перенаправление запросов *.jpg/*.png на файл webp-on-demand.php. Пример такого перенаправления для apache:

    #Redirect images to webp-on-demand.php (if browser supports webp)
    #RewriteCond %{HTTP_COOKIE} ^.*deb=true.*$ [NC]
    #RewriteCond %{HTTP_HOST} ^(.*)\.site\.ru$ [NC] # для конкретного сайта / поддомена
    RewriteCond %{HTTP_COOKIE} !^.*webpactive=false.*$ [NC]
    #RewriteCond %{REQUEST_URI} !^/admin(.*)$ [NC]
    RewriteCond %{REQUEST_URI} \.(jpg|jpeg|png)$ [NC]
    RewriteCond %{REQUEST_FILENAME} -f
    RewriteCond %{HTTP_COOKIE} ^.*webpactive=true.*$ [NC,OR]
    RewriteCond %{HTTP_ACCEPT} image/webp [NC,OR]
    RewriteCond %{HTTP_USER_AGENT} Chrome [OR]
    RewriteCond %{HTTP_USER_AGENT} "Google Page Speed Insights"
    RewriteRule ^(.*)\.(jpe?g|png)$ /php/webp/webp-on-demand.php [NC,L]
