<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit394db9ff3e8bed461b3131f49f543b23
{
    public static $prefixLengthsPsr4 = array (
        's' => 
        array (
            'setasign\\Fpdi\\' => 14,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'setasign\\Fpdi\\' => 
        array (
            0 => __DIR__ . '/..' . '/setasign/fpdi/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'FPDF' => __DIR__ . '/..' . '/setasign/fpdf/fpdf.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit394db9ff3e8bed461b3131f49f543b23::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit394db9ff3e8bed461b3131f49f543b23::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit394db9ff3e8bed461b3131f49f543b23::$classMap;

        }, null, ClassLoader::class);
    }
}
