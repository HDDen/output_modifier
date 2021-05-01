<?php

/* Использование

writeLog($logdata, true); - true если начинаем писать лог в начале всего скрипта, и нужно проконтролировать размер накопившегося лога.

*/

if (!function_exists('writeLog')){
    function writeLog($logdata = '', $newstarted = false){

        require(WEBPPROJECT.'/_settings.php'); // настройки по-умолчанию

        // logfile path
        if (defined('WEBP_OUTPUTMODIFIER_LOGPATH') && (WEBP_OUTPUTMODIFIER_LOGPATH != '')){
            $logfile = WEBP_OUTPUTMODIFIER_LOGPATH;
        } else {
            $logfile = $_SERVER['DOCUMENT_ROOT'].'/'.trim($webp_core_fallback_location, '/').'/log.txt';
        }

        date_default_timezone_set( 'Europe/Moscow' );

        // Контроль размера файла. Если он больше определенного размера, помещаем в архив и пересоздаем.
        $maxLogSize = 1000000; // мегабайт

        if ($newstarted){
            $actualLogSize = filesize($logfile);

            if ($actualLogSize >= $maxLogSize){
                $date = date('d-m-Y_H-i-s', time());

                $zip_file = dirname(__FILE__).'/_log_'.$date.'.zip';
                $zip = new ZipArchive();

                if ($zip->open($zip_file, ZIPARCHIVE::CREATE)!==TRUE)
                {
                    exit("cannot open <$zip_file>\n");
                }
                $zip->addFile($logfile);
                $zip->close();

                // Второй метод сжатия - если не отработал первый.
                if ( !file_exists($zip_file) ){

                    $bkp_to = dirname(__FILE__);
                    $bkp_name = '_log_'.$date.'.tar.gz';

                    $toarchive = shell_exec('tar -zcvf '.$bkp_to.'/'.$bkp_name.' '.$logfile.' ');
                    //$toarchive = shell_exec('tar -zcvf file.tar.gz /path/to/filename ');

                    $newlogdata = 'Прошли стадию паковки в гз'.PHP_EOL;
                    $newlogdata .= var_export($toarchive, true);
                }

                if ( (file_exists($zip_file)) || (file_exists($bkp_to.'/'.$bkp_name)) ){
                    unlink($logfile);
                }
            }
        }

        $date = date('d/m/Y H:i:s', time());
        file_put_contents($logfile, $date.': '.$logdata.PHP_EOL, FILE_APPEND | LOCK_EX);

    }
}


?>