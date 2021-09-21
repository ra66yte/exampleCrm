<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit9caffdd3aa2c06e9a736debdad76505f
{
    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'Workerman\\' => 10,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Workerman\\' => 
        array (
            0 => __DIR__ . '/..' . '/workerman/workerman',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit9caffdd3aa2c06e9a736debdad76505f::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit9caffdd3aa2c06e9a736debdad76505f::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit9caffdd3aa2c06e9a736debdad76505f::$classMap;

        }, null, ClassLoader::class);
    }
}