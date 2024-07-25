<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit9fecccd0761f578ec60d112322d331c5
{
    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'WebPConvert\\' => 12,
        ),
        'L' => 
        array (
            'LocateBinaries\\' => 15,
        ),
        'I' => 
        array (
            'ImageMimeTypeSniffer\\' => 21,
            'ImageMimeTypeGuesser\\' => 21,
        ),
        'F' => 
        array (
            'FileUtil\\' => 9,
        ),
        'E' => 
        array (
            'ExecWithFallback\\' => 17,
        ),
        'D' => 
        array (
            'DiDom\\' => 6,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'WebPConvert\\' => 
        array (
            0 => __DIR__ . '/..' . '/rosell-dk/webp-convert/src',
        ),
        'LocateBinaries\\' => 
        array (
            0 => __DIR__ . '/..' . '/rosell-dk/locate-binaries/src',
        ),
        'ImageMimeTypeSniffer\\' => 
        array (
            0 => __DIR__ . '/..' . '/rosell-dk/image-mime-type-sniffer/src',
        ),
        'ImageMimeTypeGuesser\\' => 
        array (
            0 => __DIR__ . '/..' . '/rosell-dk/image-mime-type-guesser/src',
        ),
        'FileUtil\\' => 
        array (
            0 => __DIR__ . '/..' . '/rosell-dk/file-util/src',
        ),
        'ExecWithFallback\\' => 
        array (
            0 => __DIR__ . '/..' . '/rosell-dk/exec-with-fallback/src',
        ),
        'DiDom\\' => 
        array (
            0 => __DIR__ . '/..' . '/imangazaliev/didom/src/DiDom',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit9fecccd0761f578ec60d112322d331c5::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit9fecccd0761f578ec60d112322d331c5::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit9fecccd0761f578ec60d112322d331c5::$classMap;

        }, null, ClassLoader::class);
    }
}
